<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    #[Route('/login.php', name: 'app_login_php', methods: ['GET', 'POST'])]
    public function login(Request $request, DatabaseService $db): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRepo = new UserRepository($db);

        
        if (!empty($_SESSION['user'])) {
            $userRole = $_SESSION['user']['role'] ?? 'user';
            if ($userRole === 'admin') {
                return $this->redirectToRoute('admin_dashboard');
            }
            if ($userRole === 'staff') {
                return $this->redirectToRoute('staff_dashboard');
            }
            return $this->redirectToRoute('app_home');
        }

        $error = '';
        $success = '';

        if ($request->isMethod('POST')) {
            
            $csrfToken = $request->request->get('_csrf_token');
            if (!DatabaseService::validateCsrfToken($csrfToken)) {
                $error = 'Invalid security session. Please try again.';
            } else {
                $action = $request->request->get('action', 'login');

                if ($action === 'login') {
                    $identifier = trim((string)$request->request->get('identifier', ''));
                    $password   = (string)$request->request->get('password', '');

                    if (empty($identifier) || empty($password)) {
                        $error = 'Please fill in all fields.';
                    } else {
                        $user = $userRepo->findByUsernameOrEmail($identifier);

                        if (!$user || !$user->verifyPassword($password)) {
                            $error = 'Invalid username/email or password.';
                        } elseif ($user->isDisabled() || $user->isArchived()) {
                            
                            $error = 'Your account has been ' . $user->getStatus() . '. Please contact the restaurant administrator.';
                        } else {
                            $_SESSION['user'] = $user->toSessionArray();

                            
                            if ($user->isAdmin()) {
                                return $this->redirectToRoute('admin_dashboard');
                            }
                            if ($user->isStaff()) {
                                return $this->redirectToRoute('staff_dashboard');
                            }
                            return $this->redirectToRoute('app_home');
                        }
                    }
                } elseif ($action === 'register') {
                    $name            = trim((string)$request->request->get('name', ''));
                    $username        = trim((string)$request->request->get('username', ''));
                    $email           = trim((string)$request->request->get('email', ''));
                    $phone           = trim((string)$request->request->get('phone', ''));
                    $password        = (string)$request->request->get('password', '');
                    $confirmPassword = (string)$request->request->get('confirm_password', '');

                    if (empty($name) || empty($username) || empty($email) || empty($password)) {
                        $error = 'Please fill in all required fields.';
                    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Please enter a valid email address.';
                    } elseif (strlen($password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                    } elseif (!empty($confirmPassword) && $password !== $confirmPassword) {
                        $error = 'Passwords do not match.';
                    } elseif ($userRepo->findByUsername($username)) {
                        $error = 'That username is already taken. Please choose another.';
                    } elseif ($userRepo->findByEmail($email)) {
                        $error = 'An account with that email already exists.';
                    } else {
                        $newUser = (new User())
                            ->setName($name)
                            ->setUsername($username)
                            ->setEmail($email)
                            ->setPhone($phone)
                            ->setPlainPassword($password)
                            ->setRole('user')
                            ->setStatus('active');

                        if ($userRepo->save($newUser)) {
                            $_SESSION['user'] = $newUser->toSessionArray();
                            return $this->redirectToRoute('app_home');
                        } else {
                            $error = 'Failed to create your account. Please try again.';
                        }
                    }
                }
            }
        }

        return $this->render('auth/login.html.twig', [
            'error'      => $error,
            'success'    => $success,
            'dbSource'   => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, DatabaseService $db): Response
    {
        return $this->login($request, $db);
    }

    #[Route('/logout', name: 'app_logout')]
    #[Route('/logout.php', name: 'app_logout_php')]
    public function logout(): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();

        return $this->redirectToRoute('app_home');
    }
}
