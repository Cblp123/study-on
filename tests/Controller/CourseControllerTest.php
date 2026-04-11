<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    // Проверка, что на странице 3 курса
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('.card'));
    }

    // Страница курса
    public function testShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        // у первого курса python-basa 3 урока
        $this->assertCount(3, $crawler->filter('li'));
    }

    // 404 для несуществующего курса
    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/999');

        $this->assertResponseStatusCodeSame(404);
    }

    // Создание курса
    public function testCreateCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'test-course',
            'course[name]' => 'Новый тестовый курс',
            'course[description]' => 'Описание',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Новый тестовый курс');
        $this->assertSelectorTextContains('p', 'Описание');
    }

    // Проверка на пустые поля
    public function testCreateCourseValidationEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/new');

        $cases = [
            ['course[code]' => '', 'course[name]' => 'test-empty-code'],
            ['course[code]' => 'test-empty-name', 'course[name]' => ''],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    // Проверка на очень длинные значения полей
    public function testCreateCourseValidationLength(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/new');

        $cases = [
            ['course[code]' => str_repeat('0', 256), 'course[name]' => 'test-large-code'],
            ['course[code]' => 'test-large-name', 'course[name]' => str_repeat('0', 256)],
            ['course[code]' => 'test-large-description', 'course[name]' => 'test-large-description',
                'course[description]' => str_repeat('0', 1001)],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    // Редактирование курса
    public function testEditCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => 'Изменённое название',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Изменённое название');
    }

    // Удаление курса
    public function testDeleteCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/courses');
        $crawler = $client->followRedirect();

        // курсов осталось 2
        $this->assertCount(2, $crawler->filter('.card'));
    }
}
