<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccessDeniedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof AccessDeniedException) {
            $request = $event->getRequest();
            $request->getSession()->getFlashBag()->add('danger', 'Accès refusé : vous n\'avez pas les droits nécessaires');
            $event->setResponse(new RedirectResponse($request->headers->get('referer') ?? '/'));
        }
    }
}