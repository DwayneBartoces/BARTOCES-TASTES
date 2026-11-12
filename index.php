<?php

// Canonical URL cleanup: redirect /index.php to /
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, 'index.php') !== false) {
    $cleanUri = preg_replace('#/index\.php(\?.*)?$#i', '/$1', $requestUri);
    if ($cleanUri === '') {
        $cleanUri = '/';
    }
    header('Location: ' . $cleanUri, true, 301);
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/src/Service/DatabaseService.php';

use App\Service\DatabaseService;

$db = new DatabaseService();
$currentUser  = $_SESSION['user'] ?? null;
$allMenuItems = $db->getActiveMenuItems();
$dbSource     = $db->getDbSource();
$appName      = 'BARTOCES TASTES';
$estYear      = 2026;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bartoces Tastes - Est. 2026 | Authentic Culinary Excellence</title>
    <meta name="description"
        content="Bartoces Tastes Est. 2026 - Taste authentic Filipino classics, flame-grilled specialties, and gourmet dishes crafted with passion. Order online or reserve your table.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">

    <!-- SYMFONY SERVER-SIDE DATA INJECTION (FROM DOCKER MYSQL) -->
    <script>
        window.BARTOCES_SESSION = <?php echo json_encode([
            'loggedIn' => !empty($currentUser),
            'user'     => $currentUser
        ]); ?>;
        window.BARTOCES_MENU = <?php echo json_encode($allMenuItems); ?>;
    </script>
</head>

<body>

    <!-- ADMIN / STAFF MANAGEMENT TOP BAR -->
    <?php if (!empty($currentUser) && $currentUser['role'] === 'admin'): ?>
    <div id="adminTopBar" class="admin-top-bar">
        <div class="admin-bar-inner">
            <div class="admin-status">
                <span class="admin-shield"><i class="fa-solid fa-shield-halved"></i> SYMFONY ADMIN ACTIVE</span>
                <span class="admin-pill" id="adminUserPill"><?php echo htmlspecialchars($currentUser['name']); ?> (Administrator)</span>
            </div>
            <div class="admin-quick-stats">
                <span class="admin-metric"><i class="fa-solid fa-bag-shopping"></i> Total Orders: <strong id="adminOrderCounter">—</strong></span>
                <span class="admin-metric"><i class="fa-solid fa-calendar-check"></i> Reservations: <strong id="adminResCounter">—</strong></span>
                <a href="admin.php" class="btn-clear-demo" title="Open Symfony Admin Portal">
                    <i class="fa-solid fa-gauge-high"></i> Symfony Admin Portal
                </a>
            </div>
        </div>
    </div>
    <?php elseif (!empty($currentUser) && $currentUser['role'] === 'staff'): ?>
    <div id="adminTopBar" class="admin-top-bar" style="border-bottom-color: rgba(91,155,213,0.4); background: rgba(18,24,34,0.96);">
        <div class="admin-bar-inner">
            <div class="admin-status">
                <span class="admin-shield" style="color:var(--blue);"><i class="fa-solid fa-kitchen-set"></i> RESTAURANT STAFF ACTIVE</span>
                <span class="admin-pill" id="adminUserPill" style="border-color:rgba(91,155,213,0.3);"><?php echo htmlspecialchars($currentUser['name']); ?> (Staff)</span>
            </div>
            <div class="admin-quick-stats">
                <a href="staff.php" class="btn-clear-demo" style="border-color:rgba(91,155,213,0.4); color:var(--blue);" title="Open Staff Management Portal">
                    <i class="fa-solid fa-gauge-high"></i> Staff Management Portal
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NOTIFICATION TOAST CONTAINER -->
    <div id="toastContainer" class="toast-container" aria-live="polite"></div>

    <!-- HEADER -->
    <header class="header" id="mainHeader">
        <div class="logo-area">
            <a href="#home" class="logo-link" aria-label="Bartoces Tastes Home">
                <img src="images/logo.png" alt="Bartoces Tastes Logo" class="logo-img">
            </a>
        </div>

        <nav class="navigation" id="navigation">
            <a href="#home" class="nav-link active">HOME</a>
            <a href="#menu" class="nav-link">MENU</a>
            <a href="#about" class="nav-link">ABOUT</a>
            <a href="#contact" class="nav-link">CONTACT</a>
        </nav>

        <div class="header-actions">
            <!-- USER PROFILE & LOGOUT / SIGN IN -->
            <?php if (!empty($currentUser)): ?>
            <div class="user-profile-badge" id="userProfileBadge">
                <div class="user-avatar-wrap">
                    <i class="fa-solid fa-user" id="userRoleIcon"></i>
                </div>
                <div class="user-meta">
                    <span class="user-display-name" id="userNameDisplay"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <span class="user-role-label" id="userRoleLabel">
                        <?php 
                        if ($currentUser['role'] === 'admin') {
                            echo 'Administrator';
                        } elseif ($currentUser['role'] === 'staff') {
                            echo 'Staff';
                        } else {
                            echo 'Customer';
                        }
                        ?>
                    </span>
                </div>
                <a href="logout.php" class="header-logout-btn" id="headerLogoutBtn" title="Sign Out">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
            <?php else: ?>
            <a href="login.php" class="header-signin-btn" id="headerSignInBtn" title="Sign In">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>SIGN IN</span>
            </a>
            <?php endif; ?>

            <!-- CART BUTTON -->
            <button class="cart-btn" id="cartBtn" aria-label="View Cart and Checkout" <?php if (empty($currentUser)): ?>onclick="window.location.href='login.php'; return false;"<?php endif; ?>>
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="cart-label">CART</span>
                <span class="cart-badge" id="cartBadge">0</span>
            </button>

            <!-- ORDER NOW CTA -->
            <button class="order-button" id="headerOrderBtn" <?php if (empty($currentUser)): ?>onclick="window.location.href='login.php'; return false;"<?php endif; ?>>
                ORDER NOW
            </button>

            <!-- MOBILE HAMBURGER TOGGLE -->
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation menu">
                <i class="fa-solid fa-bars" id="menuToggleIcon"></i>
            </button>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero" id="home">
        <img src="images/hero-bg.jpg" class="hero-background" alt="Bartoces Tastes luxury restaurant kitchen">

        <div class="hero-overlay"></div>

        <div class="hero-content">
            <div class="hero-brand-tag">
                <span class="arch-icon"><i class="fa-solid fa-utensils"></i></span>
                <span class="tag-line">FINE CASUAL DINING</span>
            </div>

            <h1>BARTOCES TASTES</h1>
            <p class="hero-subtext">EST 2026</p>
            <p class="hero-tagline">“Made with Love, Crafted with Flavor”</p>

            <div class="hero-buttons">
                <button class="dark-button btn-view-menu" id="heroViewMenuBtn">
                    <i class="fa-solid fa-book-open"></i> VIEW MENU
                </button>

                <button class="dark-button btn-order-hero" id="heroOrderBtn" <?php if (empty($currentUser)): ?>onclick="window.location.href='login.php'; return false;"<?php endif; ?>>
                    <i class="fa-solid fa-utensils"></i> ORDER NOW
                </button>
            </div>
        </div>

        <div class="hero-chef-wrapper">
            <div class="chef-badge">
                <span class="chef-badge-star">★ 4.9</span>
                <span class="chef-badge-name">Executive Chef • Bartoces Tastes</span>
            </div>
        </div>
    </section>

    <!-- BEST SELLERS -->
    <section class="best-sellers" id="best-sellers">
        <div class="section-title">
            <span>OUR BEST SELLERS</span>
            <p class="section-subtitle">The crowd favorites perfected with our signature 2026 recipes</p>
        </div>

        <div class="products" id="bestSellersContainer">
            <?php 
            $bestSellers = array_filter($allMenuItems, function($item) {
                $badge = strtolower($item['badge'] ?? '');
                return in_array($badge, ['bestseller', 'signature', 'hot and sizzling', 'hot & sizzling', 'chef choice', "chef's pick", 'popular', 'specialty']);
            });
            if (empty($bestSellers)) {
                $bestSellers = array_slice($allMenuItems, 0, 4);
            } else {
                $bestSellers = array_slice($bestSellers, 0, 8);
            }
            foreach ($bestSellers as $item): 
                $itemId   = (int)$item['id'];
                $name     = htmlspecialchars($item['name']);
                $price    = number_format((float)$item['price'], 0);
                $cat      = htmlspecialchars($item['category']);
                $img      = htmlspecialchars(!empty($item['image']) ? $item['image'] : 'images/chef.png');
                $badge    = htmlspecialchars($item['badge'] ?? '');
                $desc     = htmlspecialchars($item['description'] ?? '');
                $stockVal = (int)($item['stock'] ?? 0);
                $thresh   = (int)($item['low_stock_threshold'] ?? 5);
                $isOut    = $stockVal <= 0;
            ?>
            <div class="product-card" data-id="<?php echo $itemId; ?>" data-name="<?php echo $name; ?>" data-price="<?php echo (float)$item['price']; ?>" data-category="<?php echo $cat; ?>" data-image="<?php echo $img; ?>" data-desc="<?php echo $desc; ?>" data-stock="<?php echo $stockVal; ?>">
                <div class="card-image-wrap">
                    <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" onerror="this.src='images/chef.png'">
                    <?php if ($isOut): ?>
                    <span class="badge-tag" style="background:#dc2626;color:#fff;">Out of Stock</span>
                    <?php elseif (!empty($badge)): ?>
                    <span class="badge-tag"><?php echo $badge; ?></span>
                    <?php endif; ?>
                    <button class="quick-view-btn" data-action="quickview" aria-label="Quick View <?php echo $name; ?>">
                        <i class="fa-regular fa-eye"></i> Quick View
                    </button>
                </div>
                <div class="product-info">
                    <h3><?php echo $name; ?></h3>
                    <p class="price">₱<?php echo $price; ?></p>
                    <?php if (!$isOut && $stockVal <= $thresh): ?>
                    <small style="color:#d97706;font-weight:600;font-size:11px;"><i class="fa-solid fa-triangle-exclamation"></i> Only <?php echo $stockVal; ?> left in stock</small>
                    <?php endif; ?>
                    <div class="stars">★★★★★ <span class="rating-num">(4.9)</span></div>
                    <p class="item-desc"><?php echo $desc; ?></p>
                    <button class="btn-add-cart" data-action="add-to-cart" <?php if ($isOut): ?>style="background:#6b7280;cursor:not-allowed;"<?php endif; ?>>
                        <?php if ($isOut): ?>
                        <i class="fa-solid fa-ban"></i> Out of Stock
                        <?php else: ?>
                        <i class="fa-solid fa-plus"></i> Add to Order
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- STATISTICS -->
    <section class="statistics" id="statistics">
        <div class="stat">
            <strong class="counter" data-target="5000">0</strong><span class="stat-plus">+</span>
            <span class="stat-label">HAPPY CUSTOMERS</span>
        </div>

        <div class="stat">
            <strong class="stat-highlight"><i class="fa-solid fa-award"></i> QUALITY</strong>
            <span class="stat-label">GOURMET FOOD</span>
        </div>

        <div class="stat">
            <strong><span class="counter" data-target="4.5" data-decimal="1">0</span> <i
                    class="fa-solid fa-star text-gold"></i></strong>
            <span class="stat-label">AVERAGE RATING</span>
        </div>

        <div class="stat">
            <strong class="counter" data-target="10">0</strong><span class="stat-plus">+</span>
            <span class="stat-label">PROFESSIONAL CHEFS</span>
        </div>
    </section>

    <!-- MENU SECTION -->
    <section class="menu-section" id="menu">
        <div class="section-title">
            <span>OUR MENU</span>
            <p class="section-subtitle">Handcrafted dishes cooked fresh to order every day</p>
        </div>

        <!-- CATEGORY FILTER BUTTONS -->
        <div class="menu-filter-bar">
            <button class="filter-pill active" data-filter="all">All Items</button>
            <button class="filter-pill" data-filter="classics">Filipino Classics</button>
            <button class="filter-pill" data-filter="fastfood">Snacks & Sides</button>
            <button class="filter-pill" data-filter="pasta">Pizza & Pasta</button>
        </div>

        <div class="menu-container">
            <div class="menu-items" id="menuItemsGrid">
                <?php if (empty($allMenuItems)): ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 40px;">No menu items available at the moment.</p>
                <?php else: ?>
                <?php foreach ($allMenuItems as $item): 
                    $itemId   = (int)$item['id'];
                    $name     = htmlspecialchars($item['name']);
                    $price    = number_format((float)$item['price'], 0);
                    $cat      = htmlspecialchars($item['category']);
                    $img      = htmlspecialchars(!empty($item['image']) ? $item['image'] : 'images/chef.png');
                    $badge    = htmlspecialchars($item['badge'] ?? '');
                    $desc     = htmlspecialchars($item['description'] ?? '');
                    $stockVal = (int)($item['stock'] ?? 0);
                    $thresh   = (int)($item['low_stock_threshold'] ?? 5);
                    $isOut    = $stockVal <= 0;
                ?>
                <div class="menu-card" data-id="<?php echo $itemId; ?>" data-name="<?php echo $name; ?>" data-price="<?php echo (float)$item['price']; ?>" data-category="<?php echo $cat; ?>" data-image="<?php echo $img; ?>" data-desc="<?php echo $desc; ?>" data-stock="<?php echo $stockVal; ?>">
                    <div class="card-image-wrap">
                        <img src="<?php echo $img; ?>" alt="<?php echo $name; ?>" loading="lazy" onerror="this.src='images/chef.png'">
                        <?php if ($isOut): ?>
                        <span class="badge-tag" style="background:#dc2626;color:#fff;">Out of Stock</span>
                        <?php elseif (!empty($badge)): ?>
                        <span class="badge-tag"><?php echo $badge; ?></span>
                        <?php endif; ?>
                        <button class="quick-view-btn" data-action="quickview" aria-label="Quick View <?php echo $name; ?>">
                            <i class="fa-regular fa-eye"></i> View
                        </button>
                    </div>
                    <div class="menu-card-body">
                        <h3><?php echo strtoupper($name); ?></h3>
                        <p class="price">₱<?php echo $price; ?></p>
                        <?php if (!$isOut && $stockVal <= $thresh): ?>
                        <small style="color:#d97706;font-weight:600;font-size:11px;"><i class="fa-solid fa-triangle-exclamation"></i> Only <?php echo $stockVal; ?> left</small>
                        <?php endif; ?>
                        <div class="stars">★★★★★</div>
                        <button class="btn-card-order" data-action="add-to-cart" <?php if ($isOut): ?>style="background:#6b7280;cursor:not-allowed;"<?php endif; ?>>
                            <?php if ($isOut): ?>
                            <i class="fa-solid fa-ban"></i> Out of Stock
                            <?php else: ?>
                            <i class="fa-solid fa-cart-plus"></i> Order
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- FEATURED FOOD (CHEF'S RECOMMENDATION) -->
            <div class="featured-food" id="featuredFoodCard" data-id="8" data-name="Grilled Chicken Inasal"
                data-price="249" data-image="images/grilled-chicken.jpg">
                <div class="featured-information">
                    <small class="featured-tag">
                        <i class="fa-solid fa-fire"></i> CHEF'S RECOMMENDATION
                    </small>

                    <h2>GRILLED CHICKEN INASAL</h2>

                    <div class="stars">
                        ★★★★★ <span class="rating-badge">4.9 / 5.0</span>
                    </div>

                    <p class="featured-price">
                        ₱249
                    </p>

                    <p class="featured-description">
                        A True Filipino Classic, bringing comfort to every bite. Flame-grilled over charcoal embers with
                        our secret lemongrass achiote baste.
                    </p>

                    <div class="featured-actions">
                        <button class="featured-button" id="featuredOrderBtn" data-action="order-featured">
                            <i class="fa-solid fa-bag-shopping"></i> ORDER NOW
                        </button>
                        <button class="featured-details-btn" id="featuredQuickViewBtn" data-action="quickview-featured">
                            <i class="fa-regular fa-circle-info"></i> Details
                        </button>
                    </div>
                </div>

                <div class="featured-image-box">
                    <img src="images/grilled-chicken.jpg" alt="Charcoal Grilled Chicken Inasal" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- OUR STORY -->
    <section class="story-section" id="about">
        <div class="story-image">
            <img src="images/restaurant.jpg" alt="Bartoces Tastes Restaurant Ambiance" loading="lazy">
            <div class="story-image-overlay">
                <span>ESTABLISHED 2026</span>
            </div>
        </div>

        <div class="story-content">
            <div class="section-title left-title">
                <span>OUR STORY</span>
            </div>

            <h3>Where Every Meal Feels Like Coming Home</h3>

            <p>
                Founded in 2026, <strong>Bartoces Tastes</strong> began with a simple passion for honest, homemade food
                cooked with heartwarming heritage recipes. Today, we continue to serve fresh, flavorful meals made with
                utmost care, creating memorable dining experiences for every guest.
            </p>

            <p>
                From our signature charcoal-grilled chicken inasal to rich sizzling sisig and handmade pastas, every
                plate is prepared with locally sourced ingredients, authentic herbs, and an unwavering commitment to
                culinary excellence.
            </p>

            <div class="story-cta">
                <button class="btn-reservation" id="storyReservationBtn">
                    <i class="fa-regular fa-calendar-check"></i> RESERVE A TABLE
                </button>
                <a href="#menu" class="btn-view-story-menu">
                    EXPLORE MENU <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials" id="testimonials">
        <div class="section-title">
            <span>TESTIMONIALS</span>
            <p class="section-subtitle">Real words from our beloved diners</p>
        </div>

        <div class="testimonial-container">
            <div class="testimonial">
                <div class="quote">“</div>
                <p>
                    Highly Rated, Deliciously Crafted, and Made to Satisfy Every Craving. The Chicken Inasal is
                    unquestionably the best in town!
                </p>
                <div class="testimonial-author">
                    <strong>Maria Santos</strong>
                    <span>Verified Guest ★★★★★</span>
                </div>
            </div>

            <div class="testimonial">
                <div class="quote">“</div>
                <p>
                    Every Bite Is Packed With Flavor, Quality, and the Taste Our Family Loves. Sizzling Sisig and
                    Carbonara are must-orders!
                </p>
                <div class="testimonial-author">
                    <strong>Carlos Ramirez</strong>
                    <span>Food Enthusiast ★★★★★</span>
                </div>
            </div>

            <div class="testimonial">
                <div class="quote">“</div>
                <p>
                    Five-Star Flavor in Every Bite, Carefully Prepared to Give You a Truly Satisfying Meal. Outstanding
                    service and cozy ambiance.
                </p>
                <div class="testimonial-author">
                    <strong>David Chen</strong>
                    <span>Regular Diner ★★★★★</span>
                </div>
            </div>
        </div>
    </section>

    <!-- TEAM -->
    <section class="team" id="team">
        <div class="team-title-wrap">
            <span class="team-caption">MEET OUR MASTER CHEFS</span>
        </div>
        <div class="team-grid">
            <div class="team-member">
                <img src="images/chef1.jpg" alt="Chef Kenji Tanaka" loading="lazy">
                <h4>Chef Kenji</h4>
                <span>Executive Chef</span>
            </div>

            <div class="team-member">
                <img src="images/chef2.jpg" alt="Chef Marcus Vance" loading="lazy">
                <h4>Chef Marcus</h4>
                <span>Sous Chef</span>
            </div>

            <div class="team-member">
                <img src="images/chef3.jpg" alt="Chef Priya Patel" loading="lazy">
                <h4>Chef Priya</h4>
                <span>Pastry & Grill Specialist</span>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer" id="contact">
        <div class="footer-brand">
            <img src="images/logo-light.png" alt="Bartoces Tastes Logo" class="footer-logo">

            <div class="footer-brand-info">
                <h2>BARTOCES TASTES</h2>
                <p class="footer-est">EST 2026</p>
                <small class="footer-quote">
                    "Made with Love, Made with Flavor."
                </small>
                <div class="social-links">
                    <span class="social-badge" aria-label="Facebook"><i
                            class="fa-brands fa-facebook-f"></i></span>
                    <span class="social-badge" aria-label="Instagram"><i
                            class="fa-brands fa-instagram"></i></span>
                    <span class="social-badge" aria-label="TikTok"><i
                            class="fa-brands fa-tiktok"></i></span>
                </div>
            </div>
        </div>

        <div class="footer-column">
            <h3>QUICK LINKS</h3>
            <a href="#home">HOME</a>
            <a href="#menu">MENU</a>
            <a href="#about">ABOUT</a>
            <a href="#contact">CONTACT</a>
            <button class="footer-text-btn" id="footerReserveLink">RESERVATIONS</button>
        </div>

        <div class="footer-column">
            <h3>OUR MENU</h3>
            <button class="footer-text-btn" data-menu-target="5">BURGER</button>
            <button class="footer-text-btn" data-menu-target="6">FRIES</button>
            <button class="footer-text-btn" data-menu-target="7">PIZZA</button>
            <button class="footer-text-btn" data-menu-target="1">CHICKEN INASAL</button>
        </div>

        <div class="footer-column">
            <h3>SERVICES</h3>
            <button class="footer-service-btn" id="serviceDineInBtn">
                <i class="fa-solid fa-champagne-glasses"></i> DINE IN
            </button>
            <button class="footer-service-btn" id="serviceTakeOutBtn">
                <i class="fa-solid fa-box-archive"></i> TAKE OUT
            </button>
            <button class="footer-service-btn" id="serviceDeliveryBtn">
                <i class="fa-solid fa-motorcycle"></i> DELIVERY
            </button>
        </div>

        <div class="footer-column">
            <h3>CONTACTS</h3>
            <p>
                <i class="fa-solid fa-phone"></i>
                <span class="contact-text">0912 345 6789</span>
            </p>
            <p>
                <i class="fa-solid fa-envelope"></i>
                <span class="contact-text">bartocestastes@email.com</span>
            </p>
            <p>
                <i class="fa-solid fa-location-dot"></i>
                <span>Main St., Culinary Plaza, 2026</span>
            </p>
            <button class="footer-contact-badge" id="openContactModalBtn">
                <i class="fa-regular fa-paper-plane"></i> Send a Message
            </button>
        </div>
    </footer>

    <div class="footer-bottom">
        <p>© 2026 Bartoces Tastes. All Rights Reserved. Powered by Symfony 6.4 & Docker MySQL.</p>
    </div>

    <!-- ==============================================
         MODALS & SLIDE-OUT DRAWERS
    =============================================== -->

    <!-- BACKDROP OVERLAY -->
    <div id="modalBackdrop" class="modal-backdrop"></div>

    <!-- 1. CART & CHECKOUT SLIDE-OVER DRAWER -->
    <aside id="cartDrawer" class="cart-drawer" aria-label="Shopping Cart">
        <div class="drawer-header">
            <div class="drawer-title">
                <i class="fa-solid fa-bag-shopping"></i>
                <h3>YOUR ORDER</h3>
                <span class="drawer-item-count" id="drawerItemCount">(0 items)</span>
            </div>
            <button class="close-drawer-btn" id="closeCartBtn" aria-label="Close cart">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- ORDER TYPE SELECTOR (Dine In / Take Out / Delivery) -->
        <div class="order-type-selector">
            <button class="order-type-tab active" data-type="dinein">
                <i class="fa-solid fa-utensils"></i> Dine In
            </button>
            <button class="order-type-tab" data-type="takeout">
                <i class="fa-solid fa-bag-shopping"></i> Take Out
            </button>
            <button class="order-type-tab" data-type="delivery">
                <i class="fa-solid fa-motorcycle"></i> Delivery
            </button>
        </div>

        <!-- CART ITEMS LIST -->
        <div class="cart-items-container" id="cartItemsList">
            <!-- Rendered by JS: empty state or items -->
        </div>

        <!-- CART FOOTER & CHECKOUT -->
        <div class="cart-footer" id="cartFooter">
            <div class="order-type-dynamic-fields" id="orderTypeFields">
                <!-- Injected dynamically based on type -->
            </div>

            <div class="cart-special-notes">
                <label for="orderNotes">Special Instructions / Requests</label>
                <input type="text" id="orderNotes" placeholder="e.g., extra sauce, allergy info, cutlery">
            </div>

            <div class="bill-summary">
                <div class="bill-row">
                    <span>Subtotal</span>
                    <span id="billSubtotal">₱0</span>
                </div>
                <div class="bill-row" id="deliveryFeeRow" style="display: none;">
                    <span>Delivery Fee</span>
                    <span id="billDeliveryFee">₱49</span>
                </div>
                <div class="bill-row total-row">
                    <strong>Total Amount</strong>
                    <strong id="billTotal" class="bill-total-price">₱0</strong>
                </div>
            </div>

            <!-- CUSTOMER DETAILS FORM -->
            <form id="checkoutForm" class="checkout-form">
                <div class="form-group">
                    <input type="text" id="custName" placeholder="Your Full Name *" required>
                </div>
                <div class="form-group" id="deliveryAddressGroup" style="display: none;">
                    <textarea id="custAddress" placeholder="Delivery Street Address & Landmarks *" rows="2"></textarea>
                </div>

                <div class="payment-method-box">
                    <label>Payment Method:</label>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="Cash / Cash on Delivery" checked>
                            <span><i class="fa-solid fa-money-bill-wave"></i> Cash</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="GCash">
                            <span><i class="fa-solid fa-mobile-screen-button"></i> GCash</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="Credit / Debit Card">
                            <span><i class="fa-solid fa-credit-card"></i> Card</span>
                        </label>
                    </div>
                </div>

                <!-- CARD DETAILS -->
                <div id="cardDetailsGroup" class="payment-details-box" style="display: none;">
                    <div class="form-group">
                        <label class="field-micro-label"><i class="fa-solid fa-credit-card"></i> Card Number *</label>
                        <input type="text" id="custCard" placeholder="1234 5678 9012 3456" inputmode="numeric" pattern="[0-9 ]*" maxlength="19" autocomplete="cc-number">
                    </div>
                    <div class="form-row-split">
                        <div class="form-group">
                            <label class="field-micro-label"><i class="fa-solid fa-calendar-days"></i> Expiry (MM/YY) *</label>
                            <input type="text" id="custCardExp" placeholder="MM/YY" inputmode="numeric" maxlength="5" autocomplete="cc-exp">
                        </div>
                        <div class="form-group">
                            <label class="field-micro-label"><i class="fa-solid fa-lock"></i> CVV / CVC *</label>
                            <input type="password" id="custCardCvv" placeholder="123" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="cc-csc">
                        </div>
                    </div>
                </div>

                <!-- GCASH DETAILS -->
                <div id="gcashDetailsGroup" class="payment-details-box" style="display: none;">
                    <div class="form-group">
                        <label class="field-micro-label"><i class="fa-solid fa-mobile-screen-button"></i> GCash Mobile Number *</label>
                        <input type="tel" id="custGcashPhone" placeholder="09XXXXXXXXX" inputmode="numeric" maxlength="11">
                    </div>
                </div>

                <button type="submit" class="btn-confirm-order" id="btnConfirmOrder">
                    <i class="fa-solid fa-lock"></i> PLACE ORDER NOW
                </button>
            </form>
        </div>
    </aside>

    <!-- 2. ORDER RECEIPT CONFIRMATION MODAL -->
    <div id="receiptModal" class="custom-modal" role="dialog" aria-modal="true">
        <div class="modal-card receipt-card">
            <button class="modal-close-btn" id="closeReceiptBtn" aria-label="Close receipt">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="receipt-header">
                <div class="success-icon-badge">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3>ORDER CONFIRMED!</h3>
                <p class="receipt-sub">Thank you for dining with Bartoces Tastes</p>
                <div class="order-ref-pill">
                    <span>Order No: </span><strong id="receiptOrderNum">#BT-2026-8821</strong>
                </div>
            </div>

            <div class="receipt-body" id="receiptBody">
                <!-- Rendered dynamically -->
            </div>

            <div class="receipt-footer">
                <button class="btn-done-order" id="btnDoneOrder">
                    Order Again / Done
                </button>
            </div>
        </div>
    </div>

    <!-- 3. TABLE RESERVATION MODAL -->
    <div id="reservationModal" class="custom-modal" role="dialog" aria-modal="true">
        <div class="modal-card">
            <button class="modal-close-btn" id="closeReservationBtn" aria-label="Close reservation">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-header-banner">
                <i class="fa-solid fa-champagne-glasses modal-icon"></i>
                <h3>TABLE RESERVATION</h3>
                <p>Reserve an unforgettable dining experience at Bartoces Tastes</p>
            </div>

            <form id="reservationForm" class="modal-form">
                <div class="form-row">
                    <div class="form-col">
                        <label for="resName">Full Name *</label>
                        <input type="text" id="resName" required placeholder="Juan Dela Cruz">
                    </div>
                    <div class="form-col">
                        <label for="resPhone">Phone Number *</label>
                        <input type="tel" id="resPhone" required placeholder="0912 345 6789">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label for="resDate">Reservation Date *</label>
                        <input type="date" id="resDate" required>
                    </div>
                    <div class="form-col">
                        <label for="resTime">Preferred Time *</label>
                        <select id="resTime" required>
                            <option value="11:30 AM">11:30 AM (Lunch)</option>
                            <option value="12:30 PM">12:30 PM (Lunch)</option>
                            <option value="01:30 PM">01:30 PM (Lunch)</option>
                            <option value="05:30 PM">05:30 PM (Dinner)</option>
                            <option value="06:30 PM" selected>06:30 PM (Dinner)</option>
                            <option value="07:30 PM">07:30 PM (Dinner)</option>
                            <option value="08:30 PM">08:30 PM (Late Dinner)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label for="resGuests">Number of Guests *</label>
                        <select id="resGuests" required>
                            <option value="1 Person">1 Person</option>
                            <option value="2 Persons" selected>2 Persons (Couple)</option>
                            <option value="3-4 Persons">3 - 4 Persons (Small Group)</option>
                            <option value="5-8 Persons">5 - 8 Persons (Family)</option>
                            <option value="9+ Persons">9+ Persons (Celebration / Banquet)</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="resSeating">Seating Preference</label>
                        <select id="resSeating">
                            <option value="Indoor Main Dining">Indoor Dining (Air-conditioned)</option>
                            <option value="Garden Al Fresco">Garden Al Fresco (Open Air)</option>
                            <option value="VIP Booth">VIP Booth (Private)</option>
                        </select>
                    </div>
                </div>

                <div class="form-col full-width">
                    <label for="resNotes">Special Requests / Occasion</label>
                    <textarea id="resNotes" rows="2"
                        placeholder="Birthday celebration, anniversary, high chair needed, etc."></textarea>
                </div>

                <button type="submit" class="btn-modal-submit" id="btnSubmitReservation">
                    <i class="fa-regular fa-calendar-check"></i> Confirm Table Reservation
                </button>
            </form>
        </div>
    </div>

    <!-- 4. FOOD ITEM QUICK VIEW MODAL -->
    <div id="quickViewModal" class="custom-modal" role="dialog" aria-modal="true">
        <div class="modal-card quick-view-card">
            <button class="modal-close-btn" id="closeQuickViewBtn" aria-label="Close dish view">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="quick-view-grid">
                <div class="quick-view-img-box">
                    <img id="qvImage" src="images/chicken-inasal.jpg" alt="Dish photo">
                </div>
                <div class="quick-view-details">
                    <span class="qv-tag" id="qvCategory">Classics</span>
                    <h3 id="qvTitle">Chicken Inasal</h3>
                    <div class="stars" id="qvStars">★★★★★</div>
                    <p class="qv-price" id="qvPrice">₱249</p>
                    <p class="qv-desc" id="qvDesc">Authentic Filipino specialty cooked with traditional spices and fresh
                        ingredients.</p>

                    <div class="qv-options">
                        <label class="qv-opt-title">Add-on Options:</label>
                        <label class="qv-opt-item">
                            <input type="checkbox" id="optExtraRice" data-price="25">
                            <span>Extra Garlic Rice (+₱25)</span>
                        </label>
                        <label class="qv-opt-item">
                            <input type="checkbox" id="optExtraSauce" data-price="15">
                            <span>Signature Dipping Sauce (+₱15)</span>
                        </label>
                        <label class="qv-opt-item">
                            <input type="checkbox" id="optExtraCheese" data-price="35">
                            <span>Melted Cheese Topping (+₱35)</span>
                        </label>
                    </div>

                    <div class="qv-qty-row">
                        <span class="qty-label">Quantity:</span>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" id="qvQtyMinus">-</button>
                            <span class="qty-display" id="qvQtyVal">1</span>
                            <button type="button" class="qty-btn" id="qvQtyPlus">+</button>
                        </div>
                    </div>

                    <button class="btn-qv-add-cart" id="qvAddToCartBtn">
                        <i class="fa-solid fa-cart-plus"></i> Add to Order • <span id="qvBtnPrice">₱249</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. CONTACT US MODAL -->
    <div id="contactModal" class="custom-modal" role="dialog" aria-modal="true">
        <div class="modal-card">
            <button class="modal-close-btn" id="closeContactBtn" aria-label="Close contact modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-header-banner">
                <i class="fa-regular fa-comments modal-icon"></i>
                <h3>SEND US A MESSAGE</h3>
                <p>Have questions about events, catering, or dining? We'd love to hear from you!</p>
            </div>

            <form id="contactForm" class="modal-form">
                <div class="form-row">
                    <div class="form-col">
                        <label for="contactName">Your Name *</label>
                        <input type="text" id="contactName" required placeholder="Your Full Name">
                    </div>
                    <div class="form-col">
                        <label for="contactEmail">Your Email *</label>
                        <input type="email" id="contactEmail" required placeholder="you@example.com">
                    </div>
                </div>
                <div class="form-col full-width">
                    <label for="contactSubject">Subject</label>
                    <input type="text" id="contactSubject" placeholder="General Inquiry / Event Catering / Feedback">
                </div>
                <div class="form-col full-width">
                    <label for="contactMessage">Message *</label>
                    <textarea id="contactMessage" rows="3" required
                        placeholder="Tell us how we can help you..."></textarea>
                </div>
                <button type="submit" class="btn-modal-submit">
                    <i class="fa-regular fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script src="js/script.js?v=<?php echo time(); ?>"></script>

</body>

</html>