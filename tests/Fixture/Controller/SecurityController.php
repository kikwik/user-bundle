<?php

namespace Kikwik\UserBundle\Tests\Fixture\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'test_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'test_logout')]
    public function logout(): void
    {
        throw new \LogicException('This route is intercepted by the firewall logout listener.');
    }

    #[Route('/', name: 'test_home')]
    public function home(): Response
    {
        return new Response('Home');
    }

    #[Route('/profile', name: 'test_profile')]
    public function profile(): Response
    {
        return new Response('Profile');
    }
}