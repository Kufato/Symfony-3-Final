<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    // Symfony calls this method automatically when the login is successful.
    // $request = the incoming HTTP request
    // $token = contains the logged-in user's details
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }
}