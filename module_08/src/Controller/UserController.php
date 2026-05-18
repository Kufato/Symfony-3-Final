<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    // Renders the login form template.
    // Authentication itself is handled by Symfony's json_login firewall —
    // this action only serves the HTML for the login form.
    // It is also called via render(controller(...)) from the post/index.html.twig
    // template when the user is not authenticated.
    #[Route('/login', name: 'app_login')]
    public function loginAction(): Response
    {
        return $this->render('user/login.html.twig');
    }
}