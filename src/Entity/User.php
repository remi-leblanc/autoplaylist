<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $playlistID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $refreshToken;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $selectedSubs = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $keywords = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $keywordSubs = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUserID(): ?string
    {
        return $this->userID;
    }

    public function setUserID(string $userID): self
    {
        $this->userID = $userID;

        return $this;
    }

    public function getPlaylistID(): ?string
    {
        return $this->playlistID;
    }

    public function setPlaylistID(?string $playlistID): self
    {
        $this->playlistID = $playlistID;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getSelectedSubs(): ?array
    {
        return $this->selectedSubs;
    }

    public function setSelectedSubs(?array $selectedSubs): self
    {
        $this->selectedSubs = $selectedSubs;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywordSubs(): ?array
    {
        return $this->keywordSubs;
    }

    public function setKeywordSubs(?array $keywordSubs): self
    {
        $this->keywordSubs = $keywordSubs;

        return $this;
    }
}
