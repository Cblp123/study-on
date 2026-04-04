<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursesData = [
            [
                'code' => 'python-basa',
                'name' => 'Основы Python',
                'description' => 'Курс для начинающих. Синтаксис Python, структуры данных и основы ООП.',
            ],
            [
                'code' => 'html-css',
                'name' => 'Основы веб-разработки HTML и CSS',
                'description' => 'Создание современных веб-страниц с нуля используя HTML5 и CSS3.',
            ],
            [
                'code' => 'sql-beginner',
                'name' => 'SQL для начинающих',
                'description' => 'Основы реляционных баз данных и языка запросов SQL.',
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setCode($courseData['code']);
            $course->setName($courseData['name']);
            $course->setDescription($courseData['description']);
            $manager->persist($course);
            $this->addReference($courseData['code'], $course);
        }

        $manager->flush();
    }
}
