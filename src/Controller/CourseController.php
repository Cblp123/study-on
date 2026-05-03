<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
final class CourseController extends AbstractController
{
    public function __construct(private BillingClient $billingClient)
    {
    }

    #[Route(name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findAll();
        $billingCourses = [];
        $userTransactions = [];

        try {
            foreach ($this->billingClient->getCourses() as $billingCourse) {
                $billingCourses[$billingCourse['code']] = $billingCourse;
            }

            if ($this->getUser()) {
                $token = $this->getUser()->getApiToken();
                $transactions = $this->billingClient->getTransactions($token, [
                    'filter' => ['type' => 'payment', 'skip_expired' => true],
                ]);
                foreach ($transactions as $transaction) {
                    if (!empty($transaction['course_code'])) {
                        $userTransactions[$transaction['course_code']] = $transaction;
                    }
                }
            }
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
        }

        $coursesData = array_map(fn(Course $course) => [
            'course' => $course,
            'billing' => $billingCourses[$course->getCode()] ?? null,
            'transaction' => $userTransactions[$course->getCode()] ?? null,
        ], $courses);

        return $this->render('course/index.html.twig', [
            'coursesData' => $coursesData,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $billingCourse = null;
        $userTransaction = null;

        try {
            $billingCourse = $this->billingClient->getCourse($course->getCode());
            if (!isset($billingCourse['type'])) {
                $billingCourse = null;
            }

            if ($this->getUser()) {
                $token = $this->getUser()->getApiToken();
                $transactions = $this->billingClient->getTransactions($token, [
                    'filter' => [
                        'type' => 'payment',
                        'course_code' => $course->getCode(),
                        'skip_expired' => true,
                    ],
                ]);
                $userTransaction = $transactions[0] ?? null;
            }
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
        }

        $balance = null;
        if ($this->getUser()) {
            try {
                $billingUser = $this->billingClient->getCurrentUser($this->getUser()->getApiToken());
                $balance = $billingUser['balance'] ?? null;
            } catch (BillingUnavailableException) {
                $this->addFlash('error', 'Сервис временно недоступен');
            }
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'billingCourse' => $billingCourse,
            'userTransaction' => $userTransaction,
            'balance' => $balance,
        ]);
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function pay(Course $course): Response
    {
        try {
            $result = $this->billingClient->payCourse($course->getCode(), $this->getUser()->getApiToken());

            if (!empty($result['success'])) {
                $this->addFlash('success', 'Курс успешно оплачен');
            } else {
                $this->addFlash('error', $result['message'] ?? 'Ошибка оплаты');
            }
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
        }

        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
