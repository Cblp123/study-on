<?php

namespace App\Controller;

use App\Service\BillingClient;
use App\Exception\BillingUnavailableException;
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
        $user = $this->getUser();
        $balance = null;

        try {
            $balance = $billingClient->getCurrentUser($user->getApiToken())['balance'];
        } catch (BillingUnavailableException $e) {
            $this->addFlash('error', 'Сервис сейчас не доступен.');
        }

        $role = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) ? "Администратор" : "Пользователь";
        return $this->render('profile/index.html.twig', [
            'email'   => $user->getEmail(),
            'role'    => $role,
            'balance' => $balance,
        ]);
    }
}
