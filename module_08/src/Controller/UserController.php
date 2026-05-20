<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    // Renders the login page.
    // If the user is already authenticated, redirects to the home page.
    // Authentication itself is handled by Symfony's json_login firewall.
    #[Route('/login', name: 'app_login')]
    public function loginAction(): Response
    {
        // Redirect to home if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render('user/login.html.twig');
    }
}