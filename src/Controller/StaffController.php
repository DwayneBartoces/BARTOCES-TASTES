<?php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/staff')]
class StaffController extends AbstractController
{
    
    private function checkStaff(): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            $this->addFlash('error', 'Please log in to access the Staff portal.');
            return $this->redirectToRoute('app_login');
        }

        $role = $_SESSION['user']['role'] ?? 'user';
        if ($role !== 'staff' && $role !== 'admin') {
            return new Response(
                $this->renderView('errors/403.html.twig', [
                    'message' => 'Access Denied. You do not have permission to access the Staff portal.',
                    'user'    => $_SESSION['user'],
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        return null;
    }

    private function getStaffId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    
    
    

    #[Route('', name: 'staff_dashboard')]
    #[Route('/dashboard', name: 'staff_dashboard_alt')]
    public function dashboard(DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        $staffId = $this->getStaffId();
        $stats = $db->getDashboardStats($staffId);

        return $this->render('staff/dashboard.html.twig', [
            'stats'      => $stats,
            'user'       => $_SESSION['user'],
            'active_tab' => 'dashboard',
            'dbSource'   => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    
    
    

    #[Route('/inventory', name: 'staff_inventory')]
    public function inventory(DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        $staffId = $this->getStaffId();
        $inventory = $db->getInventory();

        return $this->render('staff/inventory.html.twig', [
            'inventory'  => $inventory,
            'currentUserId' => $staffId,
            'user'       => $_SESSION['user'],
            'active_tab' => 'inventory',
            'dbSource'   => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }

    
    #[Route('/inventory/add', name: 'staff_inventory_add', methods: ['POST'])]
    public function addDish(Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkStaff()) return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('staff_inventory');
        }

        $name     = trim((string)$request->request->get('name', ''));
        $price    = (float)$request->request->get('price', 0);
        $category = (string)$request->request->get('category', 'classics');
        $desc     = trim((string)$request->request->get('description', ''));
        $stock    = max(0, (int)$request->request->get('stock', 0));
        $thresh   = max(0, (int)$request->request->get('threshold', 5));
        $badge    = trim((string)$request->request->get('badge', ''));

        if (empty($name) || $price <= 0) {
            $this->addFlash('error', 'Please provide a valid dish name and price.');
            return $this->redirectToRoute('staff_inventory');
        }

        $staffId = $this->getStaffId();
        $ok = $db->addMenuItem([
            'name'                => $name,
            'price'               => $price,
            'category'            => $category,
            'description'         => $desc,
            'stock'               => $stock,
            'low_stock_threshold' => $thresh,
            'badge'               => !empty($badge) ? $badge : null,
            'image'               => 'images/chicken-inasal.jpg'
        ], $staffId);

        if ($ok) {
            $this->addFlash('success', "Record '{$name}' created successfully! You are the owner of this dish.");
        } else {
            $this->addFlash('error', 'Failed to save menu record. A dish with that name may already exist.');
        }

        return $this->redirectToRoute('staff_inventory');
    }

    
    #[Route('/inventory/edit/{id}', name: 'staff_inventory_edit', methods: ['POST'])]
    public function editDish(int $id, Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('staff_inventory');
        }

        $item = $db->getMenuItemById($id);
        if (!$item) {
            $this->addFlash('error', 'Record not found.');
            return $this->redirectToRoute('staff_inventory');
        }

        $staffId = $this->getStaffId();
        $userRole = $_SESSION['user']['role'] ?? 'user';

        
        if ($userRole !== 'admin' && (int)($item['created_by'] ?? 0) !== $staffId) {
            $this->addFlash('error', 'Access Denied: You cannot edit records created by the Administrator or other staff members.');
            return new Response(
                $this->renderView('errors/403.html.twig', [
                    'message' => 'Access Denied: You do not have permission to edit this record. Staff members may only edit records they created.',
                    'user'    => $_SESSION['user'],
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $name     = trim((string)$request->request->get('name', ''));
        $price    = (float)$request->request->get('price', 0);
        $category = (string)$request->request->get('category', 'classics');
        $desc     = trim((string)$request->request->get('description', ''));
        $stock    = max(0, (int)$request->request->get('stock', 0));
        $thresh   = max(0, (int)$request->request->get('threshold', 5));
        $badge    = trim((string)$request->request->get('badge', ''));

        if (!empty($name) && $price > 0) {
            $db->updateMenuItem($id, [
                'name'                => $name,
                'price'               => $price,
                'category'            => $category,
                'description'         => $desc,
                'stock'               => $stock,
                'low_stock_threshold' => $thresh,
                'badge'               => !empty($badge) ? $badge : null,
            ], $userRole === 'admin' ? null : $staffId);

            $this->addFlash('success', "Your dish '{$name}' was updated successfully.");
        }

        return $this->redirectToRoute('staff_inventory');
    }

    
    #[Route('/inventory/delete/{id}', name: 'staff_inventory_delete', methods: ['POST'])]
    public function deleteDish(int $id, Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('staff_inventory');
        }

        $item = $db->getMenuItemById($id);
        if (!$item) {
            $this->addFlash('error', 'Record not found.');
            return $this->redirectToRoute('staff_inventory');
        }

        $staffId = $this->getStaffId();
        $userRole = $_SESSION['user']['role'] ?? 'user';

        
        if ($userRole !== 'admin' && (int)($item['created_by'] ?? 0) !== $staffId) {
            return new Response(
                $this->renderView('errors/403.html.twig', [
                    'message' => 'Access Denied: You do not have permission to delete this record. Staff members may only delete records they created.',
                    'user'    => $_SESSION['user'],
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $ok = $db->deleteMenuItem($id, $userRole === 'admin' ? null : $staffId);
        if ($ok) {
            $this->addFlash('success', "Your dish '{$item['name']}' has been deleted.");
        } else {
            $this->addFlash('error', 'Failed to delete dish.');
        }

        return $this->redirectToRoute('staff_inventory');
    }

    
    
    

    #[Route('/orders', name: 'staff_orders')]
    public function orders(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        $status = $request->query->get('status', 'all');
        $orders = $db->getOrders($status);

        return $this->render('staff/orders.html.twig', [
            'orders'        => $orders,
            'currentStatus' => $status,
            'user'          => $_SESSION['user'],
            'active_tab'    => 'orders',
            'dbSource'      => $db->getDbSource(),
            'csrf_token'    => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/order/status/{id}', name: 'staff_order_status', methods: ['POST'])]
    public function updateOrderStatus(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkStaff()) return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('staff_orders');
        }

        $status = (string)$request->request->get('status', 'pending');
        if ($id > 0) {
            $db->updateOrderStatus($id, $status);
            $this->addFlash('success', "Order #{$id} status updated to " . ucfirst($status) . ".");
        }

        return $this->redirectToRoute('staff_orders');
    }

    
    
    

    #[Route('/reservations', name: 'staff_reservations')]
    public function reservations(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        $status = $request->query->get('status', 'all');
        $reservations = $db->getReservations($status);

        return $this->render('staff/reservations.html.twig', [
            'reservations'  => $reservations,
            'currentStatus' => $status,
            'user'          => $_SESSION['user'],
            'active_tab'    => 'reservations',
            'dbSource'      => $db->getDbSource(),
            'csrf_token'    => DatabaseService::getCsrfToken(),
        ]);
    }

    #[Route('/reservation/status/{id}', name: 'staff_reservation_status', methods: ['POST'])]
    public function updateReservationStatus(int $id, Request $request, DatabaseService $db): RedirectResponse
    {
        if ($res = $this->checkStaff()) return $res;

        if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Security token expired. Please try again.');
            return $this->redirectToRoute('staff_reservations');
        }

        $status = (string)$request->request->get('status', 'confirmed');
        if ($id > 0) {
            $db->updateReservationStatus($id, $status);
            $this->addFlash('success', "Reservation #{$id} status updated to " . ucfirst($status) . ".");
        }

        return $this->redirectToRoute('staff_reservations');
    }

    
    
    

    #[Route('/profile', name: 'staff_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, DatabaseService $db): Response
    {
        if ($res = $this->checkStaff()) return $res;

        $staffId = $this->getStaffId();
        $userData = $db->getUserById($staffId);

        if ($request->isMethod('POST')) {
            if (!DatabaseService::validateCsrfToken($request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Security token expired. Please try again.');
                return $this->redirectToRoute('staff_profile');
            }

            $action = $request->request->get('action', '');

            if ($action === 'change_password') {
                $currentPass = (string)$request->request->get('current_password', '');
                $newPass     = (string)$request->request->get('new_password', '');
                $confirmPass = (string)$request->request->get('confirm_password', '');

                if (empty($currentPass) || empty($newPass)) {
                    $this->addFlash('error', 'Please fill in all password fields.');
                } elseif ($newPass !== $confirmPass) {
                    $this->addFlash('error', 'New passwords do not match.');
                } else {
                    $result = $db->changeUserPassword($staffId, $currentPass, $newPass);
                    if ($result['success']) {
                        $this->addFlash('success', $result['message']);
                    } else {
                        $this->addFlash('error', $result['message']);
                    }
                }
            } elseif ($action === 'update_profile') {
                $name  = trim((string)$request->request->get('name', ''));
                $email = trim((string)$request->request->get('email', ''));
                $phone = trim((string)$request->request->get('phone', ''));

                if (!empty($name) && !empty($email)) {
                    $res = $db->updateUser($staffId, [
                        'name'  => $name,
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

            return $this->redirectToRoute('staff_profile');
        }

        return $this->render('staff/profile.html.twig', [
            'userData'   => $userData,
            'user'       => $_SESSION['user'],
            'active_tab' => 'profile',
            'dbSource'   => $db->getDbSource(),
            'csrf_token' => DatabaseService::getCsrfToken(),
        ]);
    }
}

