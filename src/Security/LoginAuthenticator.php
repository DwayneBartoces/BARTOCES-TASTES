<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginAuthenticator
{
    private UserRepository $userRepository;
    private UrlGeneratorInterface $urlGenerator;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(UserRepository $userRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->userRepository = $userRepository;
        $this->urlGenerator   = $urlGenerator;
    }

    
    public function authenticate(string $identifier, string $password): ?User
    {
        $user = $this->userRepository->findByUsernameOrEmail($identifier);

        if (!$user) {
            return null;
        }

        if (!$user->verifyPassword($password)) {
            return null;
        }

        return $user;
    }

    
    public function loginUser(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = $user->toSessionArray();
    }

    
    public function onAuthenticationSuccess(User $user): string
    {
        $this->loginUser($user);

        if ($user->isAdmin()) {
            return 'admin_symfony.php';
        }

        return '/';
    }
}

