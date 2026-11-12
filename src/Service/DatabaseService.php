<?php

namespace App\Service;

use App\Entity\MenuItem;
use App\Entity\User;
use PDO;
use PDOException;

class DatabaseService
{
    private ?PDO $pdo = null;
    private string $activeSource = 'Unknown';

    public function __construct()
    {
        
    }

    
    public function getConnection(): ?PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $candidates = [
            [
                'host'  => $_ENV['DATABASE_HOST'] ?? '127.0.0.1',
                'port'  => (int)($_ENV['DATABASE_PORT'] ?? 3307),
                'user'  => $_ENV['DATABASE_USER'] ?? 'root',
                'pass'  => $_ENV['DATABASE_PASSWORD'] ?? 'root',
                'label' => 'Docker MySQL (Port 3307)'
            ],
            [
                'host'  => '127.0.0.1',
                'port'  => 3307,
                'user'  => 'webdev',
                'pass'  => 'webdev123',
                'label' => 'Docker MySQL (Port 3307, webdev)'
            ],
            [
                'host'  => '127.0.0.1',
                'port'  => 3306,
                'user'  => 'root',
                'pass'  => '',
                'label' => 'Local MySQL (Port 3306)'
            ],
            [
                'host'  => '127.0.0.1',
                'port'  => 3306,
                'user'  => 'root',
                'pass'  => 'root',
                'label' => 'Local MySQL (Port 3306, root pwd)'
            ]
        ];

        foreach ($candidates as $cand) {
            try {
                $dsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
                $pdo = new PDO($dsn, $cand['user'], $cand['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 1,
                ]);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `bartoces_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `bartoces_db`");

                $this->pdo = $pdo;
                $this->activeSource = $cand['label'];

                $this->ensureSchema();
                return $this->pdo;
            } catch (PDOException $e) {
                continue;
            }
        }

        
        try {
            $dbDir = dirname(__DIR__, 2) . '/var';
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0777, true);
            }
            $sqlitePath = $dbDir . '/bartoces.db';
            $isNew = !file_exists($sqlitePath);
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $this->pdo = $pdo;
            $this->activeSource = 'Built-in Fast Database (SQLite)';

            if ($isNew) {
                $this->initSqliteSchema();
            } else {
                $this->ensureSchema();
            }
            return $this->pdo;
        } catch (\Throwable $e) {
            return null;
        }
    }

    
    private function initSqliteSchema(): void
    {
        if (!$this->pdo) return;

        $schema = <<<SQL
CREATE TABLE IF NOT EXISTS `users` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT NULL UNIQUE,
    `email` TEXT NOT NULL UNIQUE,
    `name` TEXT NOT NULL,
    `password` TEXT NOT NULL,
    `phone` TEXT,
    `role` TEXT NOT NULL DEFAULT 'user',
    `status` TEXT NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` TEXT NOT NULL UNIQUE,
    `description` TEXT,
    `price` REAL NOT NULL,
    `category` TEXT DEFAULT 'classics',
    `image` TEXT,
    `badge` TEXT,
    `stock` INTEGER NOT NULL DEFAULT 0,
    `low_stock_threshold` INTEGER NOT NULL DEFAULT 5,
    `created_by` INTEGER DEFAULT NULL,
    `is_active` INTEGER NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_number` TEXT NOT NULL UNIQUE,
    `user_id` INTEGER,
    `customer_name` TEXT NOT NULL,
    `customer_phone` TEXT,
    `order_type` TEXT NOT NULL DEFAULT 'dinein',
    `table_number` TEXT,
    `delivery_address` TEXT,
    `payment_method` TEXT NOT NULL DEFAULT 'Cash',
    `subtotal` REAL NOT NULL DEFAULT 0.00,
    `delivery_fee` REAL NOT NULL DEFAULT 0.00,
    `total_amount` REAL NOT NULL DEFAULT 0.00,
    `special_notes` TEXT,
    `status` TEXT NOT NULL DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `order_id` INTEGER NOT NULL,
    `item_id` INTEGER,
    `item_name` TEXT NOT NULL,
    `unit_price` REAL NOT NULL,
    `quantity` INTEGER NOT NULL DEFAULT 1,
    `addons` TEXT,
    `subtotal` REAL NOT NULL DEFAULT 0.00
);

CREATE TABLE IF NOT EXISTS `reservations` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER,
    `name` TEXT NOT NULL,
    `phone` TEXT NOT NULL,
    `reservation_date` TEXT NOT NULL,
    `reservation_time` TEXT NOT NULL,
    `guests` TEXT NOT NULL,
    `seating` TEXT DEFAULT 'Indoor Main Dining',
    `special_notes` TEXT,
    `status` TEXT NOT NULL DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INTEGER,
    `name` TEXT NOT NULL,
    `email` TEXT NOT NULL,
    `subject` TEXT,
    `message` TEXT NOT NULL,
    `is_read` INTEGER NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
SQL;
        $this->pdo->exec($schema);

        
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $staffPass = password_hash('staff123', PASSWORD_DEFAULT);
        $userPass  = password_hash('user123', PASSWORD_DEFAULT);
        $this->pdo->exec("INSERT OR IGNORE INTO `users` (`username`, `email`, `name`, `password`, `phone`, `role`, `status`) VALUES
            ('admin', 'admin@bartoces.com', 'Executive Chef / Manager', '{$adminPass}', '0912 345 6789', 'admin', 'active'),
            ('staff', 'staff@bartoces.com', 'Elena Reyes (Sous Chef)',  '{$staffPass}', '0922 456 7890', 'staff', 'active'),
            ('user',  'user@bartoces.com',  'Juan Dela Cruz',           '{$userPass}',  '0998 765 4321', 'user',  'active')");

        
        $items = [
            ['Chicken Inasal', 'Achiote-marinated grilled chicken thigh served with garlic rice and calamansi dip.', 249.00, 'classics', 'images/chicken-inasal.jpg', 'Bestseller', 50, 1],
            ['Beef Steak', 'Tender ribeye slices simmered in citrus soy glaze and caramelized onions.', 399.00, 'classics', 'images/beef-steak.jpg', 'Signature', 30, 1],
            ['Pork Sisig', 'Crispy diced pork belly served sizzling with fresh egg, chili, and calamansi.', 199.00, 'classics', 'images/pork-sisig.jpg', 'Hot and Sizzling', 40, 1],
            ['Creamy Carbonara', 'Al dente pasta tossed in rich parmesan egg sauce with crispy smoked bacon.', 250.00, 'pasta', 'images/carbonara.jpg', 'Chef Choice', 35, 1],
            ['Gourmet Burger', 'Flame-grilled artisan beef patty with melted cheddar and brioche bun.', 120.00, 'fastfood', 'images/burger.jpg', 'Popular', 60, 1],
            ['Crispy Fries', 'Hand-cut golden russet potato fries dusted with sea salt. Served with garlic aioli.', 99.00, 'fastfood', 'images/fries.jpg', 'Sides', 100, 2],
            ['Artisan Pizza', 'Woodfired thin-crust pizza with San Marzano tomato, mozzarella, pepperoni and basil.', 300.00, 'pasta', 'images/pizza.jpg', 'Specialty', 45, 2],
            ['Grilled Chicken Inasal', 'Chef signature charcoal flame-grilled chicken in traditional Ilonggo marinade.', 249.00, 'classics', 'images/grilled-chicken.jpg', 'Chefs Pick', 25, 2],
            ['Bacon Cheeseburger', 'Juicy beef patty topped with crispy bacon, melted cheddar, lettuce, tomato, and special sauce.', 180.00, 'fastfood', 'images/bacon-cheeseburger.jpg', 'Chef Special', 40, 2],
            ['Mushroom Swiss Burger', 'Savory beef patty with sautéed mushrooms, melted Swiss cheese, and garlic aioli.', 185.00, 'fastfood', 'images/mushroom-swiss-burger.jpg', 'Gourmet', 35, 2],
            ['Spicy Chicken Burger', 'Crispy chicken fillet with pepper jack cheese, lettuce, tomato, and spicy mayo.', 160.00, 'fastfood', 'images/spicy-chicken-burger.jpg', 'Spicy', 45, 2],
            ['Spicy Arrabbiata', 'Penne pasta in a spicy tomato sauce with garlic, chili flakes, and fresh basil.', 220.00, 'pasta', 'images/spicy-arrabbiata.jpg', 'Spicy', 30, 2],
            ['Mushroom Alfredo', 'Fettuccine in a creamy Alfredo sauce with sautéed mushrooms, garlic, and parmesan.', 240.00, 'pasta', 'images/mushroom-alfredo.jpg', 'Chef Choice', 25, 2],
            ['Seafood Linguine', 'Linguine with shrimp, mussels, clams in a white wine garlic sauce.', 280.00, 'pasta', 'images/seafood-linguine.jpg', 'Specialty', 20, 1],
            ['Beef Teriyaki Bowl', 'Grilled beef strips with steamed rice, stir-fried vegetables, and homemade teriyaki glaze.', 220.00, 'classics', 'images/beef-teriyaki.jpg', 'Asian', 35, 1],
            ['Chicken Teriyaki', 'Grilled chicken thigh with steamed rice, stir-fried vegetables, and sweet teriyaki glaze.', 195.00, 'classics', 'images/chicken-teriyaki.jpg', 'Asian', 40, 1],
            ['Vegetable Stir Fry', 'Mixed seasonal vegetables stir-fried with tofu in ginger-garlic sauce.', 175.00, 'classics', 'images/vegetable-stir-fry.jpg', 'Healthy', 50, 1],
        ];

        $ins = $this->pdo->prepare("INSERT OR IGNORE INTO `menu_items` (`name`, `description`, `price`, `category`, `image`, `badge`, `stock`, `low_stock_threshold`, `created_by`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, 5, ?, 1)");
        foreach ($items as $dish) {
            $ins->execute($dish);
        }
    }

    
    public function getDbSource(): string
    {
        if ($this->pdo === null) {
            $this->getConnection();
        }
        return $this->activeSource;
    }

    
    private function ensureSchema(): void
    {
        if (!$this->pdo) return;

        try {
            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

            if ($isSqlite) {
                
                $uCols = $this->pdo->query("PRAGMA table_info(users)")->fetchAll();
                $uNames = array_column($uCols, 'name');
                if (!in_array('status', $uNames)) {
                    $this->pdo->exec("ALTER TABLE `users` ADD COLUMN `status` TEXT NOT NULL DEFAULT 'active'");
                }

                
                $mCols = $this->pdo->query("PRAGMA table_info(menu_items)")->fetchAll();
                $mNames = array_column($mCols, 'name');
                if (!in_array('created_by', $mNames)) {
                    $this->pdo->exec("ALTER TABLE `menu_items` ADD COLUMN `created_by` INTEGER DEFAULT NULL");
                }
            } else {
                
                $uCols = $this->pdo->query("SHOW COLUMNS FROM `users` LIKE 'status'")->fetch();
                if (!$uCols) {
                    $this->pdo->exec("ALTER TABLE `users` ADD COLUMN `status` ENUM('active','disabled','archived') NOT NULL DEFAULT 'active'");
                }

                $mCols = $this->pdo->query("SHOW COLUMNS FROM `menu_items` LIKE 'created_by'")->fetch();
                if (!$mCols) {
                    $this->pdo->exec("ALTER TABLE `menu_items` ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL");
                }
            }

            
            $staffCheck = $this->pdo->query("SELECT id FROM `users` WHERE `username` = 'staff'")->fetch();
            if (!$staffCheck) {
                $staffPass = password_hash('staff123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO `users` (`username`, `email`, `name`, `password`, `phone`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute(['staff', 'staff@bartoces.com', 'Elena Reyes (Sous Chef)', $staffPass, '0922 456 7890', 'staff', 'active']);
                $staffId = (int)$this->pdo->lastInsertId();
            } else {
                $staffId = (int)$staffCheck['id'];
            }

            
            $this->pdo->exec("UPDATE `menu_items` SET `created_by` = 1 WHERE `id` IN (1, 2, 3, 4, 5) AND `created_by` IS NULL");
            $this->pdo->exec("UPDATE `menu_items` SET `created_by` = {$staffId} WHERE `id` IN (6, 7, 8, 9, 10) AND `created_by` IS NULL");
            $this->pdo->exec("UPDATE `menu_items` SET `created_by` = 1 WHERE `created_by` IS NULL");
        } catch (\Throwable $e) {
            
        }
    }

    
    
    

    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expected = $_SESSION['_csrf_token'] ?? '';
        if (empty($expected) || empty($token)) {
            return false;
        }
        return hash_equals($expected, $token);
    }

    
    
    

    
    public function getInventory(): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $sql = 'SELECT m.id, m.name, m.description, m.price, m.category, m.image, m.badge, 
                       m.stock, m.low_stock_threshold, m.is_active, m.created_by,
                       COALESCE(u.name, "Administrator") AS creator_name,
                       COALESCE(u.role, "admin") AS creator_role
                FROM `menu_items` m
                LEFT JOIN `users` u ON m.created_by = u.id
                ORDER BY m.category, m.name';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    
    public function searchInventory(string $query = '', string $category = '', string $status = ''): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $conditions = ['1=1'];
        $params     = [];

        if ($query !== '') {
            $conditions[] = '(m.name LIKE :q OR m.badge LIKE :q OR m.description LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }

        if ($category !== '') {
            $conditions[] = 'm.category = :cat';
            $params[':cat'] = $category;
        }

        if ($status === 'instock') {
            $conditions[] = 'm.stock > m.low_stock_threshold';
        } elseif ($status === 'lowstock') {
            $conditions[] = '(m.stock > 0 AND m.stock <= m.low_stock_threshold)';
        } elseif ($status === 'outofstock') {
            $conditions[] = 'm.stock <= 0';
        }

        $sql = 'SELECT m.id, m.name, m.description, m.price, m.category, m.image, m.badge,
                       m.stock, m.low_stock_threshold, m.is_active, m.created_by,
                       COALESCE(u.name, "Administrator") AS creator_name,
                       COALESCE(u.role, "admin") AS creator_role
                FROM `menu_items` m
                LEFT JOIN `users` u ON m.created_by = u.id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY m.category, m.name';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    
    public function searchUsers(string $query = '', string $role = '', string $status = ''): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $conditions = ['1=1'];
        $params     = [];

        if ($query !== '') {
            $conditions[] = '(name LIKE :q OR username LIKE :q OR email LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }

        if ($role !== '') {
            $conditions[] = 'role = :role';
            $params[':role'] = $role;
        }

        if ($status !== '') {
            $conditions[] = 'status = :status';
            $params[':status'] = $status;
        }

        $sql = 'SELECT id, username, email, name, phone, role, status, created_at
                FROM `users`
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY role ASC, name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveMenuItems(): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $stmt = $pdo->query('SELECT * FROM `menu_items` WHERE `is_active` = 1 ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function getMenuItemById(int $id): ?array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare('SELECT m.*, u.name AS creator_name, u.role AS creator_role FROM `menu_items` m LEFT JOIN `users` u ON m.created_by = u.id WHERE m.id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function adjustStock(int $id, int $delta): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $isSqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        $sql = $isSqlite
            ? 'UPDATE `menu_items` SET `stock` = MAX(0, CAST(`stock` AS INTEGER) + :delta) WHERE `id` = :id'
            : 'UPDATE `menu_items` SET `stock` = GREATEST(0, CAST(`stock` AS SIGNED) + :delta) WHERE `id` = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':delta', $delta, PDO::PARAM_INT);
        $stmt->bindValue(':id',    $id, PDO::PARAM_INT);
        $stmt->execute();

        $fetchStmt = $pdo->prepare('SELECT `name`, `stock`, `low_stock_threshold` FROM `menu_items` WHERE `id` = :id LIMIT 1');
        $fetchStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $fetchStmt->execute();
        $item = $fetchStmt->fetch();

        if ($item) {
            return [
                'success'   => true,
                'name'      => $item['name'],
                'stock'     => (int)$item['stock'],
                'threshold' => (int)$item['low_stock_threshold'],
                'message'   => "Stock for {$item['name']} adjusted."
            ];
        }

        return ['success' => false, 'message' => 'Item not found'];
    }

    public function updateStock(int $id, int $stock, int $threshold): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('UPDATE `menu_items` SET `stock` = :stock, `low_stock_threshold` = :thresh WHERE `id` = :id');
        $stmt->bindValue(':stock',  max(0, $stock), PDO::PARAM_INT);
        $stmt->bindValue(':thresh', max(0, $threshold), PDO::PARAM_INT);
        $stmt->bindValue(':id',     $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    
    public function addMenuItem(array $data, ?int $createdBy = null): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $sql = 'INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `image`, `badge`, `stock`, `low_stock_threshold`, `created_by`, `is_active`)
                VALUES (:name, :description, :price, :category, :image, :badge, :stock, :thresh, :created_by, 1)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name'        => trim($data['name']),
            ':description' => trim($data['description'] ?? ''),
            ':price'       => (float)($data['price'] ?? 0),
            ':category'    => $data['category'] ?? 'classics',
            ':image'       => !empty($data['image']) ? $data['image'] : 'images/chicken-inasal.jpg',
            ':badge'       => !empty($data['badge']) ? trim($data['badge']) : null,
            ':stock'       => (int)($data['stock'] ?? 0),
            ':thresh'      => (int)($data['low_stock_threshold'] ?? 5),
            ':created_by'  => $createdBy,
        ]);
    }

    
    public function updateMenuItem(int $id, array $data, ?int $currentUserId = null, bool $isAdmin = false): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        $existing = $this->getMenuItemById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        
        if (!$isAdmin) {
            if ($existing['created_by'] === null || (int)$existing['created_by'] !== (int)$currentUserId) {
                return [
                    'success' => false,
                    'message' => 'Access Denied: Staff members can only edit records they created themselves.'
                ];
            }
        }

        $sql = 'UPDATE `menu_items` SET 
                `name` = :name,
                `description` = :description,
                `price` = :price,
                `category` = :category,
                `badge` = :badge,
                `stock` = :stock,
                `low_stock_threshold` = :thresh
                WHERE `id` = :id';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':name'        => trim($data['name']),
            ':description' => trim($data['description'] ?? ''),
            ':price'       => (float)($data['price'] ?? 0),
            ':category'    => $data['category'] ?? 'classics',
            ':badge'       => !empty($data['badge']) ? trim($data['badge']) : null,
            ':stock'       => (int)($data['stock'] ?? 0),
            ':thresh'      => (int)($data['low_stock_threshold'] ?? 5),
            ':id'          => $id,
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? "Dish '{$data['name']}' updated successfully." : 'Failed to update dish.'
        ];
    }

    
    public function deleteMenuItem(int $id, ?int $currentUserId = null, bool $isAdmin = false): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        $existing = $this->getMenuItemById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        
        if (!$isAdmin) {
            if ($existing['created_by'] === null || (int)$existing['created_by'] !== (int)$currentUserId) {
                return [
                    'success' => false,
                    'message' => 'Access Denied: Staff members can only delete records they created themselves.'
                ];
            }
        }

        $stmt = $pdo->prepare('DELETE FROM `menu_items` WHERE `id` = :id');
        $ok = $stmt->execute([':id' => $id]);

        return [
            'success' => $ok,
            'message' => $ok ? "Record '{$existing['name']}' removed successfully." : 'Failed to delete record.'
        ];
    }

    
    
    

    public function getDashboardStats(?int $staffId = null): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return [
                'total_users'        => 0,
                'total_staff'        => 0,
                'total_admins'       => 0,
                'total_records'      => 0,
                'staff_records'      => 0,
                'total_orders'       => 0,
                'total_revenue'      => 0.0,
                'total_reservations' => 0,
                'low_stock_items'    => 0,
                'recent_orders'      => [],
            ];
        }

        $userCount   = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'user'")->fetchColumn();
        $staffCount  = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'staff'")->fetchColumn();
        $adminCount  = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();
        $recordCount = (int)$pdo->query("SELECT COUNT(*) FROM `menu_items`")->fetchColumn();

        $staffRecords = 0;
        if ($staffId !== null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `menu_items` WHERE `created_by` = :sid");
            $stmt->execute([':sid' => $staffId]);
            $staffRecords = (int)$stmt->fetchColumn();
        }

        $orderCount = (int)$pdo->query('SELECT COUNT(*) FROM `orders`')->fetchColumn();
        $revenue    = (float)$pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM `orders`')->fetchColumn();
        $resCount   = (int)$pdo->query('SELECT COUNT(*) FROM `reservations`')->fetchColumn();
        $lowStock   = (int)$pdo->query('SELECT COUNT(*) FROM `menu_items` WHERE stock <= low_stock_threshold')->fetchColumn();

        $recentOrdersStmt = $pdo->query('SELECT id, order_number, customer_name, customer_phone, order_type, total_amount, status, created_at FROM `orders` ORDER BY created_at DESC LIMIT 6');
        $recentOrders = $recentOrdersStmt->fetchAll();

        return [
            'total_users'        => $userCount,
            'total_staff'        => $staffCount,
            'total_admins'       => $adminCount,
            'total_records'      => $recordCount,
            'staff_records'      => $staffRecords,
            'total_orders'       => $orderCount,
            'total_revenue'      => $revenue,
            'total_reservations' => $resCount,
            'low_stock_items'    => $lowStock,
            'recent_orders'      => $recentOrders,
        ];
    }

    
    
    

    public function getOrders(?string $status = null): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        if ($status && $status !== 'all') {
            $stmt = $pdo->prepare('SELECT * FROM `orders` WHERE `status` = :status ORDER BY created_at DESC');
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $pdo->query('SELECT * FROM `orders` ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public function getOrderById(int $id): ?array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare('SELECT * FROM `orders` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function getOrderItems(int $orderId): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $stmt = $pdo->prepare('SELECT * FROM `order_items` WHERE `order_id` = :oid');
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll();
    }

    public function updateOrderStatus(int $id, string $status): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('UPDATE `orders` SET `status` = :status WHERE `id` = :id');
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function deleteOrder(int $id): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $pdo->prepare('DELETE FROM `order_items` WHERE `order_id` = :id')->execute([':id' => $id]);
        $stmt = $pdo->prepare('DELETE FROM `orders` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function placeOrder(array $orderData, array $cartItems): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $pdo->beginTransaction();

            $orderNumber = 'BT-' . strtoupper(substr(uniqid(), -6));
            $sql = 'INSERT INTO `orders` (`order_number`, `user_id`, `customer_name`, `customer_phone`, `order_type`, `table_number`, `delivery_address`, `payment_method`, `subtotal`, `delivery_fee`, `total_amount`, `special_notes`, `status`)
                    VALUES (:num, :uid, :name, :phone, :type, :tbl, :addr, :pay, :sub, :fee, :total, :notes, "pending")';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':num'   => $orderNumber,
                ':uid'   => $orderData['user_id'] ?? null,
                ':name'  => $orderData['customer_name'],
                ':phone' => $orderData['customer_phone'] ?? '',
                ':type'  => $orderData['order_type'] ?? 'dinein',
                ':tbl'   => $orderData['table_number'] ?? null,
                ':addr'  => $orderData['delivery_address'] ?? null,
                ':pay'   => $orderData['payment_method'] ?? 'Cash',
                ':sub'   => (float)($orderData['subtotal'] ?? 0),
                ':fee'   => (float)($orderData['delivery_fee'] ?? 0),
                ':total' => (float)($orderData['total_amount'] ?? 0),
                ':notes' => $orderData['special_notes'] ?? null,
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $itemSql = 'INSERT INTO `order_items` (`order_id`, `item_id`, `item_name`, `unit_price`, `quantity`, `addons`, `subtotal`)
                        VALUES (:oid, :iid, :name, :price, :qty, :addons, :sub)';
            $itemStmt = $pdo->prepare($itemSql);

            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    ':oid'    => $orderId,
                    ':iid'    => $item['id'] ?? null,
                    ':name'   => $item['name'],
                    ':price'  => (float)($item['price'] ?? 0),
                    ':qty'    => (int)($item['quantity'] ?? 1),
                    ':addons' => !empty($item['addons']) ? json_encode($item['addons']) : null,
                    ':sub'    => (float)($item['subtotal'] ?? ($item['price'] * ($item['quantity'] ?? 1))),
                ]);
            }

            $pdo->commit();
            return [
                'success'      => true,
                'order_id'     => $orderId,
                'order_number' => $orderNumber,
                'message'      => "Order {$orderNumber} placed successfully!"
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getReservations(?string $status = null): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        if ($status && $status !== 'all') {
            $stmt = $pdo->prepare('SELECT * FROM `reservations` WHERE `status` = :status ORDER BY reservation_date ASC, reservation_time ASC');
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $pdo->query('SELECT * FROM `reservations` ORDER BY reservation_date ASC, reservation_time ASC');
        }
        return $stmt->fetchAll();
    }

    public function updateReservationStatus(int $id, string $status): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('UPDATE `reservations` SET `status` = :status WHERE `id` = :id');
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function deleteReservation(int $id): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('DELETE FROM `reservations` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function createReservation(array $data): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $sql = 'INSERT INTO `reservations` (`user_id`, `name`, `phone`, `reservation_date`, `reservation_time`, `guests`, `seating`, `special_notes`, `status`)
                VALUES (:uid, :name, :phone, :date, :time, :guests, :seating, :notes, "pending")';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':uid'     => $data['user_id'] ?? null,
            ':name'    => $data['name'],
            ':phone'   => $data['phone'],
            ':date'    => $data['date'],
            ':time'    => $data['time'],
            ':guests'  => $data['guests'],
            ':seating' => $data['seating'] ?? 'Indoor Main Dining',
            ':notes'   => $data['notes'] ?? null,
        ]);

        return [
            'success' => $ok,
            'id'      => $ok ? (int)$pdo->lastInsertId() : 0,
            'message' => $ok ? 'Table reservation confirmed!' : 'Failed to save reservation.'
        ];
    }

    public function getMessages(?bool $unreadOnly = null): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        if ($unreadOnly === true) {
            $stmt = $pdo->query('SELECT * FROM `contacts` WHERE `is_read` = 0 ORDER BY created_at DESC');
        } else {
            $stmt = $pdo->query('SELECT * FROM `contacts` ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    public function markMessageRead(int $id): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('UPDATE `contacts` SET `is_read` = 1 WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function deleteMessage(int $id): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('DELETE FROM `contacts` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function createContactMessage(array $data): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $sql = 'INSERT INTO `contacts` (`user_id`, `name`, `email`, `subject`, `message`, `is_read`)
                VALUES (:uid, :name, :email, :subject, :msg, 0)';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':uid'     => $data['user_id'] ?? null,
            ':name'    => $data['name'],
            ':email'   => $data['email'],
            ':subject' => $data['subject'] ?? 'General Inquiry',
            ':msg'     => $data['message'],
        ]);

        return [
            'success' => $ok,
            'id'      => $ok ? (int)$pdo->lastInsertId() : 0,
            'message' => $ok ? 'Thank you! Your message has been received.' : 'Failed to send message.'
        ];
    }

    
    
    

    public function getUsers(): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return [];

        $stmt = $pdo->query('SELECT id, username, email, name, phone, role, status, created_at FROM `users` ORDER BY role ASC, name ASC');
        return $stmt->fetchAll();
    }

    public function getUserById(int $id): ?array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare('SELECT id, username, email, name, phone, role, status, created_at FROM `users` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getUserByUsernameOrEmail(string $identifier): ?array
    {
        $pdo = $this->getConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare('SELECT * FROM `users` WHERE (`username` = :username OR `email` = :email) LIMIT 1');
        $stmt->execute([':username' => $identifier, ':email' => $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getUserEntity(string $identifier): ?\App\Entity\User
    {
        $data = $this->getUserByUsernameOrEmail($identifier);
        return $data ? (new \App\Entity\User())->fromArray($data) : null;
    }

    
    public function createUser(array $data): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        $username = trim($data['username'] ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));

        if (empty($username) || empty($email)) {
            return ['success' => false, 'message' => 'Username and Email are required.'];
        }

        
        $stmt = $pdo->prepare('SELECT id FROM `users` WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or Email is already taken.'];
        }

        $hash   = password_hash($data['password'], PASSWORD_DEFAULT);
        $role   = in_array($data['role'] ?? 'user', ['admin', 'staff', 'user'], true) ? $data['role'] : 'user';
        $status = in_array($data['status'] ?? 'active', ['active', 'disabled', 'archived'], true) ? $data['status'] : 'active';

        $insert = $pdo->prepare('INSERT INTO `users` (`username`, `email`, `name`, `password`, `phone`, `role`, `status`) VALUES (:u, :e, :n, :p, :ph, :r, :s)');
        $insert->execute([
            ':u'  => $username,
            ':e'  => $email,
            ':n'  => trim($data['name'] ?? $username),
            ':p'  => $hash,
            ':ph' => trim($data['phone'] ?? ''),
            ':r'  => $role,
            ':s'  => $status,
        ]);

        return [
            'success' => true,
            'id'      => (int)$pdo->lastInsertId(),
            'message' => "Account for '{$username}' created successfully as " . strtoupper($role) . "!"
        ];
    }

    
    public function updateUser(int $id, array $data): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        $existing = $this->getUserById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $username = trim($data['username'] ?? $existing['username']);
        $email    = strtolower(trim($data['email'] ?? $existing['email']));

        
        $check = $pdo->prepare('SELECT id FROM `users` WHERE (username = :u OR email = :e) AND id != :id LIMIT 1');
        $check->execute([':u' => $username, ':e' => $email, ':id' => $id]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'That username or email is already used by another account.'];
        }

        $role   = in_array($data['role'] ?? $existing['role'], ['admin', 'staff', 'user'], true) ? $data['role'] : $existing['role'];
        $status = in_array($data['status'] ?? ($existing['status'] ?? 'active'), ['active', 'disabled', 'archived'], true) ? $data['status'] : 'active';

        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE `users` SET `name` = :n, `username` = :u, `email` = :e, `phone` = :ph, `role` = :r, `status` = :s, `password` = :p WHERE `id` = :id');
            $ok = $stmt->execute([
                ':n'  => trim($data['name'] ?? $existing['name']),
                ':u'  => $username,
                ':e'  => $email,
                ':ph' => trim($data['phone'] ?? $existing['phone']),
                ':r'  => $role,
                ':s'  => $status,
                ':p'  => $hash,
                ':id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE `users` SET `name` = :n, `username` = :u, `email` = :e, `phone` = :ph, `role` = :r, `status` = :s WHERE `id` = :id');
            $ok = $stmt->execute([
                ':n'  => trim($data['name'] ?? $existing['name']),
                ':u'  => $username,
                ':e'  => $email,
                ':ph' => trim($data['phone'] ?? $existing['phone']),
                ':r'  => $role,
                ':s'  => $status,
                ':id' => $id,
            ]);
        }

        return [
            'success' => $ok,
            'message' => $ok ? "User #{$id} updated successfully." : 'Failed to update user.'
        ];
    }

    public function updateUserRole(int $id, string $role): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo || !in_array($role, ['admin', 'staff', 'user'], true)) return false;

        $stmt = $pdo->prepare('UPDATE `users` SET `role` = :role WHERE `id` = :id');
        return $stmt->execute([':role' => $role, ':id' => $id]);
    }

    public function setUserStatus(int $id, string $status): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo || !in_array($status, ['active', 'disabled', 'archived'], true)) return false;

        $stmt = $pdo->prepare('UPDATE `users` SET `status` = :status WHERE `id` = :id');
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    
    public function changeUserPassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $pdo = $this->getConnection();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed.'];
        }

        $stmt = $pdo->prepare('SELECT `password` FROM `users` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($oldPassword, $row['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters long.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE `users` SET `password` = :p WHERE `id` = :id');
        $ok = $update->execute([':p' => $newHash, ':id' => $userId]);

        return [
            'success' => $ok,
            'message' => $ok ? 'Password changed successfully!' : 'Failed to update password.'
        ];
    }

    public function deleteUser(int $id): bool
    {
        $pdo = $this->getConnection();
        if (!$pdo) return false;

        $stmt = $pdo->prepare('DELETE FROM `users` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }
}
