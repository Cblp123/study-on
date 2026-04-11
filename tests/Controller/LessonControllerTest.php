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
            'lesson[orderNumber]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertCount($lessonsCountBefore + 1, $crawler->filter('li'));
    }

    // Проверка на пустые поля
    public function testAddLessonValidationEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $cases = [
            ['lesson[name]' => '', 'lesson[content]' => 'test-empty-name'],
            ['lesson[name]' => 'test-empty-content', 'lesson[content]' => ''],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    // Проверка на очень длинные значения полей
    public function testAddLessonValidationLength(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $cases = [
            ['lesson[name]' => str_repeat('0', 256), 'lesson[content]' => 'test-large-name'],
            ['lesson[name]' => 'test-large-content', 'lesson[content]' => str_repeat('0', 10_001)],
            ['lesson[name]' => 'test-large-order', 'lesson[content]' => 'test-large-order',
                'lesson[orderNumber]' => 10_001],
            ['lesson[name]' => 'test-short-order', 'lesson[content]' => 'test-short-order',
                'lesson[orderNumber]' => -10_001],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    // Проверка числового типа поля orderNumber
    public function testAddLessonValidationIntegerType(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'test-strigtype-order',
            'lesson[content]' => 'test-strigtype-order',
            'lesson[orderNumber]' => 'invalid-order-number',
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
