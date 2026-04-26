<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;

final class BillingClientMock extends BillingClient
{
    private array $users = [
        [
            'email' => 'user@example.com',
            'password' => 'password',
            'roles' => ['ROLE_USER'],
            'balance' => 100.0,
            'token' => 'user-test-token',
        ],
        [
            'email' => 'admin@example.com',
            'password' => 'password',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'balance' => 999.0,
            'token' => 'admin-test-token',
        ],
    ];

    public function __construct()
    {
    }

    public function auth(string $email, string $password): array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email && $user['password'] === $password) {
                return [
                    'token' => $user['token'],
                ];
            }
        }

        return [
            'code' => 401,
            'message' => 'Invalid credentials.',
        ];
    }

    public function register(string $email, string $password): array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return [
                    'code' => 422,
                    'message' => 'Пользователь с таким email уже существует',
                ];
            }
        }

        return [
            'token' => 'new-user-test-token',
            'roles' => ['ROLE_USER'],
            'email' => $email,
        ];
    }

    public function getCurrentUser(string $token): array
    {
        foreach ($this->users as $user) {
            if ($user['token'] === $token) {
                return [
                    'username' => $user['email'],
                    'roles' => $user['roles'],
                    'balance' => $user['balance'],
                ];
            }
        }

        return [
            'code' => 401,
            'message' => 'JWT Token not found',
        ];
    }
}
