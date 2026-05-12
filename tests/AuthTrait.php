<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthTrait 
{
    protected function loginAs(KernelBrowser $client, string $email, string $password): void
    {
        $client->request('GET', '/login');
        $client->submitForm('Войти', [
            'email' => $email,
            'password' => $password,
        ]);
        $client->followRedirect();
    }

    protected function loginAsUser(KernelBrowser $client): void
    {
        $this->loginAs($client, 'user@example.com', 'password');
    }

    protected function loginAsAdmin(KernelBrowser $client): void
    {
        $this->loginAs($client, 'admin@example.com', 'password');
    }

    protected function loginAsTestUser(KernelBrowser $client): void
    {
        $this->loginAs($client, 'testuser@example.com', 'password');
    }
}
