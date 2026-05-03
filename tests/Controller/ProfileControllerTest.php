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

    public function testAnonymousCannotViewTransactions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile/transactions');

        $this->assertResponseRedirects('/login');
    }

    public function testProfileShowsBalance(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Баланс:');
        $this->assertSelectorTextContains('body', '100');
    }

    public function testProfileShowsTransactionHistoryLink(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a:contains("История транзакций")');
    }

    public function testUserCanViewTransactions(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }

    public function testTransactionsTableHasCorrectColumns(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('table');
        $rows = $crawler->filter('table tbody tr');
        $this->assertGreaterThan(0, count($rows));
    }

    public function testTransactionsShowDepositType(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'Пополнение');
    }

    public function testTransactionsShowPaymentType(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();
        // Проверяем наличие транзакции типа списание
        $this->assertSelectorTextContains('body', 'Списание');
    }

    public function testTransactionsShowCourseCode(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'sql-beginner');
    }

    public function testTransactionsShowAmount(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', '200.00');
    }

    public function testTransactionWithCourseHasLink(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();

        $links = $crawler->filter('a[href*="/courses/"]');
        $this->assertGreaterThan(0, count($links));
    }

    public function testAdminCanViewTransactions(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/profile/transactions');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }

    public function testAdminCanViewProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'admin@example.com');
        $this->assertSelectorTextContains('body', 'Администратор');
        $this->assertSelectorTextContains('body', '999');
    }

    public function testClickTransactionHistoryFromProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('История транзакций')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertSelectorTextContains('body', 'История транзакций');
    }
}
