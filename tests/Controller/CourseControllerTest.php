<?php

namespace App\Tests\Controller;

class CourseControllerTest extends AbstractControllerTest
{
    // проверка для неавторизованных пользователей
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('.card'));

        $this->assertSelectorExists('a:contains("Войти")');
        $this->assertSelectorExists('a:contains("Регистрация")');

        $this->assertSelectorNotExists('a:contains("Добавить курс")');
    }

    public function testShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('li'));

        $this->assertSelectorNotExists('a:contains("Редактировать")');
        $this->assertSelectorNotExists('button:contains("Удалить")');
        $this->assertSelectorNotExists('a:contains("Добавить урок")');
    }

    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnonymousCannotCreateCourse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/new');

        $this->assertResponseRedirects('/login');
    }

    // права доступа пользователя
    public function testUserCannotCreateCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotEditCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/courses');
        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);
        $courseUrl = $client->getRequest()->getUri();

        $client->request('GET', $courseUrl . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserDoesNotSeeAdminButtons(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/courses');

        $this->assertSelectorNotExists('a:contains("Добавить курс")');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $this->assertSelectorNotExists('a:contains("Редактировать")');
        $this->assertSelectorNotExists('button:contains("Удалить")');
        $this->assertSelectorNotExists('a:contains("Добавить урок")');
    }

    // права администратора
    public function testCreateCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/courses');
        $this->assertSelectorExists('a:contains("Добавить курс")');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $this->assertSelectorExists('a:contains("Редактировать")');
        $this->assertSelectorExists('button:contains("Удалить")');
        $this->assertSelectorExists('a:contains("Добавить урок")');

        $link = $crawler->selectLink('К списку курсов')->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить курс')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'test-course',
            'course[name]' => 'Новый тестовый курс',
            'course[description]' => 'Описание',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Новый тестовый курс');
    }

    public function testCreateCourseValidationUniqueCode(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses/new');

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'python-basa',
            'course[name]' => 'test',
        ]);
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testCreateCourseValidationEmpty(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses/new');

        $cases = [
            ['course[code]' => '', 'course[name]' => 'test'],
            ['course[code]' => 'test', 'course[name]' => ''],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    public function testCreateCourseValidationLength(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses/new');

        $cases = [
            ['course[code]' => str_repeat('0', 256), 'course[name]' => 'test'],
            ['course[code]' => 'test', 'course[name]' => str_repeat('0', 256)],
            ['course[code]' => 'test2', 'course[name]' => 'test2', 'course[description]' => str_repeat('0', 1001)],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    public function testEditCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => 'Изменённое название',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Изменённое название');
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/courses');
        $crawler = $client->followRedirect();
        $this->assertCount(2, $crawler->filter('.card'));
    }
}
