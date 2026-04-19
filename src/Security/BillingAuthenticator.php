<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private RouterInterface $router, private BillingClient $billingClient)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        try {
            // получаем токен
            $response = $this->billingClient->auth($email, $password);
        } catch (BillingUnavailableException $e) {
            throw new CustomUserMessageAuthenticationException(
                'Сервис временно недоступен. Попробуйте авторизоваться позднее.'
            );
        }

        if (isset($response['error'])) {
            throw new CustomUserMessageAuthenticationException($response['error']);
        }

        if (isset($response['message'])) {
            throw new CustomUserMessageAuthenticationException($response['message']);
        }

        $token = $response['token'];

        $userLoader = function () use ($token): User {
            try {
                $userData = $this->billingClient->getCurrentUser($token);
            } catch (BillingUnavailableException $e) {
                throw new CustomUserMessageAuthenticationException(
                    'Сервис временно недоступен. Попробуйте авторизоваться позднее.'
                );
            }

            $user = new User();
            $user->setEmail($userData['username']);
            $user->setRoles($userData['roles']);
            $user->setBalance($userData['balance']);
            $user->setApiToken($token);

            return $user;
        };

        return new SelfValidatingPassport(
            new UserBadge($token, $userLoader),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
