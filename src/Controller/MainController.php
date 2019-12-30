<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class MainController extends AbstractController
{

	private $session;
	private $client;
	private $service;

	private function newClient(){
		$httpClient = new \GuzzleHttp\Client(['verify' => false]);
		$this->client = new \Google_Client(['verify' => false]);
		$this->client->setAuthConfig('../client_secret.json');
		$this->client->setHttpClient($httpClient);
		$this->client->setApplicationName('AutoPlaylist');
		$this->client->setDeveloperKey("AIzaSyBSFLPC92s2hA3yNAVzdS1HIJ1Bg4lonJ8");
		$this->client->setScopes('https://www.googleapis.com/auth/youtube');
		$this->client->setRedirectUri('http://89.234.182.22/auth/response');
		$this->client->setAccessType('offline');
	}

    public function __construct(SessionInterface $session)
    {
		$this->session = $session;

		require_once '../vendor/autoload.php';

		$this->newClient();

		$this->service = new \Google_Service_YouTube($this->client);
		 
    }

	/**
    * @Route("/", name="home")
    */
	public function home(UserRepository $userRepository)
	{

		return $this->render('home.html.twig', [

		]);

	}

	/**
    * @Route("/auth", name="auth")
    */
	public function auth(Request $request, UserRepository $userRepository)
	{

		$auth_url = $this->client->createAuthUrl();

		return $this->redirect(filter_var($auth_url, FILTER_SANITIZE_URL));

	}

	/**
    * @Route("/auth/response", name="authResponse")
    */
	public function authResponse(Request $request, UserRepository $userRepository)
	{

		$authCode = $request->query->get('code');
		if($authCode == null){
			return $this->redirectToRoute('home');
		}
		$this->client->authenticate($authCode);
		$refreshToken = $this->client->getAccessToken();

		$queryParams = [
			'mine' => true
		];
		$response = $this->service->channels->listChannels('id, snippet, status', $queryParams);

		$userID = $response['items'][0]['id'];
		$username = $response['items'][0]['snippet']['title'];
		$userImage = $response['items'][0]['snippet']['thumbnails']['default']['url'];
		$userPrivacy = $response['items'][0]['status']['privacyStatus'];

		if($userPrivacy == "private"){
			return $this->redirectToRoute('error', ['errorType' => 'privacy']);
		}

		$user = $userRepository->findOneBy(['userID' => $userID]);
		if(!$user){

			$playlist = new \Google_Service_YouTube_Playlist();
			$playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
			$playlistSnippet->setTitle('AutoPlaylist2');
			$playlist->setSnippet($playlistSnippet);
			$playlistId = $this->service->playlists->insert('snippet, status', $playlist);

			$user = new User(); // initialise l'entité
			$user->setUsername($username); // on set les différents champs
			$user->setUserID($userID);
			$user->setPlaylistID($playlistId['id']);
			$user->setRefreshToken($refreshToken['refresh_token']);
			$em = $this->getDoctrine()->getManager(); // on récupère le gestionnaire d'entité
			$em->persist($user); // on déclare une modification de type persist et la génération des différents liens entre entité
			$em->flush(); // on effectue les différentes modifications sur la base de données 
			// réelle

			$this->session->set('newUser', true);
		}

		$this->session->set('username', $username);
		$this->session->set('userID', $userID);
		$this->session->set('userImage', $userImage);

		return $this->redirectToRoute('user');

	}

	/**
    * @Route("/user", name="user")
    */
	public function user(Request $request, UserRepository $userRepository)
	{

		$username = $this->session->get('username');
		$userID = $this->session->get('userID');
		$userImage = $this->session->get('userImage');

		$user = $userRepository->findOneBy(['userID' => $userID]);

		$DBselectedSubs = $user->getSelectedSubs();
		$DBkeywordSubs = $user->getKeywordSubs();
		$DBkeywords = $user->getKeywords();

		$subscriptions = [];
		
		//Get all subscriptions of specified channel
		$queryParams = [
			'channelId' => $userID,
			'maxResults' => 50,
			'order' => 'alphabetical'
		];
		try{
			$response = $this->service->subscriptions->listSubscriptions('snippet, contentDetails, id', $queryParams);
		}
		catch(\Exception $e){
			return $this->redirectToRoute('error', ['errorType' => 'privacy']);
		}
		
		//Store subsciptions in array
		foreach($response['items'] as $item) {
			$subData = [
				'name' => $item['snippet']['title'],
				'id' => $item['snippet']['resourceId']['channelId'],
				'image' => $item['snippet']['thumbnails']['default']['url'],
				'selected' => false,
				'type' => 'all',
			];
			if($DBselectedSubs !== null){
				if(in_array ($item['snippet']['resourceId']['channelId'], $DBselectedSubs)){
					$subData['selected'] = true;
				}
			}
			if($DBkeywordSubs !== null){
				if(in_array ($item['snippet']['resourceId']['channelId'], $DBkeywordSubs)){
					$subData['selected'] = true;
					$subData['type'] = 'keywords';
				}
			}
			array_push($subscriptions, $subData);
		}

		// If there are more pages, go to next page, pageToken = nextPageToken
		while ($response['nextPageToken']) {
			$queryParams['pageToken'] = $response['nextPageToken'];
			$response = $this->service->subscriptions->listSubscriptions('snippet, contentDetails, id', $queryParams);

			//Display list of subscriptions names
			foreach($response['items'] as $item) {
				$subData = [
					'name' => $item['snippet']['title'],
					'id' => $item['snippet']['resourceId']['channelId'],
					'image' => $item['snippet']['thumbnails']['default']['url'],
					'selected' => false,
					'type' => 'all',
				];
				if($DBselectedSubs !== null){
					if(in_array ($item['snippet']['resourceId']['channelId'], $DBselectedSubs)){
						$subData['selected'] = true;
					}
				}
				if($DBkeywordSubs !== null){
					if(in_array ($item['snippet']['resourceId']['channelId'], $DBkeywordSubs)){
						$subData['selected'] = true;
						$subData['type'] = 'keywords';
					}
				}
				array_push($subscriptions, $subData);
			}

		}

		usort($subscriptions, function ($item1, $item2) {
			return strnatcasecmp($item1['name'], $item2['name']);
		});

		return $this->render('user.html.twig', [
			'username' => $username,
			'userImage' => $userImage,
			'subscriptions' => $subscriptions,
			'keywords' => $DBkeywords,
			'newUser' => $this->session->get('newUser'),
		]);

	}


	/**
    * @Route("/update", name="update", condition="request.isXmlHttpRequest()")
    */
	public function update(Request $request, UserRepository $userRepository)
	{

		$userID = $this->session->get('userID');
		$user = $userRepository->findOneBy(['userID' => $userID]);

		$selectedSubs = $request->request->get('selected_subs');
		$keywordSubs = $request->request->get('keyword_subs');
		$keywords = $request->request->get('keywords');

		$subCount = 0;
		if($selectedSubs == null && $keywordSubs !== null){
			$subCount = count($keywordSubs);
		}
		elseif($selectedSubs !== null && $keywordSubs == null){
			$subCount = count($selectedSubs);
		}
		elseif($selectedSubs !== null && $keywordSubs !== null){
			$subCount = count($selectedSubs) + count($keywordSubs);
		}
		
		if($subCount <= 30){
			$user->setSelectedSubs($selectedSubs);
			$user->setKeywordSubs($keywordSubs);
			$user->setKeywords($keywords);
			$em = $this->getDoctrine()->getManager();
			$em->flush();

			echo json_encode("");
		}
		else{
			echo json_encode("You have exceeded the limit of selected channels");
		}

		return new Response();

	}

	/**
    * @Route("/insert", name="insert")
    */
	public function insert(Request $request, UserRepository $userRepository)
	{

		/* if($request->getClientIp() !== "::1" || $request->getClientIp() !== "localhost" || $request->getClientIp() !== "127.0.0.1"){
			return $this->redirectToRoute('error', ['errorType' => '404']);
		} */

		$users = $userRepository->findall();

		$clientSecret = json_decode(file_get_contents('../client_secret.json'), true);

		foreach($users as $user){

			$playlist = $user->getPlaylistID();
			$channels = $user->getSelectedSubs();
			$keywordsChannels = $user->getKeywordSubs();
			$keywords = $user->getKeywords();
			$refreshToken = $user->getRefreshToken();

			//Get Access Token from Refresh Token
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $clientSecret['web']['token_uri'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => 'grant_type=refresh_token&client_id='.$clientSecret['web']['client_id'].'&client_secret='.$clientSecret['web']['client_secret'].'&refresh_token='.$refreshToken,
				CURLOPT_HTTPHEADER => array('content-type: application/x-www-form-urlencoded'),
				CURLOPT_SSL_VERIFYHOST => 'false',
				CURLOPT_SSL_VERIFYPEER => 'false',
			));
			$accessToken = curl_exec($curl);
			curl_close($curl);

			$this->newClient();
			$this->client->setAccessToken($accessToken);

			$this->service = new \Google_Service_YouTube($this->client);


			//Get channels last uploaded video
			$lastVideos = [];

			foreach((array) $channels as $channel){
				$queryParams = [
					'maxResults' => 1,
					'channelId' => $channel,
				];
				$response = $this->service->activities->listActivities('snippet, contentDetails', $queryParams);
				array_push($lastVideos, $response['items'][0]['contentDetails']['upload']['videoId']);
			}
			
			//Get channels last uploaded video if title contain a keyword
			foreach((array) $keywordsChannels as $channel){
				$queryParams = [
					'maxResults' => 1,
					'channelId' => $channel,
				];
				$response = $this->service->activities->listActivities('snippet, contentDetails', $queryParams);
				foreach($keywords as $keyword){
					if(strpos(strtoupper($response['items'][0]['snippet']['title']), strtoupper($keyword)) !== false) {
						array_push($lastVideos, $response['items'][0]['contentDetails']['upload']['videoId']);
					}
				}
			}

			//Get all videos in specified playlist
			$playlistVideos = [];

			$queryParams = [
				'playlistId' => $playlist,
				'maxResults' => 50,
			];
			$response = $this->service->playlistItems->listPlaylistItems('snippet,contentDetails', $queryParams);
		
			// Store vids ids into array
			foreach($response['items'] as $item) {
				array_push($playlistVideos, $item['snippet']['resourceId']['videoId']);
			}
		
			// If there are more pages, go to next page, pageToken = nextPageToken
			while ($response['nextPageToken']) {
				$queryParams['pageToken'] = $response['nextPageToken'];
				$response = $this->service->playlistItems->listPlaylistItems('snippet,contentDetails', $queryParams);
		
				// Store vids ids into array
				foreach($response['items'] as $item) {
					array_push($playlistVideos, $item['snippet']['resourceId']['videoId']);
				}
			}

			//Substract actual videos to new videos found
			$newVideos = array_diff($lastVideos, $playlistVideos);

			
			//Init playlist insert and set parameters
			$playlistItem = new \Google_Service_YouTube_PlaylistItem();
			
			$playlistItemSnippet = new \Google_Service_YouTube_PlaylistItemSnippet();
			$playlistItemSnippet->setPlaylistId($playlist);
			
			$resourceId = new \Google_Service_YouTube_ResourceId();
			$resourceId->setKind('youtube#video');
			
			//Insert new videos in playlist
			foreach($newVideos as $video){
				$resourceId->setVideoId($video);
			
				$playlistItemSnippet->setResourceId($resourceId);
				$playlistItem->setSnippet($playlistItemSnippet);
				
				$response = $this->service->playlistItems->insert('snippet', $playlistItem);
			}

		}

		return new Response();

	}

	/**
    * @Route("/error/{errorType}", name="error")
	*/
	public function error($errorType, Request $request, UserRepository $userRepository)
	{

		return $this->render('error-'.$errorType.'.html.twig');

	}


}
