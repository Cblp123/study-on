<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class BillingClient
{
    private string $billingUrl;

    public function __construct(string $billingUrl)
    {
        $this->billingUrl = $billingUrl;
    }

    /**
     * запрос к billing
     * @throws BillingUnavailableException
     */
    private function request(string $method, string $path, array $data = [], ?string $token = null): array
    {
        $ch = curl_init($this->billingUrl . $path);

        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException();
        }

        curl_close($ch);

        return json_decode($response, true);
    }


    /**
     * @throws BillingUnavailableException
     */
    public function auth(string $email, string $password): array
    {
        return $this->request('POST', '/api/v1/auth', [
            'username' => $email,
            'password' => $password,
        ]);
    }

    public function register(string $email, string $password): array
    {
        return $this->request('POST', '/api/v1/register', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCurrentUser(string $token): array
    {
        return $this->request('GET', '/api/v1/users/current', token: $token);
    }

    /**
     * @throws BillingUnavailableException
     */
    public function refreshToken(string $refreshToken): array
    {
        $ch = curl_init($this->billingUrl . '/api/v1/token/refresh');

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['refresh_token' => $refreshToken]));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new BillingUnavailableException();
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
