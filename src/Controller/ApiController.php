<?php

namespace App\Controller;

use App\Service\DatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    
    #[Route('/auth-check', name: 'api_auth_check', methods: ['GET'])]
    #[Route('/auth_check.php', name: 'api_auth_check_php', methods: ['GET'])]
    public function authCheck(): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        return new JsonResponse([
            'success'  => true,
            'loggedIn' => !empty($user),
            'user'     => $user,
        ]);
    }

    
    #[Route('/place-order', name: 'api_place_order', methods: ['POST'])]
    #[Route('/place_order.php', name: 'api_place_order_php', methods: ['POST'])]
    public function placeOrder(Request $request, DatabaseService $db): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized. Please sign in to place an order.'], 401);
        }

        $payload = json_decode($request->getContent(), true) ?? $request->request->all();
        if (empty($payload)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid order data.'], 400);
        }

        $customerName  = trim($payload['customer_name'] ?? '');
        $cartItems     = $payload['items'] ?? [];

        if (empty($customerName) || empty($cartItems)) {
            return new JsonResponse(['success' => false, 'message' => 'Customer name and order items are required.'], 400);
        }

        $orderData = [
            'user_id'          => $_SESSION['user']['id'] ?? null,
            'customer_name'    => $customerName,
            'customer_phone'   => trim($payload['customer_phone'] ?? ($_SESSION['user']['phone'] ?? '')),
            'order_type'       => $payload['order_type'] ?? 'dinein',
            'table_number'     => $payload['table_number'] ?? null,
            'delivery_address' => $payload['delivery_address'] ?? null,
            'payment_method'   => $payload['payment_method'] ?? 'Cash',
            'subtotal'         => (float)($payload['subtotal'] ?? 0),
            'delivery_fee'     => (float)($payload['delivery_fee'] ?? 0),
            'total_amount'     => (float)($payload['total_amount'] ?? 0),
            'special_notes'    => $payload['special_notes'] ?? null,
        ];

        
        $cardNumber = trim($payload['card_number'] ?? '');
        if (!empty($cardNumber)) {
            $masked = '•••• ' . substr($cardNumber, -4);
            $orderData['payment_method'] .= " ({$masked})";
        }

        $result = $db->placeOrder($orderData, $cartItems);
        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    
    #[Route('/save-reservation', name: 'api_save_reservation', methods: ['POST'])]
    #[Route('/save_reservation.php', name: 'api_save_reservation_php', methods: ['POST'])]
    public function saveReservation(Request $request, DatabaseService $db): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $payload = json_decode($request->getContent(), true) ?? $request->request->all();

        $name  = trim($payload['name'] ?? '');
        $phone = trim($payload['phone'] ?? '');
        $date  = trim($payload['reservation_date'] ?? '');
        $time  = trim($payload['reservation_time'] ?? '');
        $guests = trim($payload['guests'] ?? '');

        if (empty($name) || empty($phone) || empty($date) || empty($time)) {
            return new JsonResponse(['success' => false, 'message' => 'Please fill in all required reservation fields.'], 400);
        }

        $resData = [
            'user_id'          => $_SESSION['user']['id'] ?? null,
            'name'             => $name,
            'phone'            => $phone,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'guests'           => $guests,
            'seating'          => $payload['seating'] ?? 'Indoor Main Dining',
            'special_notes'    => $payload['special_notes'] ?? null,
        ];

        $result = $db->createReservation($resData);
        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    
    #[Route('/save-contact', name: 'api_save_contact', methods: ['POST'])]
    #[Route('/save_contact.php', name: 'api_save_contact_php', methods: ['POST'])]
    public function saveContact(Request $request, DatabaseService $db): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $payload = json_decode($request->getContent(), true) ?? $request->request->all();

        $name    = trim($payload['name'] ?? '');
        $email   = trim($payload['email'] ?? '');
        $message = trim($payload['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            return new JsonResponse(['success' => false, 'message' => 'Please complete all required fields.'], 400);
        }

        $result = $db->createContactMessage([
            'user_id' => $_SESSION['user']['id'] ?? null,
            'name'    => $name,
            'email'   => $email,
            'subject' => $payload['subject'] ?? 'General Inquiry',
            'message' => $message,
        ]);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    
    #[Route('/admin/stats', name: 'api_admin_stats', methods: ['GET'])]
    #[Route('/admin_stats.php', name: 'api_admin_stats_php', methods: ['GET'])]
    public function adminStats(DatabaseService $db): JsonResponse
    {
        $stats = $db->getDashboardStats();
        return new JsonResponse([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    
    #[Route('/admin/inventory', name: 'api_admin_inventory', methods: ['GET'])]
    #[Route('/admin_inventory.php', name: 'api_admin_inventory_php', methods: ['GET'])]
    public function adminInventory(DatabaseService $db): JsonResponse
    {
        $inventory = $db->getInventory();
        return new JsonResponse([
            'success' => true,
            'data'    => $inventory,
        ]);
    }

    
    #[Route('/update-stock', name: 'api_update_stock', methods: ['POST'])]
    #[Route('/update_stock.php', name: 'api_update_stock_php', methods: ['POST'])]
    public function updateStock(Request $request, DatabaseService $db): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return new JsonResponse(['success' => false, 'message' => 'Access denied. Admins only.'], 403);
        }

        $payload = json_decode($request->getContent(), true) ?? $request->request->all();
        $id     = (int)($payload['id'] ?? 0);
        $stock  = (int)($payload['stock'] ?? 0);
        $thresh = (int)($payload['threshold'] ?? 5);

        if ($id <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid menu item ID.'], 400);
        }

        $ok = $db->updateStock($id, $stock, $thresh);
        return new JsonResponse([
            'success' => $ok,
            'message' => $ok ? "Stock updated to {$stock}." : "Failed to update stock.",
            'stock'   => $stock,
            'threshold' => $thresh,
        ]);
    }
}
