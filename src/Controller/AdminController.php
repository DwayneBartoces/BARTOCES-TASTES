<?php

namespace App\Controller;

use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{

    private function checkAdmin(): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            $this->addFlash('error', 'Please log in with Administrator credentials.');
            return $this->redirectToRoute('app_login');
        }

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            return new Response(
                $this->renderView('errors/403.html.twig', [
                    'message' => 'Access Denied. You do not have permission to access the Administrator portal. This section is restricted to Executive Management.',
                    'user' => $_SESSION['user'],
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        return null;
    }





    #[Route('', name: 'admin_dashboard')]
    #[Route('/dashboard', name: 'admin_dashboard_alt')]
    public function dashboard(DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $stats = $db->getDashboardStats();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'user' => $_SESSION['user'],
            'active_tab' => 'dashboard',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }





    #[Route('/inventory', name: 'admin_inventory')]
    public function inventory(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;


        $q = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $status = trim((string) $request->query->get('status', ''));

        $inventory = ($q !== '' || $category !== '' || $status !== '')
            ? $db->searchInventory($q, $category, $status)
            : $db->getInventory();

        return $this->render('admin/inventory.html.twig', [
            'inventory' => $inventory,
            'user' => $_SESSION['user'],
            'active_tab' => 'inventory',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/inventory/adjust/{id}', name: 'admin_inventory_adjust', methods: ['POST'])]
    public function adjustStock(int $id, Request $request, DatabaseService $db): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? $request->request->all();
        $delta = (int) ($payload['delta'] ?? 0);

        if ($id <= 0 || $delta === 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid adjustment parameters'], 400);
        }

        $result = $db->adjustStock($id, $delta);
        return new JsonResponse($result);
    }

    #[Route('/inventory/update/{id}', name: 'admin_inventory_update', methods: ['POST'])]
    public function updateStock(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_inventory');
        }

        $stock = max(0, (int) $request->request->get('stock', 0));
        $thresh = max(0, (int) $request->request->get('threshold', 5));

        if ($id > 0) {
            $db->updateStock($id, $stock, $thresh);
            $this->addFlash('success', "Stock updated for dish #{$id}.");
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/inventory/add', name: 'admin_inventory_add', methods: ['POST'])]
    public function addDish(Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_inventory');
        }

        $name = trim((string) $request->request->get('name', ''));
        $price = (float) $request->request->get('price', 0);
        $category = (string) $request->request->get('category', 'classics');
        $desc = trim((string) $request->request->get('description', ''));
        $stock = max(0, (int) $request->request->get('stock', 0));
        $thresh = max(0, (int) $request->request->get('threshold', 5));
        $badge = trim((string) $request->request->get('badge', ''));

        if (!empty($name) && $price > 0) {
            $adminId = (int) ($_SESSION['user']['id'] ?? 1);
            $db->addMenuItem([
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'description' => $desc,
                'stock' => $stock,
                'low_stock_threshold' => $thresh,
                'badge' => !empty($badge) ? $badge : null,
                'image' => 'images/chicken-inasal.jpg'
            ], $adminId);
            $this->addFlash('success', "Dish '{$name}' added to menu successfully!");
        } else {
            $this->addFlash('error', 'Please provide a valid dish name and price.');
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/inventory/edit/{id}', name: 'admin_inventory_edit', methods: ['POST'])]
    public function editDish(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_inventory');
        }

        $name = trim((string) $request->request->get('name', ''));
        $price = (float) $request->request->get('price', 0);
        $category = (string) $request->request->get('category', 'classics');
        $desc = trim((string) $request->request->get('description', ''));
        $stock = max(0, (int) $request->request->get('stock', 0));
        $thresh = max(0, (int) $request->request->get('threshold', 5));
        $badge = trim((string) $request->request->get('badge', ''));

        if ($id > 0 && !empty($name)) {
            $db->updateMenuItem($id, [
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'description' => $desc,
                'stock' => $stock,
                'low_stock_threshold' => $thresh,
                'badge' => !empty($badge) ? $badge : null,
            ]);
            $this->addFlash('success', "Dish '{$name}' updated successfully.");
        }

        return $this->redirectToRoute('admin_inventory');
    }

    #[Route('/inventory/delete/{id}', name: 'admin_inventory_delete', methods: ['POST'])]
    public function deleteDish(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_inventory');
        }

        if ($id > 0) {
            $db->deleteMenuItem($id);
            $this->addFlash('success', "Dish deleted from menu.");
        }

        return $this->redirectToRoute('admin_inventory');
    }





    #[Route('/orders', name: 'admin_orders')]
    public function orders(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $status = $request->query->get('status', 'all');
        $orders = $db->getOrders($status);

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'currentStatus' => $status,
            'user' => $_SESSION['user'],
            'active_tab' => 'orders',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/order/status/{id}', name: 'admin_order_status', methods: ['POST'])]
    public function updateOrderStatus(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_orders');
        }

        $status = (string) $request->request->get('status', 'pending');
        if ($id > 0) {
            $db->updateOrderStatus($id, $status);
            $this->addFlash('success', "Order #{$id} status updated to " . ucfirst($status) . ".");
        }
        return $this->redirectToRoute('admin_orders');
    }

    #[Route('/order/delete/{id}', name: 'admin_order_delete', methods: ['POST'])]
    public function deleteOrder(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_orders');
        }

        if ($id > 0) {
            $db->deleteOrder($id);
            $this->addFlash('success', "Order #{$id} has been removed.");
        }
        return $this->redirectToRoute('admin_orders');
    }





    #[Route('/reservations', name: 'admin_reservations')]
    public function reservations(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $status = $request->query->get('status', 'all');
        $reservations = $db->getReservations($status);

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'currentStatus' => $status,
            'user' => $_SESSION['user'],
            'active_tab' => 'reservations',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/reservation/status/{id}', name: 'admin_reservation_status', methods: ['POST'])]
    public function updateReservationStatus(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_reservations');
        }

        $status = (string) $request->request->get('status', 'confirmed');
        if ($id > 0) {
            $db->updateReservationStatus($id, $status);
            $this->addFlash('success', "Reservation #{$id} status updated to " . ucfirst($status) . ".");
        }
        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservation/delete/{id}', name: 'admin_reservation_delete', methods: ['POST'])]
    public function deleteReservation(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_reservations');
        }

        if ($id > 0) {
            $db->deleteReservation($id);
            $this->addFlash('success', "Reservation #{$id} deleted.");
        }
        return $this->redirectToRoute('admin_reservations');
    }





    #[Route('/messages', name: 'admin_messages')]
    public function messages(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $filter = $request->query->get('filter', 'all');
        $unreadOnly = ($filter === 'unread');
        $messages = $db->getMessages($unreadOnly);

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
            'filter' => $filter,
            'user' => $_SESSION['user'],
            'active_tab' => 'messages',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/message/read/{id}', name: 'admin_message_read', methods: ['POST'])]
    public function markMessageRead(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_messages');
        }

        if ($id > 0) {
            $db->markMessageRead($id);
            $this->addFlash('success', "Message #{$id} marked as read.");
        }
        return $this->redirectToRoute('admin_messages');
    }

    #[Route('/message/delete/{id}', name: 'admin_message_delete', methods: ['POST'])]
    public function deleteMessage(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_messages');
        }

        if ($id > 0) {
            $db->deleteMessage($id);
            $this->addFlash('success', "Message #{$id} removed.");
        }
        return $this->redirectToRoute('admin_messages');
    }






    #[Route('/users', name: 'admin_users')]
    public function users(DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $users = $db->getUsers();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'user' => $_SESSION['user'],
            'active_tab' => 'users',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }





    #[Route('/search/inventory', name: 'admin_search_inventory', methods: ['GET'])]
    public function searchInventory(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $q = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $status = trim((string) $request->query->get('status', ''));

        $inventory = $db->searchInventory($q, $category, $status);

        return $this->render('admin/inventory.html.twig', [
            'inventory' => $inventory,
            'user' => $_SESSION['user'],
            'active_tab' => 'inventory',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
            'searchQuery' => $q,
        ]);
    }

    #[Route('/search/users', name: 'admin_search_users', methods: ['GET'])]
    public function searchUsers(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $q = trim((string) $request->query->get('q', ''));
        $role = trim((string) $request->query->get('role', ''));
        $status = trim((string) $request->query->get('status', ''));

        $users = $db->searchUsers($q, $role, $status);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'user' => $_SESSION['user'],
            'active_tab' => 'users',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
            'searchQuery' => $q,
        ]);
    }

    #[Route('/user/add', name: 'admin_user_add', methods: ['POST'])]
    public function addUser(Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_users');
        }

        $name = trim((string) $request->request->get('name', ''));
        $username = trim((string) $request->request->get('username', ''));
        $email = trim((string) $request->request->get('email', ''));
        $phone = trim((string) $request->request->get('phone', ''));
        $password = (string) $request->request->get('password', '');
        $role = (string) $request->request->get('role', 'user');
        $status = (string) $request->request->get('status', 'active');

        if (!empty($name) && !empty($username) && !empty($email) && !empty($password)) {
            $result = $db->createUser([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'role' => $role,
                'status' => $status,
            ]);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('error', $result['message']);
            }
        } else {
            $this->addFlash('error', 'Please fill in all required fields.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/user/edit/{id}', name: 'admin_user_edit', methods: ['POST'])]
    public function editUser(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_users');
        }

        $name = trim((string) $request->request->get('name', ''));
        $username = trim((string) $request->request->get('username', ''));
        $email = trim((string) $request->request->get('email', ''));
        $phone = trim((string) $request->request->get('phone', ''));
        $role = (string) $request->request->get('role', 'user');
        $status = (string) $request->request->get('status', 'active');
        $password = (string) $request->request->get('password', '');

        if ($id > 0 && !empty($name) && !empty($username) && !empty($email)) {
            $data = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'status' => $status,
            ];
            if (!empty($password)) {
                $data['password'] = $password;
            }

            $result = $db->updateUser($id, $data);
            if ($result['success']) {
                $this->addFlash('success', "Account for '{$name}' was successfully updated.");
            } else {
                $this->addFlash('error', $result['message']);
            }
        } else {
            $this->addFlash('error', 'Required user information is missing.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/user/status/{id}', name: 'admin_user_status', methods: ['POST'])]
    public function setUserStatus(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_users');
        }

        $status = (string) $request->request->get('status', 'active');
        if ($id > 0) {
            $db->setUserStatus($id, $status);
            $this->addFlash('success', "User #{$id} status changed to " . strtoupper($status) . ".");
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/user/role/{id}', name: 'admin_user_role', methods: ['POST'])]
    public function updateUserRole(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_users');
        }

        $role = (string) $request->request->get('role', 'user');
        if ($id > 0) {
            $db->updateUserRole($id, $role);
            $this->addFlash('success', "User #{$id} role updated to " . strtoupper($role) . ".");
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/user/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkAdmin())
            return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('admin_users');
        }

        if ($id > 0) {

            if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
                $this->addFlash('error', 'You cannot delete your own active administrator account.');
                return $this->redirectToRoute('admin_users');
            }

            $db->deleteUser($id);
            $this->addFlash('success', "User #{$id} has been permanently deleted.");
        }
        return $this->redirectToRoute('admin_users');
    }





    #[Route('/profile', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkAdmin())
            return $res;

        $adminId = (int) ($_SESSION['user']['id'] ?? 1);
        $userData = $db->getUserById($adminId);

        if ($request->isMethod('POST')) {
            if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Security token expired. Please try again.');
                return $this->redirectToRoute('admin_profile');
            }

            $action = $request->request->get('action', '');

            if ($action === 'change_password') {
                $currentPass = (string) $request->request->get('current_password', '');
                $newPass = (string) $request->request->get('new_password', '');
                $confirmPass = (string) $request->request->get('confirm_password', '');

                if (empty($currentPass) || empty($newPass)) {
                    $this->addFlash('error', 'Please fill in all password fields.');
                } elseif ($newPass !== $confirmPass) {
                    $this->addFlash('error', 'New passwords do not match.');
                } else {
                    $result = $db->changeUserPassword($adminId, $currentPass, $newPass);
                    if ($result['success']) {
                        $this->addFlash('success', $result['message']);
                    } else {
                        $this->addFlash('error', $result['message']);
                    }
                }
            } elseif ($action === 'update_profile') {
                $name = trim((string) $request->request->get('name', ''));
                $email = trim((string) $request->request->get('email', ''));
                $phone = trim((string) $request->request->get('phone', ''));

                if (!empty($name) && !empty($email)) {
                    $res = $db->updateUser($adminId, [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]);
                    if ($res['success']) {
                        $_SESSION['user']['name'] = $name;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['phone'] = $phone;
                        $this->addFlash('success', 'Profile updated successfully.');
                    } else {
                        $this->addFlash('error', $res['message']);
                    }
                }
            }

            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/profile.html.twig', [
            'userData' => $userData,
            'user' => $_SESSION['user'],
            'active_tab' => 'profile',
            'dbSource' => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }
}
