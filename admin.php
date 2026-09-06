<?php
// ============================================================
// BARTOCES TASTES - ADMIN DASHBOARD & MANAGEMENT PORTAL
// Strictly adheres to Week 7: PHP Forms, Validation & Database CRUD with PDO
// ============================================================
require_once __DIR__ . '/config.php';

session_start();

// Guard: Only authenticated administrators can enter
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('login.php');
}

$admin = $_SESSION['user'];
$pdo   = db();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ============================================================
// HANDLE ADMIN ACTIONS (POST)
// Using PDO Prepared Statements per Week 7 PDF
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
              || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

    // 1. UPDATE RESERVATION STATUS
    if ($action === 'update_reservation_status') {
        $id     = intval($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'pending');
        $validStatuses = ['pending', 'confirmed', 'cancelled'];

        if ($id > 0 && in_array($status, $validStatuses)) {
            $stmt = $pdo->prepare('UPDATE `reservations` SET `status` = :status WHERE `id` = :id');
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id',     $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Reservation #{$id} status updated to " . ucfirst($status) . ".";
        }
        redirect('admin.php?tab=reservations');
    }

    // 2. DELETE RESERVATION
    elseif ($action === 'delete_reservation') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM `reservations` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Reservation #{$id} deleted successfully.";
        }
        redirect('admin.php?tab=reservations');
    }

    // 3. UPDATE ORDER STATUS
    elseif ($action === 'update_order_status') {
        $id     = intval($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'pending');
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];

        if ($id > 0 && in_array($status, $validStatuses)) {
            $stmt = $pdo->prepare('UPDATE `orders` SET `status` = :status WHERE `id` = :id');
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id',     $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Order #{$id} status updated to " . ucfirst($status) . ".";
        }
        redirect('admin.php?tab=orders');
    }

    // 4. DELETE ORDER
    elseif ($action === 'delete_order') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM `orders` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Order #{$id} removed.";
        }
        redirect('admin.php?tab=orders');
    }

    // 5. UPDATE STOCK & THRESHOLD
    elseif ($action === 'update_stock') {
        $id     = intval($_POST['id'] ?? 0);
        $stock  = max(0, intval($_POST['stock'] ?? 0));
        $thresh = max(0, intval($_POST['threshold'] ?? 5));

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE `menu_items` SET `stock` = :stock, `low_stock_threshold` = :thresh WHERE `id` = :id');
            $stmt->bindValue(':stock',  $stock, PDO::PARAM_INT);
            $stmt->bindValue(':thresh', $thresh, PDO::PARAM_INT);
            $stmt->bindValue(':id',     $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Stock for item #{$id} updated to {$stock} units.";
        }
        redirect('admin.php?tab=inventory');
    }

    // 6. QUICK RESTOCK (+15 units)
    elseif ($action === 'quick_restock') {
        $id  = intval($_POST['id'] ?? 0);
        $qty = intval($_POST['qty'] ?? 15);
        if ($id > 0 && $qty > 0) {
            $stmt = $pdo->prepare('UPDATE `menu_items` SET `stock` = `stock` + :qty WHERE `id` = :id');
            $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
            $stmt->bindValue(':id',  $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Restocked +{$qty} units to item #{$id}.";
        }
        redirect('admin.php?tab=inventory');
    }

    // 7. TOGGLE MENU ITEM ACTIVE/INACTIVE
    elseif ($action === 'toggle_item_active') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE `menu_items` SET `is_active` = 1 - `is_active` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Item #{$id} visibility toggled.";
        }
        redirect('admin.php?tab=inventory');
    }

    // 8. ADD NEW MENU ITEM
    elseif ($action === 'add_menu_item') {
        $name     = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? 'classics');
        $price    = floatval($_POST['price'] ?? 0);
        $stock    = max(0, intval($_POST['stock'] ?? 50));
        $thresh   = max(0, intval($_POST['threshold'] ?? 5));
        $badge    = sanitize($_POST['badge'] ?? '');
        $image    = sanitize($_POST['image'] ?? 'images/chef.png');
        $desc     = sanitize($_POST['description'] ?? '');

        if (!empty($name) && $price > 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `image`, `badge`, `stock`, `low_stock_threshold`, `is_active`)
                 VALUES (:name, :desc, :price, :category, :image, :badge, :stock, :thresh, 1)'
            );
            $stmt->bindValue(':name',     $name);
            $stmt->bindValue(':desc',     $desc);
            $stmt->bindValue(':price',    $price);
            $stmt->bindValue(':category', $category);
            $stmt->bindValue(':image',    $image);
            $stmt->bindValue(':badge',    $badge);
            $stmt->bindValue(':stock',    $stock, PDO::PARAM_INT);
            $stmt->bindValue(':thresh',   $thresh, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "New menu item \"{$name}\" added successfully!";
        } else {
            $_SESSION['flash_error'] = "Please provide a valid dish name and price.";
        }
        redirect('admin.php?tab=inventory');
    }

    // 9. DELETE MENU ITEM
    elseif ($action === 'delete_menu_item') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM `menu_items` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Menu item #{$id} deleted.";
        }
        redirect('admin.php?tab=inventory');
    }

    // 10. TOGGLE MESSAGE READ STATUS
    elseif ($action === 'toggle_message_read') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE `contacts` SET `is_read` = 1 - `is_read` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Message #{$id} status updated.";
        }
        redirect('admin.php?tab=messages');
    }

    // 11. DELETE MESSAGE
    elseif ($action === 'delete_message') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM `contacts` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "Message #{$id} deleted.";
        }
        redirect('admin.php?tab=messages');
    }

    // 12. DELETE USER
    elseif ($action === 'delete_user') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && $id !== intval($admin['id'])) {
            $stmt = $pdo->prepare('DELETE FROM `users` WHERE `id` = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['flash_success'] = "User #{$id} deleted.";
        } else {
            $_SESSION['flash_error'] = "You cannot delete your own admin account.";
        }
        redirect('admin.php?tab=users');
    }
}

// ============================================================
// DATA QUERIES (PDO Read - SELECT per Week 7 PDF slide 14)
// ============================================================

// Counts
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM `orders`')->fetchColumn();
$resCount   = (int)$pdo->query('SELECT COUNT(*) FROM `reservations`')->fetchColumn();
$userCount  = (int)$pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
$msgCount   = (int)$pdo->query('SELECT COUNT(*) FROM `contacts`')->fetchColumn();

// Low stock count
$lowStockCount = (int)$pdo->query('SELECT COUNT(*) FROM `menu_items` WHERE `stock` <= `low_stock_threshold`')->fetchColumn();

// Orders
$ordersStmt = $pdo->prepare(
    'SELECT o.id, o.order_number, o.customer_name, o.customer_phone, o.order_type,
            o.table_number, o.delivery_address, o.payment_method, o.total_amount, 
            o.special_notes, o.status, o.created_at
     FROM `orders` o ORDER BY o.created_at DESC LIMIT 50'
);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Reservations
$resStmt = $pdo->prepare(
    'SELECT id, name, phone, reservation_date, reservation_time, guests, seating, special_notes, status, created_at
     FROM `reservations` ORDER BY reservation_date ASC, reservation_time ASC'
);
$resStmt->execute();
$reservations = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// Contact messages
$msgStmt = $pdo->prepare(
    'SELECT id, name, email, subject, message, is_read, created_at 
     FROM `contacts` ORDER BY created_at DESC'
);
$msgStmt->execute();
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

// Users
$usersStmt = $pdo->prepare('SELECT id, username, email, name, role, phone, created_at FROM `users` ORDER BY created_at DESC');
$usersStmt->execute();
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Inventory
$invStmt = $pdo->prepare('SELECT id, name, price, category, image, badge, stock, low_stock_threshold, is_active FROM `menu_items` ORDER BY category, name');
$invStmt->execute();
$inventory = $invStmt->fetchAll(PDO::FETCH_ASSOC);

// Initial active tab (can be set by ?tab=...)
$activeTab = sanitize($_GET['tab'] ?? 'overview');
$allowedTabs = ['overview', 'orders', 'reservations', 'messages', 'users', 'inventory'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'overview';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Bartoces Tastes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f0e0d;
            --surface: #1a1917;
            --surface2: #242220;
            --surface3: #2d2a27;
            --border: #2e2b28;
            --gold: #c9a84c;
            --gold-light: #e8c86b;
            --text: #f0ebe3;
            --muted: #888075;
            --red: #e05c5c;
            --green: #5cb85c;
            --blue: #5b9bd5;
            --orange: #e67e22;
            --radius-md: 12px;
            --transition: all 0.2s ease;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .admin-layout { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 250px; flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            padding: 24px 18px;
            position: sticky; top: 0; height: 100vh;
            overflow-y: auto;
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 28px; text-decoration: none;
        }
        .sidebar-logo img { width: 38px; height: 38px; object-fit: contain; border-radius: 8px; }
        .sidebar-logo span { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--gold); font-weight: 700; letter-spacing: 0.5px; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 10px;
            cursor: pointer; font-size: 13.5px; font-weight: 500;
            color: var(--muted); text-decoration: none;
            transition: var(--transition);
        }
        .nav-item:hover, .nav-item.active {
            background: var(--surface2); color: var(--gold);
        }
        .nav-item i { width: 18px; text-align: center; }
        .nav-badge {
            margin-left: auto; padding: 2px 7px; border-radius: 12px;
            font-size: 11px; font-weight: 700;
        }
        .nav-badge-warn { background: rgba(224,92,92,0.2); color: var(--red); }
        .sidebar-footer { margin-top: auto; padding-top: 18px; border-top: 1px solid var(--border); }
        .sidebar-user { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .avatar-circle {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #8b6200);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #0f0e0d; font-weight: 700;
        }
        .user-info small { display: block; color: var(--muted); font-size: 11px; }
        .user-info strong { font-size: 13px; }
        .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 9px 14px; border-radius: 8px;
            background: rgba(224,92,92,0.12); color: var(--red);
            border: 1px solid rgba(224,92,92,0.3);
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: var(--transition);
        }
        .btn-logout:hover { background: rgba(224,92,92,0.25); color: #fff; }

        /* MAIN */
        .main { flex: 1; padding: 28px 36px; overflow-y: auto; max-width: calc(100vw - 250px); }
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 14px; margin-bottom: 24px;
        }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: 26px; color: var(--gold); }
        .page-header p { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .header-actions-group { display: flex; gap: 10px; align-items: center; }

        /* BUTTONS */
        .btn-gold {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--gold); color: #0f0e0d; font-weight: 600;
            padding: 8px 16px; border-radius: 8px; border: none;
            cursor: pointer; font-size: 13px; transition: var(--transition);
            text-decoration: none;
        }
        .btn-gold:hover { background: var(--gold-light); }
        .btn-outline {
            display: inline-flex; align-items: center; gap: 8px;
            background: transparent; color: var(--text); font-weight: 500;
            padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border);
            cursor: pointer; font-size: 13px; transition: var(--transition);
            text-decoration: none;
        }
        .btn-outline:hover { background: var(--surface2); border-color: var(--gold); color: var(--gold); }

        /* FLASH NOTIFICATIONS */
        .alert-banner {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 18px; border-radius: 10px; margin-bottom: 22px;
            font-size: 13.5px; animation: fadeIn 0.3s ease;
        }
        .alert-success { background: rgba(92,184,92,0.15); border: 1px solid rgba(92,184,92,0.3); color: #8ce18c; }
        .alert-error { background: rgba(224,92,92,0.15); border: 1px solid rgba(224,92,92,0.3); color: #f28b8b; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 26px; }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 18px 20px;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .stat-card .stat-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px; }
        .stat-card .stat-value { font-size: 28px; font-weight: 700; color: var(--gold); }
        .stat-card .stat-icon { font-size: 20px; color: var(--gold); margin-bottom: 8px; }

        /* TAB ROW */
        .tab-row { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 18px; border-radius: 8px; border: 1px solid var(--border);
            background: var(--surface); color: var(--muted); font-size: 13px;
            cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 6px;
        }
        .tab-btn:hover { background: var(--surface2); color: var(--text); }
        .tab-btn.active { background: var(--gold); color: #0f0e0d; border-color: var(--gold); font-weight: 600; }

        /* SECTIONS & TABLES */
        .section { margin-bottom: 36px; }
        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px;
        }
        .section-title {
            font-size: 16px; font-weight: 600;
            display: flex; align-items: center; gap: 8px; color: var(--gold);
        }
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md); overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .data-table th {
            background: var(--surface2); padding: 11px 14px;
            text-align: left; font-weight: 600; font-size: 11.5px;
            text-transform: uppercase; letter-spacing: 0.7px;
            color: var(--muted); border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .data-table td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .data-table tr:hover td { background: rgba(255,255,255,0.025); }
        .data-table tbody tr:last-child td { border-bottom: none; }

        /* BADGES */
        .badge {
            display: inline-block; padding: 3px 9px; border-radius: 20px;
            font-size: 11px; font-weight: 600; text-transform: capitalize;
            white-space: nowrap;
        }
        .badge-pending    { background: rgba(201,168,76,0.15); color: var(--gold); }
        .badge-confirmed  { background: rgba(92,184,92,0.15);  color: var(--green); }
        .badge-preparing  { background: rgba(230,126,34,0.18); color: var(--orange); }
        .badge-ready      { background: rgba(91,155,213,0.18); color: var(--blue); }
        .badge-completed  { background: rgba(92,184,92,0.15);  color: var(--green); }
        .badge-cancelled  { background: rgba(224,92,92,0.15); color: var(--red); }
        .badge-admin      { background: rgba(91,155,213,0.15); color: var(--blue); }
        .badge-user       { background: rgba(255,255,255,0.08); color: var(--muted); }
        .badge-read       { background: rgba(92,184,92,0.1); color: var(--green); }
        .badge-unread     { background: rgba(224,92,92,0.15); color: var(--red); }

        /* INVENTORY ITEMS */
        .inventory-item-info { display: flex; align-items: center; gap: 12px; }
        .inventory-item-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
        .badge-tag {
            display: inline-block; font-size: 10px; background: rgba(201,168,76,0.12);
            color: var(--gold); padding: 1px 6px; border-radius: 4px; margin-top: 2px;
        }

        /* ACTIONS GROUP */
        .actions-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .btn-action {
            padding: 5px 10px; font-size: 11.5px; font-weight: 600;
            border-radius: 6px; border: none; cursor: pointer;
            transition: var(--transition); display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-action-green  { background: rgba(92,184,92,0.15); color: var(--green); border: 1px solid rgba(92,184,92,0.3); }
        .btn-action-green:hover  { background: var(--green); color: #fff; }
        .btn-action-gold   { background: rgba(201,168,76,0.15); color: var(--gold); border: 1px solid rgba(201,168,76,0.3); }
        .btn-action-gold:hover   { background: var(--gold); color: #000; }
        .btn-action-red    { background: rgba(224,92,92,0.15); color: var(--red); border: 1px solid rgba(224,92,92,0.3); }
        .btn-action-red:hover    { background: var(--red); color: #fff; }
        .btn-action-gray   { background: rgba(255,255,255,0.08); color: var(--text); border: 1px solid var(--border); }
        .btn-action-gray:hover   { background: var(--surface2); border-color: var(--gold); }

        .select-status {
            background: var(--surface2); color: var(--text);
            border: 1px solid var(--border); border-radius: 6px;
            padding: 4px 8px; font-size: 12px; cursor: pointer;
        }
        .select-status:focus { outline: none; border-color: var(--gold); }

        /* MODALS */
        .custom-modal {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.25s ease;
            padding: 16px;
        }
        .custom-modal.active { opacity: 1; visibility: visible; }
        .modal-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; width: 100%; max-width: 480px;
            position: relative; overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            animation: modalScale 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalScale {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header-banner {
            background: linear-gradient(135deg, var(--surface2), #201a14);
            border-bottom: 1px solid var(--border);
            padding: 20px 24px; text-align: center;
        }
        .modal-header-banner .modal-icon { font-size: 26px; color: var(--gold); margin-bottom: 8px; }
        .modal-header-banner h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--gold); }
        .modal-header-banner p { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
        .modal-close-btn {
            position: absolute; top: 14px; right: 14px;
            background: transparent; border: none; color: var(--muted);
            font-size: 18px; cursor: pointer; padding: 4px 8px;
            border-radius: 6px; transition: var(--transition);
        }
        .modal-close-btn:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .modal-form { padding: 22px 24px; display: flex; flex-direction: column; gap: 14px; }
        .form-row { display: flex; gap: 12px; }
        .form-col { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .modal-form label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-form input, .modal-form select, .modal-form textarea {
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 8px; padding: 10px 12px; color: var(--text);
            font-family: inherit; font-size: 13.5px; transition: var(--transition);
        }
        .modal-form input:focus, .modal-form select:focus, .modal-form textarea:focus {
            outline: none; border-color: var(--gold);
        }
        .modal-form input[readonly] { background: rgba(255,255,255,0.03); color: var(--muted); cursor: not-allowed; }
        .btn-modal-submit {
            background: var(--gold); color: #0f0e0d; font-weight: 700;
            padding: 12px; border-radius: 8px; border: none;
            cursor: pointer; font-size: 14px; transition: var(--transition);
            margin-top: 6px;
        }
        .btn-modal-submit:hover { background: var(--gold-light); }

        /* TOAST CONTAINER */
        .toast-container {
            position: fixed; bottom: 24px; right: 24px; z-index: 10000;
            display: flex; flex-direction: column; gap: 10px; pointer-events: none;
        }
        .toast {
            background: var(--surface2); color: var(--text);
            border: 1px solid var(--gold); border-radius: 10px;
            padding: 12px 18px; font-size: 13px; font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex; align-items: center; gap: 10px;
            pointer-events: auto; animation: toastIn 0.3s ease;
        }
        @keyframes toastIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { max-width: 100vw; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="adminSidebar">
        <a href="admin.php" class="sidebar-logo">
            <img src="images/logo.png" alt="Bartoces Tastes Logo">
            <span>BARTOCES TASTES</span>
        </a>

        <nav class="sidebar-nav">
            <span class="nav-item <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" data-target="overview" onclick="showTab('overview')">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </span>
            <span class="nav-item <?php echo $activeTab === 'orders' ? 'active' : ''; ?>" data-target="orders" onclick="showTab('orders')">
                <i class="fa-solid fa-bag-shopping"></i> Orders
                <span class="nav-badge badge-pending"><?php echo $orderCount; ?></span>
            </span>
            <span class="nav-item <?php echo $activeTab === 'reservations' ? 'active' : ''; ?>" data-target="reservations" onclick="showTab('reservations')">
                <i class="fa-solid fa-calendar-check"></i> Reservations
                <span class="nav-badge badge-pending"><?php echo $resCount; ?></span>
            </span>
            <span class="nav-item <?php echo $activeTab === 'inventory' ? 'active' : ''; ?>" data-target="inventory" onclick="showTab('inventory')">
                <i class="fa-solid fa-boxes-stacked"></i> Stocks &amp; Inventory
                <?php if ($lowStockCount > 0): ?>
                <span class="nav-badge nav-badge-warn"><?php echo $lowStockCount; ?> Low</span>
                <?php endif; ?>
            </span>
            <span class="nav-item <?php echo $activeTab === 'messages' ? 'active' : ''; ?>" data-target="messages" onclick="showTab('messages')">
                <i class="fa-solid fa-comments"></i> Messages
                <span class="nav-badge badge-pending"><?php echo $msgCount; ?></span>
            </span>
            <span class="nav-item <?php echo $activeTab === 'users' ? 'active' : ''; ?>" data-target="users" onclick="showTab('users')">
                <i class="fa-solid fa-users"></i> Users
            </span>
            <a href="index.php" class="nav-item">
                <i class="fa-solid fa-house"></i> View Live Site
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar-circle"><?php echo strtoupper(substr($admin['name'], 0, 1)); ?></div>
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($admin['name']); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- FLASH ALERTS -->
        <?php if ($flashSuccess): ?>
        <div class="alert-banner alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($flashSuccess); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
        <div class="alert-banner alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($flashError); ?></span>
        </div>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-shield-halved"></i> Admin Control Center</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin['name']); ?>. Manage reservations, stocks, orders, and inquiries.</p>
            </div>
            <div class="header-actions-group">
                <button type="button" class="btn-gold" id="btnOpenNewDishModal">
                    <i class="fa-solid fa-plus"></i> Add New Menu Item
                </button>
                <a href="index.php" class="btn-outline">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Website
                </a>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card" style="cursor:pointer;" onclick="showTab('orders')">
                <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                <div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo $orderCount; ?></div>
                </div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="showTab('reservations')">
                <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div>
                    <div class="stat-label">Reservations</div>
                    <div class="stat-value"><?php echo $resCount; ?></div>
                </div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="showTab('inventory')">
                <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-label">Menu Items (Stocks)</div>
                    <div class="stat-value"><?php echo count($inventory); ?></div>
                </div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="showTab('messages')">
                <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
                <div>
                    <div class="stat-label">Customer Inquiries</div>
                    <div class="stat-value"><?php echo $msgCount; ?></div>
                </div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="showTab('users')">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-label">Registered Users</div>
                    <div class="stat-value"><?php echo $userCount; ?></div>
                </div>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="tab-row" id="tabRow">
            <button class="tab-btn <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" data-target="overview" onclick="showTab('overview', this)">
                <i class="fa-solid fa-chart-pie"></i> Overview
            </button>
            <button class="tab-btn <?php echo $activeTab === 'orders' ? 'active' : ''; ?>" data-target="orders" onclick="showTab('orders', this)">
                <i class="fa-solid fa-bag-shopping"></i> Orders (<?php echo $orderCount; ?>)
            </button>
            <button class="tab-btn <?php echo $activeTab === 'reservations' ? 'active' : ''; ?>" data-target="reservations" onclick="showTab('reservations', this)">
                <i class="fa-solid fa-calendar-check"></i> Reservations (<?php echo $resCount; ?>)
            </button>
            <button class="tab-btn <?php echo $activeTab === 'inventory' ? 'active' : ''; ?>" data-target="inventory" onclick="showTab('inventory', this)">
                <i class="fa-solid fa-boxes-stacked"></i> Stocks &amp; Inventory (<?php echo count($inventory); ?>)
            </button>
            <button class="tab-btn <?php echo $activeTab === 'messages' ? 'active' : ''; ?>" data-target="messages" onclick="showTab('messages', this)">
                <i class="fa-solid fa-comments"></i> Messages (<?php echo $msgCount; ?>)
            </button>
            <button class="tab-btn <?php echo $activeTab === 'users' ? 'active' : ''; ?>" data-target="users" onclick="showTab('users', this)">
                <i class="fa-solid fa-users"></i> Users (<?php echo $userCount; ?>)
            </button>
        </div>

        <!-- ====================================================== -->
        <!-- TAB 1: OVERVIEW -->
        <!-- ====================================================== -->
        <div id="tab-overview" style="<?php echo $activeTab === 'overview' ? 'display:block;' : 'display:none;'; ?>">
            
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-bag-shopping"></i> Recent Orders</div>
                    <button class="btn-outline" onclick="showTab('orders')">View All Orders</button>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th><th>Customer</th><th>Phone</th><th>Type</th>
                                <th>Total</th><th>Status</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No orders placed yet.</td></tr>
                            <?php else: foreach (array_slice($orders, 0, 5) as $o): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_phone'] ?: '—'); ?></td>
                                <td><?php echo ucfirst($o['order_type']); ?></td>
                                <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="admin.php" style="display:inline-flex;gap:4px;">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                        <select name="status" class="select-status" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $o['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="preparing" <?php echo $o['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="ready" <?php echo $o['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="completed" <?php echo $o['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $o['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-calendar-check"></i> Upcoming Reservations</div>
                    <button class="btn-outline" onclick="showTab('reservations')">View All Reservations</button>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th><th>Phone</th><th>Date</th><th>Time</th>
                                <th>Guests</th><th>Seating</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No reservations recorded.</td></tr>
                            <?php else: foreach (array_slice($reservations, 0, 5) as $r): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($r['reservation_date'])); ?></td>
                                <td><?php echo htmlspecialchars($r['reservation_time']); ?></td>
                                <td><?php echo htmlspecialchars($r['guests']); ?></td>
                                <td><?php echo htmlspecialchars($r['seating']); ?></td>
                                <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                <td>
                                    <div class="actions-group">
                                        <?php if ($r['status'] !== 'confirmed'): ?>
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn-action btn-action-green" title="Confirm Reservation">
                                                <i class="fa-solid fa-check"></i> Confirm
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($r['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn-action btn-action-red" title="Cancel Reservation">
                                                <i class="fa-solid fa-xmark"></i> Cancel
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ====================================================== -->
        <!-- TAB 2: ORDERS -->
        <!-- ====================================================== -->
        <div id="tab-orders" style="<?php echo $activeTab === 'orders' ? 'display:block;' : 'display:none;'; ?>">
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-bag-shopping"></i> Customer Orders Management</div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Order Number</th><th>Customer</th><th>Phone</th><th>Type &amp; Destination</th>
                                <th>Payment</th><th>Total</th><th>Notes</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:24px;">No orders found.</td></tr>
                            <?php else: foreach ($orders as $o): ?>
                            <tr>
                                <td><?php echo $o['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_phone'] ?: '—'); ?></td>
                                <td>
                                    <strong><?php echo ucfirst($o['order_type']); ?></strong>
                                    <?php if ($o['order_type'] === 'dinein' && !empty($o['table_number'])): ?>
                                        <small style="display:block;color:var(--muted);">Table #<?php echo htmlspecialchars($o['table_number']); ?></small>
                                    <?php elseif ($o['order_type'] === 'delivery' && !empty($o['delivery_address'])): ?>
                                        <small style="display:block;color:var(--muted);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($o['delivery_address']); ?>">
                                            <?php echo htmlspecialchars($o['delivery_address']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($o['payment_method'] ?? 'Cash'); ?></td>
                                <td><strong>₱<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($o['special_notes'] ?: '—'); ?></td>
                                <td>
                                    <form method="POST" action="admin.php" style="display:inline-block;">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                        <select name="status" class="select-status" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $o['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="preparing" <?php echo $o['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="ready" <?php echo $o['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="completed" <?php echo $o['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $o['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete order #<?php echo $o['order_number']; ?>?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" class="btn-action btn-action-red" title="Delete Order">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- TAB 3: RESERVATIONS -->
        <!-- ====================================================== -->
        <div id="tab-reservations" style="<?php echo $activeTab === 'reservations' ? 'display:block;' : 'display:none;'; ?>">
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-calendar-check"></i> Table Reservations Management</div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Customer</th><th>Phone</th><th>Date</th><th>Time</th>
                                <th>Party Size</th><th>Seating Area</th><th>Special Notes</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:24px;">No reservations found.</td></tr>
                            <?php else: foreach ($reservations as $r): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                                <td><strong><?php echo date('M d, Y', strtotime($r['reservation_date'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['reservation_time']); ?></td>
                                <td><?php echo htmlspecialchars($r['guests']); ?></td>
                                <td><?php echo htmlspecialchars($r['seating']); ?></td>
                                <td><?php echo htmlspecialchars($r['special_notes'] ?: '—'); ?></td>
                                <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                <td>
                                    <div class="actions-group">
                                        <?php if ($r['status'] !== 'confirmed'): ?>
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn-action btn-action-green" title="Confirm Reservation">
                                                <i class="fa-solid fa-check"></i> Confirm
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($r['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="update_reservation_status">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn-action btn-action-gold" title="Cancel Reservation">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <form method="POST" action="admin.php" onsubmit="return confirm('Delete reservation for <?php echo htmlspecialchars($r['name']); ?>?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_reservation">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" class="btn-action btn-action-red" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- TAB 4: STOCKS & INVENTORY -->
        <!-- ====================================================== -->
        <div id="tab-inventory" style="<?php echo $activeTab === 'inventory' ? 'display:block;' : 'display:none;'; ?>">
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-boxes-stacked"></i> Dish Stocks &amp; Inventory Management</div>
                    <button type="button" class="btn-gold" id="btnOpenNewDishModal2">
                        <i class="fa-solid fa-plus"></i> Add New Dish
                    </button>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Menu Item</th><th>Category</th><th>Price</th>
                                <th>Current Stock</th><th>Low Threshold</th><th>Stock Status</th><th>Visibility</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px;">No menu items in database.</td></tr>
                            <?php else: foreach ($inventory as $item):
                                $stockVal    = (int)$item['stock'];
                                $threshold   = (int)$item['low_stock_threshold'];
                                $statusClass = $stockVal <= 0 ? 'badge-unread' : ($stockVal <= $threshold ? 'badge-pending' : 'badge-confirmed');
                                $statusText  = $stockVal <= 0 ? 'Out of Stock' : ($stockVal <= $threshold ? 'Low Stock' : 'In Stock');
                            ?>
                            <tr data-item-id="<?php echo $item['id']; ?>">
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <div class="inventory-item-info">
                                        <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="inventory-item-img">
                                        <?php endif; ?>
                                        <div class="inventory-item-details">
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <?php if (!empty($item['badge'])): ?>
                                            <span class="badge-tag"><?php echo htmlspecialchars($item['badge']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo ucfirst($item['category']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <strong style="font-size:15px;color:<?php echo $stockVal <= $threshold ? 'var(--red)' : 'var(--gold)'; ?>;">
                                        <?php echo $stockVal; ?>
                                    </strong>
                                </td>
                                <td><?php echo $threshold; ?></td>
                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                <td>
                                    <form method="POST" action="admin.php" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_item_active">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-action <?php echo $item['is_active'] ? 'btn-action-green' : 'btn-action-gray'; ?>" title="Toggle Visibility">
                                            <?php echo $item['is_active'] ? '<i class="fa-solid fa-eye"></i> Active' : '<i class="fa-solid fa-eye-slash"></i> Hidden'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions-group">
                                        <!-- Open Update Modal Button -->
                                        <button type="button" class="btn-action btn-action-gold btn-edit-stock" 
                                                data-id="<?php echo $item['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-stock="<?php echo $item['stock']; ?>"
                                                data-threshold="<?php echo $item['low_stock_threshold']; ?>">
                                            <i class="fa-solid fa-pen-to-square"></i> Set Stock
                                        </button>

                                        <!-- Quick Restock +15 Button -->
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="quick_restock">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="qty" value="15">
                                            <button type="submit" class="btn-action btn-action-green" title="Quick Restock +15">
                                                <i class="fa-solid fa-plus"></i> +15
                                            </button>
                                        </form>

                                        <!-- Delete Item Button -->
                                        <form method="POST" action="admin.php" onsubmit="return confirm('Delete dish <?php echo htmlspecialchars($item['name']); ?>?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_menu_item">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-action btn-action-red" title="Delete Dish">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- TAB 5: MESSAGES -->
        <!-- ====================================================== -->
        <div id="tab-messages" style="<?php echo $activeTab === 'messages' ? 'display:block;' : 'display:none;'; ?>">
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-comments"></i> Customer Inquiries &amp; Messages</div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No contact messages received.</td></tr>
                            <?php else: foreach ($messages as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" style="color:var(--gold);text-decoration:none;"><?php echo htmlspecialchars($m['email']); ?></a></td>
                                <td><?php echo htmlspecialchars($m['subject'] ?: '—'); ?></td>
                                <td style="max-width:240px;"><?php echo htmlspecialchars($m['message']); ?></td>
                                <td>
                                    <span class="badge <?php echo $m['is_read'] ? 'badge-read' : 'badge-unread'; ?>">
                                        <?php echo $m['is_read'] ? 'Read' : 'New'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></td>
                                <td>
                                    <div class="actions-group">
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_message_read">
                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" class="btn-action <?php echo $m['is_read'] ? 'btn-action-gray' : 'btn-action-green'; ?>" title="Toggle Read">
                                                <?php echo $m['is_read'] ? 'Mark Unread' : 'Mark Read'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="admin.php" onsubmit="return confirm('Delete message from <?php echo htmlspecialchars($m['name']); ?>?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                            <button type="submit" class="btn-action btn-action-red" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- TAB 6: USERS -->
        <!-- ====================================================== -->
        <div id="tab-users" style="<?php echo $activeTab === 'users' ? 'display:block;' : 'display:none;'; ?>">
            <div class="section">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-users"></i> Registered Accounts (<?php echo $userCount; ?>)</div>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Registered</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?: '—'); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['id'] !== $admin['id']): ?>
                                    <form method="POST" action="admin.php" onsubmit="return confirm('Delete account <?php echo htmlspecialchars($u['username']); ?>?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn-action btn-action-red" title="Delete User">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <small style="color:var(--gold);"><i class="fa-solid fa-user-shield"></i> You (Active)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ============================================================ -->
<!-- MODAL: UPDATE STOCK LEVEL -->
<!-- ============================================================ -->
<div id="stockModal" class="custom-modal" role="dialog" aria-modal="true">
    <div class="modal-card">
        <button type="button" class="modal-close-btn" id="closeStockBtn" aria-label="Close stock modal">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="modal-header-banner">
            <i class="fa-solid fa-boxes-stacked modal-icon"></i>
            <h3>Update Stock Level</h3>
            <p>Adjust the inventory count &amp; threshold for this dish</p>
        </div>

        <form id="stockForm" class="modal-form" method="POST" action="admin.php">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="id" id="stockItemId" value="">

            <div class="form-row">
                <div class="form-col">
                    <label for="stockItemName">Dish Name</label>
                    <input type="text" id="stockItemName" readonly>
                </div>
                <div class="form-col">
                    <label for="stockCurrent">Current Count</label>
                    <input type="number" id="stockCurrent" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label for="stockNew">New Stock Level</label>
                    <input type="number" id="stockNew" name="stock" min="0" required>
                </div>
                <div class="form-col">
                    <label for="stockThreshold">Low Alert Threshold</label>
                    <input type="number" id="stockThreshold" name="threshold" min="0" required>
                </div>
            </div>

            <button type="submit" class="btn-modal-submit" id="btnSaveStock">
                Save Stock Changes
            </button>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: ADD NEW MENU ITEM -->
<!-- ============================================================ -->
<div id="newDishModal" class="custom-modal" role="dialog" aria-modal="true">
    <div class="modal-card" style="max-width:540px;">
        <button type="button" class="modal-close-btn" id="closeNewDishBtn" aria-label="Close modal">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="modal-header-banner">
            <i class="fa-solid fa-utensils modal-icon"></i>
            <h3>Add New Menu Item</h3>
            <p>Introduce a new dish to the Bartoces Tastes dining selection</p>
        </div>

        <form class="modal-form" method="POST" action="admin.php">
            <input type="hidden" name="action" value="add_menu_item">

            <div class="form-row">
                <div class="form-col">
                    <label for="newDishName">Dish Name *</label>
                    <input type="text" id="newDishName" name="name" placeholder="e.g. Crispy Pata" required>
                </div>
                <div class="form-col">
                    <label for="newDishCategory">Category *</label>
                    <select id="newDishCategory" name="category" required>
                        <option value="classics">Filipino Classics</option>
                        <option value="fastfood">Snacks &amp; Sides</option>
                        <option value="pasta">Pizza &amp; Pasta</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label for="newDishPrice">Price (₱) *</label>
                    <input type="number" id="newDishPrice" name="price" step="0.01" min="1" placeholder="250.00" required>
                </div>
                <div class="form-col">
                    <label for="newDishStock">Initial Stock *</label>
                    <input type="number" id="newDishStock" name="stock" min="0" value="50" required>
                </div>
                <div class="form-col">
                    <label for="newDishThresh">Threshold</label>
                    <input type="number" id="newDishThresh" name="threshold" min="0" value="5" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label for="newDishBadge">Badge (Optional)</label>
                    <input type="text" id="newDishBadge" name="badge" placeholder="e.g. Chef Special, Bestseller">
                </div>
                <div class="form-col">
                    <label for="newDishImage">Image Path</label>
                    <input type="text" id="newDishImage" name="image" value="images/chef.png" placeholder="images/sample.jpg">
                </div>
            </div>

            <div class="form-col">
                <label for="newDishDesc">Description</label>
                <textarea id="newDishDesc" name="description" rows="2" placeholder="Brief description of the dish..."></textarea>
            </div>

            <button type="submit" class="btn-modal-submit">
                <i class="fa-solid fa-plus"></i> Add Dish to Menu
            </button>
        </form>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div id="toastContainer" class="toast-container" aria-live="polite"></div>

<script>
// ============================================================
// TAB NAVIGATION & SYNCHRONIZATION
// ============================================================
function showTab(tabName, clickedBtn) {
    const tabs = ['overview', 'orders', 'reservations', 'inventory', 'messages', 'users'];
    
    // Hide all tab panes
    tabs.forEach(t => {
        const pane = document.getElementById('tab-' + t);
        if (pane) pane.style.display = 'none';
    });

    // Show selected pane
    const activePane = document.getElementById('tab-' + tabName);
    if (activePane) activePane.style.display = 'block';

    // Update top tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-target') === tabName);
    });

    // Update sidebar navigation items
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
        item.classList.toggle('active', item.getAttribute('data-target') === tabName);
    });

    // Update URL hash without jumping
    if (history.replaceState) {
        history.replaceState(null, null, '?tab=' + tabName);
    }
}

// ============================================================
// MODAL CONTROLS & TOAST SYSTEM
// ============================================================
function openModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('active');
    document.body.style.overflow = '';
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    const icon = type === 'error' ? 'fa-circle-exclamation text-red' : 'fa-circle-check text-green';
    toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ============================================================
// EVENT LISTENERS (DOM READY)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // Stock modal elements
    const stockModal    = document.getElementById('stockModal');
    const closeStockBtn = document.getElementById('closeStockBtn');
    const stockForm     = document.getElementById('stockForm');

    // New Dish modal elements
    const newDishModal       = document.getElementById('newDishModal');
    const btnOpenNewDishModal  = document.getElementById('btnOpenNewDishModal');
    const btnOpenNewDishModal2 = document.getElementById('btnOpenNewDishModal2');
    const closeNewDishBtn     = document.getElementById('closeNewDishBtn');

    // Open stock modal from any "Set Stock" button
    document.querySelectorAll('.btn-edit-stock').forEach(btn => {
        btn.addEventListener('click', () => {
            const id        = btn.getAttribute('data-id');
            const name      = btn.getAttribute('data-name');
            const stock     = btn.getAttribute('data-stock');
            const threshold = btn.getAttribute('data-threshold');

            document.getElementById('stockItemId').value    = id;
            document.getElementById('stockItemName').value  = name;
            document.getElementById('stockCurrent').value   = stock;
            document.getElementById('stockNew').value       = stock;
            document.getElementById('stockThreshold').value = threshold;

            openModal(stockModal);
        });
    });

    if (closeStockBtn) {
        closeStockBtn.addEventListener('click', () => closeModal(stockModal));
    }

    // New Dish Modal triggers
    if (btnOpenNewDishModal) {
        btnOpenNewDishModal.addEventListener('click', () => openModal(newDishModal));
    }
    if (btnOpenNewDishModal2) {
        btnOpenNewDishModal2.addEventListener('click', () => openModal(newDishModal));
    }
    if (closeNewDishBtn) {
        closeNewDishBtn.addEventListener('click', () => closeModal(newDishModal));
    }

    // Close modals on clicking overlay backdrop
    [stockModal, newDishModal].forEach(m => {
        if (m) {
            m.addEventListener('click', (e) => {
                if (e.target === m) closeModal(m);
            });
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(stockModal);
            closeModal(newDishModal);
        }
    });
});
</script>

</body>
</html>
