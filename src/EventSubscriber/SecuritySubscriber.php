<?php

namespace App\EventSubscriber;

use App\Security\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SecuritySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->request->getBoolean('_remember_me')) {
            return;
        }

        $user = $event->getAuthenticatedToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $refreshToken = $user->getRefreshToken();
        if ($refreshToken === null) {
            return;
        }

        $cookie = Cookie::create('refresh_token')
            ->withValue($refreshToken)
            ->withExpires(new \DateTimeImmutable('+30 days'))
            ->withHttpOnly(true)
            ->withSameSite('strict');

        $event->getResponse()->headers->setCookie($cookie);
    }
}
