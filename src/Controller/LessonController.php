<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Exception\BillingUnavailableException;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lessons')]
final class LessonController extends AbstractController
{
    public function __construct(private BillingClient $billingClient)
    {
    }

    #[Route('/new', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository
    ): Response {
        $lesson = new Lesson();

        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        $courseId = $request->query->get('course_id');
        $course = $courseRepository->find($courseId);

        if (!$course) {
            throw $this->createNotFoundException('Курс не найден');
        }

        $lesson->setCourse($course);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Lesson $lesson): Response
    {
        $course = $lesson->getCourse();

        try {
            $billingCourse = $this->billingClient->getCourse($course->getCode());

            if (($billingCourse['type'] ?? 'free') !== 'free') {
                $transactions = $this->billingClient->getTransactions(
                    $this->getUser()->getApiToken(),
                    [
                        'filter' => [
                            'type' => 'payment',
                            'course_code' => $course->getCode(),
                            'skip_expired' => true,
                        ]
                    ]
                );

                if (empty($transactions)) {
                    throw new AccessDeniedException('Курс не оплачен');
                }
            }
        } catch (AccessDeniedException $e) {
            throw $e;
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $courseId = $lesson->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_course_show',
            ['id' => $courseId],
            Response::HTTP_SEE_OTHER
        );
    }
}
