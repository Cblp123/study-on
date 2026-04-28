<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\RegistrationFormType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_profile');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new
        \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $formAuthenticator,
        BillingClient $billingClient
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $response = $billingClient->register($data->email, $data->password);
            } catch (BillingUnavailableException) {
                $this->addFlash('error', 'Сервис временно недоступен. Попробуйте зарегистрироваться позднее.');
                return $this->render('security/register.html.twig', ['form' => $form]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->render('security/register.html.twig', ['form' => $form]);
            }

            if (isset($response['code'])) {
                $this->addFlash('error', $response['message']);
                return $this->render('security/register.html.twig', ['form' => $form]);
            }
            $user = new User();
            $user->setEmail($data->email);
            $user->setApiToken($response['token']);
            $user->setRefreshToken($response['refresh_token']);
            $user->setRoles($response['roles'] ?? ['ROLE_USER']);

            return $authenticator->authenticateUser($user, $formAuthenticator, $request);
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }
}
