<?php

namespace App\Tests\Controller;

class ProfileControllerTest extends AbstractControllerTest
{
    public function testAnonymousCannotViewProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertResponseRedirects('/login');
    }

    public function testUserCanViewProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('user@example.com')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'user@example.com');
        $this->assertSelectorTextContains('body', 'Пользователь');
        $this->assertSelectorTextContains('body', '100');
    }

    public function testAdminCanViewProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('admin@example.com')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'admin@example.com');
        $this->assertSelectorTextContains('body', 'Администратор');
        $this->assertSelectorTextContains('body', '999');
    }
}
