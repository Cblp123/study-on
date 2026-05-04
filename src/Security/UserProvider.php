<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use App\Service\JWTDecoder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private BillingClient $billingClient,
        private JWTDecoder $jwtDecoder,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws BillingUnavailableException
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $refreshToken = $request?->cookies->get('refresh_token');

        if (!$refreshToken) {
            throw new UserNotFoundException('Refresh token не найден.');
        }

        try {
            $response = $this->billingClient->refreshToken($refreshToken);
        } catch (BillingUnavailableException $e) {
            throw new UserNotFoundException('Сервис временно недоступен.');
        }

        if (isset($response['code'])) {
            throw new UserNotFoundException($response['message'] ?? 'Ошибка обновления токена.');
        }

        $newApiToken = $response['token'];
        $refreshToken = $response['refresh_token'];

        try {
            $userData = $this->billingClient->getCurrentUser($newApiToken);
        } catch (BillingUnavailableException $e) {
            throw new UserNotFoundException('Сервис временно недоступен.');
        }

        if ($identifier !== $userData['username']) {
            throw new UserNotFoundException('Токен принадлежит другому пользователю');
        }

        $user = new User();
        $user->setEmail($userData['username']);
        $user->setRoles($userData['roles']);
        $user->setBalance($userData['balance']);
        $user->setApiToken($newApiToken);
        $user->setRefreshToken($refreshToken);

        return $user;
    }

    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        if (!$this->jwtDecoder->isExpired($user->getApiToken())) {
            return $user;
        }

        try {
            $response = $this->billingClient->refreshToken($user->getRefreshToken());
        } catch (BillingUnavailableException $e) {
            return $user;
        }

        if (isset($response['code'])) {
            throw new UserNotFoundException('Сессия истекла, войдите снова.');
        }

        $user->setApiToken($response['token']);
        $user->setRefreshToken($response['refresh_token']);

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // TODO: when hashed passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newHashedPassword);
    }
}
