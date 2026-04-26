<?php

namespace App\Tests\Controller;

class SecurityControllerTest extends AbstractControllerTest
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAs($client, 'user@example.com', 'password');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginFailure(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request('GET', '/login');
        $client->submitForm('Войти', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testLoginValidationFields(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $cases = [
            ['email' => '', 'password' => 'password'],
            ['email' => 'user@example.com', 'password' => ''],
            ['email' => 'user@example.com', 'password' => '123'],
            ['email' => 'user@example.com', 'password' => str_repeat('a', 200000)],
        ];

        foreach ($cases as $data) {
            $client->request('GET', '/login');
            $client->submitForm('Войти', $data);
            $client->followRedirect();

            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('.alert-danger');
        }
    }

    public function testLoginRedirectsIfAlreadyAuthenticated(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAs($client, 'user@example.com', 'password');
        $client->request('GET', '/login');

        $this->assertResponseRedirects();
    }

    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="registration_form[email]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testRegisterSuccess(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $client->request('GET', '/register');
        $client->submitForm('Зарегистрироваться', [
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[password][first]' => 'password123',
            'registration_form[password][second]' => 'password123',
        ]);

        $this->assertResponseRedirects();
    }

    public function testRegisterPasswordMismatch(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');
        $client->submitForm('Зарегистрироваться', [
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[password][first]' => 'password123',
            'registration_form[password][second]' => 'different123',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testRegisterValidationFields(): void
    {
        $client = static::createClient();

        $cases = [
            [
                'registration_form[email]' => '',
                'registration_form[password][first]' => 'password123',
                'registration_form[password][second]' => 'password123',
            ],
            [
                'registration_form[email]' => 'newuser@example.com',
                'registration_form[password][first]' => '123',
                'registration_form[password][second]' => '123',
            ],
            [
                'registration_form[email]' => 'newuser@example.com',
                'registration_form[password][first]' => 'password123',
                'registration_form[password][second]' => 'different123',
            ],
            [
                'registration_form[email]' => 'newuser@example.com' . str_repeat('a', 200000),
                'registration_form[password][first]' => 'password123',
                'registration_form[password][second]' => 'password123',
            ],
        ];

        foreach ($cases as $data) {
            $client->request('GET', '/register');
            $client->submitForm('Зарегистрироваться', $data);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    public function testRegisterDuplicateEmail(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');
        $client->submitForm('Зарегистрироваться', [
            'registration_form[email]' => 'user@example.com',
            'registration_form[password][first]' => 'password123',
            'registration_form[password][second]' => 'password123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testRegisterRedirectsIfAlreadyAuthenticated(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAs($client, 'user@example.com', 'password');
        $client->request('GET', '/register');

        $this->assertResponseRedirects('/profile');
    }
}
