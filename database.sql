-- ============================================================
-- BARTOCES TASTES - DATABASE SCHEMA
-- Run this file in phpMyAdmin or MySQL CLI to set up the database
-- ============================================================

CREATE DATABASE IF NOT EXISTS `bartoces_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bartoces_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)  NOT NULL UNIQUE,
    `email`      VARCHAR(120) NOT NULL UNIQUE,
    `name`       VARCHAR(120) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `phone`      VARCHAR(20)  DEFAULT NULL,
    `role`       ENUM('admin','user') NOT NULL DEFAULT 'user',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(120) NOT NULL,
    `description`    TEXT         DEFAULT NULL,
    `price`          DECIMAL(8,2) NOT NULL,
    `category`       VARCHAR(50)  DEFAULT 'classics',
    `image`          VARCHAR(200) DEFAULT NULL,
    `badge`          VARCHAR(60)  DEFAULT NULL,
    `stock`          INT UNSIGNED NOT NULL DEFAULT 0,
    `low_stock_threshold` INT UNSIGNED NOT NULL DEFAULT 5,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number`     VARCHAR(20)  NOT NULL UNIQUE,
    `user_id`          INT UNSIGNED DEFAULT NULL,
    `customer_name`    VARCHAR(120) NOT NULL,
    `customer_phone`   VARCHAR(20)  NOT NULL,
    `order_type`       ENUM('dinein','takeout','delivery') NOT NULL DEFAULT 'dinein',
    `table_number`     VARCHAR(10)  DEFAULT NULL,
    `delivery_address` TEXT         DEFAULT NULL,
    `payment_method`   VARCHAR(50)  NOT NULL DEFAULT 'Cash',
    `subtotal`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `delivery_fee`     DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    `total_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `special_notes`    TEXT         DEFAULT NULL,
    `status`           ENUM('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`   INT UNSIGNED NOT NULL,
    `item_id`    INT UNSIGNED DEFAULT NULL,
    `item_name`  VARCHAR(120) NOT NULL,
    `unit_price` DECIMAL(8,2) NOT NULL,
    `quantity`   INT UNSIGNED NOT NULL DEFAULT 1,
    `addons`     VARCHAR(255) DEFAULT NULL,
    `subtotal`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reservations` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED DEFAULT NULL,
    `name`             VARCHAR(120) NOT NULL,
    `phone`            VARCHAR(20)  NOT NULL,
    `reservation_date` DATE         NOT NULL,
    `reservation_time` VARCHAR(20)  NOT NULL,
    `guests`           VARCHAR(30)  NOT NULL,
    `seating`          VARCHAR(60)  DEFAULT 'Indoor Main Dining',
    `special_notes`    TEXT         DEFAULT NULL,
    `status`           ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contacts` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(120) NOT NULL,
    `subject`    VARCHAR(200) DEFAULT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default accounts (passwords hashed with PHP password_hash)
-- admin123 and user123
INSERT INTO `users` (`username`, `email`, `name`, `password`, `phone`, `role`) VALUES
('admin', 'admin@bartoces.com', 'Executive Chef / Manager',
 '$2y$10$q56w6fZvZSGKzZUs5ae5WOYKhgq7LUiQCJQMv7l7C7g22UQgnpX9i',
 '0912 345 6789', 'admin'),
('user', 'user@bartoces.com', 'Juan Dela Cruz',
 '$2y$10$aPZJN6fCbPR8eFVOnd1CEegsCs/9Ol5i5p3QT8ZNeYrWUeNBSwC9y',
 '0998 765 4321', 'user')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `image`, `badge`, `stock`) VALUES
('Chicken Inasal',    'Achiote-marinated grilled chicken thigh served with garlic rice and calamansi dip.', 249.00, 'classics', 'images/chicken-inasal.jpg', 'Bestseller', 50),
('Beef Steak',        'Tender ribeye slices simmered in citrus soy glaze and caramelized onions.',          399.00, 'classics', 'images/beef-steak.jpg',     'Signature', 30),
('Pork Sisig',        'Crispy diced pork belly served sizzling with fresh egg, chili, and calamansi.',      199.00, 'classics', 'images/pork-sisig.jpg',     'Hot and Sizzling', 40),
('Creamy Carbonara',  'Al dente pasta tossed in rich parmesan egg sauce with crispy smoked bacon.',         250.00, 'pasta',    'images/carbonara.jpg',      'Chef Choice', 35),
('Gourmet Burger',    'Flame-grilled artisan beef patty with melted cheddar and brioche bun.',              120.00, 'fastfood', 'images/burger.jpg',         'Popular', 60),
('Crispy Fries',      'Hand-cut golden russet potato fries dusted with sea salt. Served with garlic aioli.', 99.00, 'fastfood', 'images/fries.jpg',          'Sides', 100),
('Artisan Pizza',     'Woodfired thin-crust pizza with San Marzano tomato, mozzarella, pepperoni and basil.', 300.00, 'pasta',  'images/pizza.jpg',          'Specialty', 45),
('Grilled Chicken Inasal', 'Chef signature charcoal flame-grilled chicken in traditional Ilonggo marinade.', 249.00, 'classics', 'images/grilled-chicken.jpg', 'Chefs Pick', 25),
-- International Burger Varieties
('Bacon Cheeseburger', 'Juicy beef patty topped with crispy bacon, melted cheddar, lettuce, tomato, and special sauce on a toasted bun.', 180.00, 'fastfood', 'images/bacon-cheeseburger.jpg', 'Chef Special', 40),
('Mushroom Swiss Burger', 'Savory beef patty with sautéed mushrooms, melted Swiss cheese, caramelized onions, and garlic aioli on a brioche bun.', 185.00, 'fastfood', 'images/mushroom-swiss-burger.jpg', 'Gourmet', 35),
('Spicy Chicken Burger', 'Crispy chicken fillet with pepper jack cheese, lettuce, tomato, and spicy mayo on a toasted bun.', 160.00, 'fastfood', 'images/spicy-chicken-burger.jpg', 'Spicy', 45),
-- International Pasta Varieties
('Spicy Arrabbiata', 'Penne pasta in a spicy tomato sauce with garlic, chili flakes, and fresh basil, topped with parmesan.', 220.00, 'pasta', 'images/spicy-arrabbiata.jpg', 'Spicy', 30),
('Mushroom Alfredo', 'Fettuccine in a creamy Alfredo sauce with sautéed mushrooms, garlic, and parmesan cheese.', 240.00, 'pasta', 'images/mushroom-alfredo.jpg', 'Chef Choice', 25),
('Seafood Linguine', 'Linguine with shrimp, mussels, clams in a white wine garlic sauce with cherry tomatoes and parsley.', 280.00, 'pasta', 'images/seafood-linguine.jpg', 'Specialty', 20),
-- Asian Cuisine
('Beef Teriyaki Bowl', 'Grilled beef strips with steamed rice, stir-fried vegetables, and homemade teriyaki sauce.', 220.00, 'classics', 'images/beef-teriyaki.jpg', 'Asian', 35),
('Chicken Teriyaki', 'Grilled chicken thigh with steamed rice, stir-fried vegetables, and sweet teriyaki glaze.', 195.00, 'classics', 'images/chicken-teriyaki.jpg', 'Asian', 40),
('Vegetable Stir Fry', 'Mixed seasonal vegetables stir-fried with tofu in ginger-garlic sauce, served with steamed rice.', 175.00, 'classics', 'images/vegetable-stir-fry.jpg', 'Healthy', 50)
ON DUPLICATE KEY UPDATE `price` = VALUES(`price`), `stock` = VALUES(`stock`);
