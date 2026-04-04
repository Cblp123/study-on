<?php

namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LessonFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lessonsData = [
            [
                'course' => 'python-basa',
                'name' => 'Введение в Python',
                'content' => 'Установка окружения, применение языка.',
                'order' => 1,
            ],
            [
                'course' => 'python-basa',
                'name' => 'Переменные и типы данных. 1 часть',
                'content' => 'Числа, строки, списки, словари и работа с ними.',
                'order' => 2,
            ],
            [
                'course' => 'python-basa',
                'name' => 'Переменные и типы данных. 2 часть',
                'content' => 'Кортежи и множества.',
                'order' => 3,
            ],
            [
                'course' => 'html-css',
                'name' => 'Структура HTML-документа',
                'content' => 'Теги, атрибуты, структура страницы, семантическая разметка.',
                'order' => 1,
            ],
            [
                'course' => 'html-css',
                'name' => 'Основы CSS',
                'content' => 'Селекторы, свойства, позиционирование.',
                'order' => 2,
            ],
            [
                'course' => 'html-css',
                'name' => 'Базовые механики построения сеток',
                'content' => 'Техники создания крупных сеток страниц и мелких сеток компонентов.',
                'order' => 3,
            ],
            [
                'course' => 'sql-beginner',
                'name' => 'Что такое база данных',
                'content' => 'Реляционные БД, таблицы, строки, столбцы, первичные ключи.',
                'order' => 1,
            ],
            [
                'course' => 'sql-beginner',
                'name' => 'SELECT-запросы',
                'content' => 'Выборка данных, фильтрация через WHERE, сортировка ORDER BY.',
                'order' => 2,
            ],
            [
                'course' => 'sql-beginner',
                'name' => 'Агрегатные функции',
                'content' => 'MIN, MAX, COUNT, AVG, SUM.',
                'order' => 3,
            ],
            [
                'course' => 'sql-beginner',
                'name' => 'Функции для работы со строками',
                'content' => 'CONCAT, TRIM, UPPER и другие.',
                'order' => 4,
            ],
        ];

        foreach ($lessonsData as $lessonData) {
            $lesson = new Lesson();
            $lesson->setCourse($this->getReference($lessonData['course'], Course::class));
            $lesson->setName($lessonData['name']);
            $lesson->setContent($lessonData['content']);
            $lesson->setOrderNumber($lessonData['order']);
            $manager->persist($lesson);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CourseFixtures::class];
    }
}
