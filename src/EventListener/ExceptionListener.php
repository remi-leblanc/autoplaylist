<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class ExceptionListener
{

    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface){
            $event->setResponse(new RedirectResponse($this->router->generate('error', [
                'errorType' => $exception->getStatusCode(),
            ])));
        }
        else{
            /* $event->setResponse(new RedirectResponse($this->router->generate('error', [
                'errorType' => '500',
            ]))); */
        }
        

    }
}