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
            'token' =>
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3Nzc4Mzc2OTQsImV4cCI6OTk5OTk5OTk5OSwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoidXNlckBleGFtcGxlLmNvbSJ9.test',
            'refresh_token' => 'user-refresh-token',
        ],
        [
            'email' => 'admin@example.com',
            'password' => 'password',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'balance' => 999.0,
            'token' =>
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3Nzc4Mzc2OTQsImV4cCI6OTk5OTk5OTk5OSwicm9sZXMiOlsiUk9MRV9TVVBFUl9BRE1JTiJdLCJ1c2VybmFtZSI6ImFkbWluQGV4YW1wbGUuY29tIn0.test',
            'refresh_token' => 'admin-refresh-token',
        ],
        [
            'email' => 'testuser@example.com',
            'password' => 'password',
            'roles' => ['ROLE_USER'],
            'balance' => 5000.0,
            'token' =>
                'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3Nzc4Mzc2OTQsImV4cCI6OTk5OTk5OTk5OSwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoidGVzdHVzZXJAZXhhbXBsZS5jb20ifQ.test',
            'refresh_token' => 'testuser-refresh-token',
        ],
    ];

    private array $courses = [
        [
            'code' => 'python-basa',
            'type' => 'free',
            'price' => '0.00',
        ],
        [
            'code' => 'html-css',
            'type' => 'rent',
            'price' => '100.00',
        ],
        [
            'code' => 'sql-beginner',
            'type' => 'buy',
            'price' => '200.00',
        ],
    ];

    private array $transactions = [];

    public function __construct()
    {
        // Инициализируем транзакции для пользователей, используя токены из массива users
        $this->transactions = [
            $this->users[0]['token'] => [
                [
                    'id' => 1,
                    'created_at' => '2026-04-28T10:00:00+00:00',
                    'type' => 'deposit',
                    'amount' => '1000.00',
                ],
                [
                    'id' => 2,
                    'created_at' => '2026-04-29T11:00:00+00:00',
                    'type' => 'payment',
                    'course_code' => 'sql-beginner',
                    'amount' => '200.00',
                ],
                [
                    'id' => 3,
                    'created_at' => '2026-04-30T12:00:00+00:00',
                    'type' => 'payment',
                    'course_code' => 'html-css',
                    'amount' => '100.00',
                    'expires_at' => '2026-05-07T12:00:00+00:00',
                ],
            ],
            $this->users[1]['token'] => [
                [
                    'id' => 4,
                    'created_at' => '2026-04-25T10:00:00+00:00',
                    'type' => 'deposit',
                    'amount' => '5000.00',
                ],
            ],
            $this->users[2]['token'] => [
                [
                    'id' => 5,
                    'created_at' => '2026-04-20T10:00:00+00:00',
                    'type' => 'deposit',
                    'amount' => '5000.00',
                ],
            ],
        ];
    }

    public function auth(string $email, string $password): array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email && $user['password'] === $password) {
                return [
                    'token' => $user['token'],
                    'refresh_token' => $user['refresh_token'],
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
            'refresh_token' => 'new-user-refresh-token',
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

    public function getCourses(): array
    {
        return $this->courses;
    }

    public function getCourse(string $code): array
    {
        foreach ($this->courses as $course) {
            if ($course['code'] === $code) {
                return $course;
            }
        }

        return [
            'code' => 404,
            'message' => 'Course not found',
        ];
    }

    public function payCourse(string $code, string $token): array
    {
        $course = null;
        foreach ($this->courses as $c) {
            if ($c['code'] === $code) {
                $course = $c;
                break;
            }
        }

        if (!$course) {
            return [
                'code' => 404,
                'message' => 'Course not found',
            ];
        }

        $user = null;
        foreach ($this->users as $u) {
            if ($u['token'] === $token) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Unauthorized',
            ];
        }

        $price = (float) $course['price'];
        if ($user['balance'] < $price) {
            return [
                'code' => 406,
                'message' => 'На вашем счету недостаточно средств',
            ];
        }

        foreach ($this->users as &$u) {
            if ($u['token'] === $token) {
                $u['balance'] -= $price;
                break;
            }
        }

        if (!isset($this->transactions[$token])) {
            $this->transactions[$token] = [];
        }

        $transactionId = max(array_map(fn($t) => $t['id'] ?? 0, array_merge(...array_values($this->transactions)))) + 1;
        $transaction = [
            'id' => $transactionId,
            'created_at' => date('c'),
            'type' => 'payment',
            'course_code' => $code,
            'amount' => $course['price'],
        ];

        if ($course['type'] === 'rent') {
            $transaction['expires_at'] = date('c', strtotime('+1 week'));
        }

        $this->transactions[$token][] = $transaction;

        return [
            'success' => true,
            'course_type' => $course['type'],
            'expires_at' => $transaction['expires_at'] ?? null,
        ];
    }

    public function getTransactions(string $token, array $filters = []): array
    {
        if (!isset($this->transactions[$token])) {
            return [];
        }

        $transactions = $this->transactions[$token];

        // Применяем фильтры
        if (!empty($filters['filter']['type'])) {
            $transactions = array_filter($transactions, fn($t) => $t['type'] === $filters['filter']['type']);
        }

        if (!empty($filters['filter']['course_code'])) {
            $transactions = array_filter(
                $transactions,
                fn($t) => (
                    $t['course_code'] ?? null) === $filters['filter']['course_code']
            );
        }

        if (!empty($filters['filter']['skip_expired'])) {
            $now = new \DateTime();
            $transactions = array_filter($transactions, function ($t) use ($now) {
                if (empty($t['expires_at'])) {
                    return true;
                }
                $expiresAt = new \DateTime($t['expires_at']);
                return $expiresAt > $now;
            });
        }

        return array_values($transactions);
    }

    public function refreshToken(string $refreshToken): array
    {
        foreach ($this->users as $user) {
            if ($user['refresh_token'] === $refreshToken) {
                return [
                    'token' => $user['token'],
                    'refresh_token' => $user['refresh_token'],
                ];
            }
        }

        return [
            'code' => 401,
            'message' => 'Invalid refresh token',
        ];
    }
}
