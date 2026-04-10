<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonControllerTest extends WebTestCase
{
    // Страница урока
    public function testShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        // первый урок в первом курсе
        $this->assertSelectorTextContains('h1', 'Введение в Python');
    }

    // 404 для несуществующего урока
    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/999');

        $this->assertResponseStatusCodeSame(404);
    }

    // Добавление урока через страницу курса
    public function testAddLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $lessonsCountBefore = $crawler->filter('li')->count();

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Новый урок',
            'lesson[content]' => 'Контент урока',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertCount($lessonsCountBefore + 1, $crawler->filter('li'));
    }

    // Проверка на пустые поля
    public function testAddLessonValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => '',
            'lesson[content]' => '',
        ]);

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('.invalid-feedback');
    }

    // Удаление урока
    public function testDeleteLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $lessonsCountBefore = $crawler->filter('li')->count();

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertCount($lessonsCountBefore - 1, $crawler->filter('li'));
    }
}
