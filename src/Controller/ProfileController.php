<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(BillingClient $billingClient): Response
    {
        $billingUser = null;

        try {
            $billingUser = $billingClient->getCurrentUser($this->getUser()->getApiToken());
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
        }

        return $this->render('profile/index.html.twig', [
            'email' => $this->getUser()->getEmail(),
            'role' =>
                in_array('ROLE_SUPER_ADMIN', $billingUser['roles'] ?? [], true) ? 'Администратор' : 'Пользователь',
            'balance' => $billingUser['balance'] ?? null,
        ]);
    }

    #[Route('/profile/transactions', name: 'app_profile_transactions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function transactions(BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        $transactions = [];

        try {
            $rawTransactions = $billingClient->getTransactions($this->getUser()->getApiToken());

            for ($i = 0, $iMax = count($rawTransactions); $i < $iMax; $i++) {
                if (!empty($rawTransactions[$i]['course_code'])) {
                    $course = $courseRepository->findOneBy(['code' => $rawTransactions[$i]['course_code']]);
                    $rawTransactions[$i]['course_id'] = $course?->getId();
                }
            }

            $transactions = $rawTransactions;
        } catch (BillingUnavailableException) {
            $this->addFlash('error', 'Сервис временно недоступен');
        }

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}
