<?php

namespace App\Tests\Controller;

class LessonControllerTest extends AbstractControllerTest
{

    // проверка для неавторизованных пользователей
    public function testAnonymousCannotViewLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->filter('li a')->first()->link();
        $client->click($link);

        $this->assertResponseRedirects('/login');
    }

    public function testShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/999');

        $this->assertResponseStatusCodeSame(404);
    }

    // права доступа пользователя
    public function testUserCanViewLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Введение в Python');
    }

    public function testUserCannotAddLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $em = static::getContainer()->get('doctrine')->getManager();
        $course = $em->getRepository(\App\Entity\Course::class)->findOneBy([]);

        $client->request('GET', '/lessons/new?course_id=' . $course->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotEditLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $em = static::getContainer()->get('doctrine')->getManager();
        $lesson = $em->getRepository(\App\Entity\Lesson::class)->findOneBy([]);

        $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotDeleteLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $em = static::getContainer()->get('doctrine')->getManager();
        $lesson = $em->getRepository(\App\Entity\Lesson::class)->findOneBy([]);

        $client->request('POST', '/lessons/' . $lesson->getId());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserDoesNotSeeAdminButtons(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $this->assertSelectorNotExists('button:contains("Удалить")');
        $this->assertSelectorNotExists('a:contains("Редактировать")');
    }

    // права администратора
    public function testShow(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $this->assertSelectorExists('button:contains("Удалить")');
        $this->assertSelectorExists('a:contains("Редактировать")');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Введение в Python');
    }

    public function testAddLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $lessonsCountBefore = $crawler->filter('li')->count();

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

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

    public function testAddLessonValidationEmpty(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);

        $cases = [
            ['lesson[name]' => '', 'lesson[content]' => 'test'],
            ['lesson[name]' => 'test', 'lesson[content]' => ''],
        ];

        foreach ($cases as $data) {
            $form = $crawler->selectButton('Сохранить')->form($data);
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertSelectorExists('.invalid-feedback');
        }
    }

    public function testEditLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
        $crawler = $client->request('GET', '/courses');

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Изменённое название урока',
            'lesson[content]' => 'Изменённый контент',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $link = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($link);

        $this->assertSelectorTextContains('h1', 'Изменённое название урока');
    }

    public function testDeleteLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);
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

    public function testUserCanAccessLessonInPaidCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Пройти курс')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('h1');

        $lessonLink = $crawler->filter('li a')->first()->link();
        $crawler = $client->click($lessonLink);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testUserCannotAccessLessonInUnpaidCourse(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $links = $crawler->selectLink('Пройти курс');

        $courseLink = $links->eq(2)->link();
        $crawler = $client->click($courseLink);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('h1');

        $lessonLinks = $crawler->filter('li a');
        $lessonLink = $lessonLinks->first()->link();
        $client->click($lessonLink);

        $this->assertResponseStatusCodeSame(403);

    }

    public function testUserCanAccessRentedCourseLesson(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->loginAsUser($client);

        $crawler = $client->request('GET', '/courses');
        $this->assertResponseIsSuccessful();

        $links = $crawler->selectLink('Пройти курс');

        if (count($links) >= 2) {
            $courseLink = $links->eq(1)->link();
            $crawler = $client->click($courseLink);
            $this->assertResponseIsSuccessful();

            $lessonLink = $crawler->filter('li a')->first()->link();
            $crawler = $client->click($lessonLink);

            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('h1');
        }
    }
}
