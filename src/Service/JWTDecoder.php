<?php

namespace App\Service;

class JWTDecoder
{
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Неверный формат JWT токена.');
        }

        $payload = $parts[1];
        $payload = base64_decode(str_pad($payload, strlen($payload) + (4 - strlen($payload) % 4) % 4, '='));

        $data = json_decode($payload, true);

        if ($data === null) {
            throw new \InvalidArgumentException('Не удалось декодировать JWT токен.');
        }

        return $data;
    }

    public function isExpired(string $token, int $leeway = 30): bool
    {
        $payload = $this->decode($token);

        if (!isset($payload['exp'])) {
            return false;
        }

        return $payload['exp'] - $leeway < time();
    }
}
