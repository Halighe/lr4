<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Если пользователь уже авторизован - редирект в зависимости от роли
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Перенаправление в зависимости от роли пользователя
     */
    private function redirectBasedOnRole(): Response
    {
        $user = $this->getUser();

        if (in_array('ROLE_MASTER', $user->getRoles(), true)) {
            return $this->redirectToRoute('master_dashboard');
        }

        if (in_array('ROLE_MODERATOR', $user->getRoles(), true)) {
            return $this->redirectToRoute('moderator_statistics_index');
        }

        if (in_array('ROLE_EMPLOYEE', $user->getRoles(), true)) {
            return $this->redirectToRoute('admin');
        }

        if (in_array('ROLE_STUDENT', $user->getRoles(), true)) {
            return $this->redirectToRoute('student_payments_index');
        }

        return $this->redirectToRoute('/');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
