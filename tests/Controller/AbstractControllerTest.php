<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractControllerTest extends WebTestCase
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
}
