<?php
// ============================================================
// BARTOCES TASTES - LOGIN & REGISTRATION (PHP PDO + MySQL)
// Strictly follows Week 7: PHP Forms, Validation & Database CRUD
// ============================================================
require_once 'config.php';

session_start();

// If already logged in, route by role
if (!empty($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        redirect('admin.php');
    } else {
        redirect('index.php');
    }
}

$error   = '';
$success = '';

// ---- Handle POST (Login or Register per PDF Week 7) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    // ---- LOGIN ----
    if ($action === 'login') {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $role       = sanitize($_POST['role'] ?? 'user');

        if (empty($identifier) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $pdo = db();
            // Prepared statement with named placeholders (PDF Week 7 slide 14, 19)
            $sql = 'SELECT * FROM `users` WHERE (`username` = :identifier OR `email` = :identifier) LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':identifier', $identifier);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid username/email or password.';
            } else {
                // Create session
                $_SESSION['user'] = [
                    'id'       => (int)$user['id'],
                    'name'     => $user['name'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                    'phone'    => $user['phone'] ?? '',
                    'role'     => $user['role'],
                ];
                // Admin sign in redirects to admin portal
                if ($user['role'] === 'admin') {
                    redirect('admin.php');
                } else {
                    redirect('index.php');
                }
            }
        }
    }

    // ---- REGISTER ----
    elseif ($action === 'register') {
        $name     = sanitize($_POST['reg_name']     ?? '');
        $email    = trim($_POST['reg_email']        ?? '');
        $username = sanitize($_POST['reg_username'] ?? '');
        $phone    = sanitize($_POST['reg_phone']    ?? '');
        $password = $_POST['reg_password']          ?? '';

        if (empty($name) || empty($email) || empty($username) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $pdo = db();

            // Check duplicates with prepared statement
            $checkSql = 'SELECT id FROM `users` WHERE `email` = :email OR `username` = :username LIMIT 1';
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindValue(':email',    $email);
            $checkStmt->bindValue(':username', $username);
            $checkStmt->execute();

            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $error = 'Email or username already exists. Please use a different one.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insertSql = 'INSERT INTO `users` (`username`, `email`, `name`, `password`, `phone`, `role`) 
                              VALUES (:username, :email, :name, :password, :phone, "user")';
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->bindValue(':username', $username);
                $insertStmt->bindValue(':email',    $email);
                $insertStmt->bindValue(':name',     $name);
                $insertStmt->bindValue(':password', $hashed);
                $insertStmt->bindValue(':phone',    $phone);

                if ($insertStmt->execute()) {
                    $newId = (int)$pdo->lastInsertId();

                    $_SESSION['user'] = [
                        'id'       => $newId,
                        'name'     => $name,
                        'username' => $username,
                        'email'    => $email,
                        'phone'    => $phone,
                        'role'     => 'user',
                    ];
                    redirect('index.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Sign In - Bartoces Tastes Est. 2026';
$appName   = 'BARTOCES TASTES';
$estYear   = 2026;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Sign in to your Bartoces Tastes account to place orders, make table reservations, or access the admin management portal.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Login Stylesheet -->
    <link rel="stylesheet" href="login.css">
</head>
<body>

    <div class="login-bg"></div>
    <div class="login-overlay"></div>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- BRAND LOGO & TITLE -->
            <div class="login-brand">
                <img src="images/logo.png" alt="<?php echo $appName; ?> Logo" class="login-logo">
                <h1 id="brandTitle">WELCOME TO <?php echo $appName; ?></h1>
                <p id="brandSubtitle">Sign in to order your favorite dishes or reserve a table</p>
            </div>

            <!-- SERVER-SIDE ALERT (PHP error/success) -->
            <?php if ($error): ?>
            <div class="auth-alert auth-alert-error" role="alert" id="phpAlert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="auth-alert auth-alert-success" role="alert">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <!-- JS ALERT BANNER -->
            <div class="auth-alert" id="authAlert" role="alert" style="display:none;">
                <i class="fa-solid fa-circle-exclamation" id="alertIcon"></i>
                <span id="alertMessage"></span>
            </div>

            <!-- 1. SIGN IN FORM -->
            <form id="signInForm" class="auth-form" method="POST" action="login.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="roleField" value="user">

                <div class="form-group">
                    <label for="loginIdentifier">Username or Email</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-user field-icon"></i>
                        <input type="text" id="loginIdentifier" name="identifier"
                               placeholder="e.g. user or user@bartoces.com" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-lock field-icon"></i>
                        <input type="password" id="loginPassword" name="password"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-pwd" id="toggleLoginPwd" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="loginEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit" id="btnSubmitAuth">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span id="submitBtnText">SIGN IN TO DINE</span>
                </button>
            </form>

            <!-- 2. REGISTRATION FORM -->
            <form id="signUpForm" class="auth-form" method="POST" action="login.php" style="display: none;">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label for="regName">Full Name</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-id-badge field-icon"></i>
                        <input type="text" id="regName" name="reg_name" placeholder="e.g. Juan Dela Cruz" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-envelope field-icon"></i>
                        <input type="email" id="regEmail" name="reg_email" placeholder="e.g. juan@example.com" required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regUsername">Username</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-user field-icon"></i>
                        <input type="text" id="regUsername" name="reg_username" placeholder="Choose a username" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regPhone">Mobile Phone (Optional)</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-phone field-icon"></i>
                        <input type="tel" id="regPhone" name="reg_phone" placeholder="0912 345 6789">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-with-icon">
                        <i class="fa-regular fa-lock field-icon"></i>
                        <input type="password" id="regPassword" name="reg_password"
                               placeholder="Minimum 6 characters" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-pwd" id="toggleRegPwd" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="regEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fa-solid fa-user-plus"></i> CREATE ACCOUNT &amp; DINE
                </button>
            </form>

            <!-- TOGGLE LOGIN / SIGN UP -->
            <div class="auth-toggle-mode">
                <span id="toggleModeText">Don't have an account yet?</span>
                <a id="toggleModeLink" style="cursor:pointer;">Create Account</a>
            </div>

        </div>

        <p class="login-footer-note">© <?php echo $estYear; ?> <?php echo $appName; ?> • Fine Casual Dining &amp; Authentic Flavors</p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        let isSignUpMode = false;

        const signInForm      = document.getElementById('signInForm');
        const signUpForm      = document.getElementById('signUpForm');
        const authAlert       = document.getElementById('authAlert');
        const alertMessage    = document.getElementById('alertMessage');
        const brandSubtitle   = document.getElementById('brandSubtitle');
        const submitBtnText   = document.getElementById('submitBtnText');
        const toggleModeText  = document.getElementById('toggleModeText');
        const toggleModeLink  = document.getElementById('toggleModeLink');
        const toggleLoginPwd  = document.getElementById('toggleLoginPwd');
        const loginPassword   = document.getElementById('loginPassword');
        const loginEyeIcon    = document.getElementById('loginEyeIcon');
        const toggleRegPwd    = document.getElementById('toggleRegPwd');
        const regPassword     = document.getElementById('regPassword');
        const regEyeIcon      = document.getElementById('regEyeIcon');

        // If PHP returned an error, show the alert on the correct tab
        const phpAlert = document.getElementById('phpAlert');
        if (phpAlert) {
            authAlert.style.display = 'none'; // PHP one is shown instead
        }

        function showJsAlert(msg, type = 'error') {
            authAlert.style.display = 'flex';
            authAlert.className     = 'auth-alert ' + (type === 'success' ? 'auth-alert-success' : 'auth-alert-error');
            alertMessage.textContent = msg;
        }

        function hideJsAlert() {
            authAlert.style.display = 'none';
        }

        // ---- Sign In / Sign Up Toggle ----
        function toggleMode() {
            isSignUpMode = !isSignUpMode;
            hideJsAlert();

            if (isSignUpMode) {
                signInForm.style.display  = 'none';
                signUpForm.style.display  = 'block';
                toggleModeText.textContent = 'Already have an account?';
                toggleModeLink.textContent = 'Sign In';
                brandSubtitle.textContent  = 'Create a new customer account to start ordering';
                submitBtnText.textContent  = 'CREATE ACCOUNT & DINE';
            } else {
                signInForm.style.display  = 'block';
                signUpForm.style.display  = 'none';
                toggleModeText.textContent = "Don't have an account yet?";
                toggleModeLink.textContent = 'Create Account';
                brandSubtitle.textContent  = 'Sign in to order your favorite dishes or reserve a table';
                submitBtnText.textContent  = 'SIGN IN TO DINE';
            }
        }

        toggleModeLink.addEventListener('click', toggleMode);

        // ---- Password Visibility Toggles ----
        toggleLoginPwd.addEventListener('click', () => {
            const isText = loginPassword.type === 'text';
            loginPassword.type   = isText ? 'password' : 'text';
            loginEyeIcon.className = isText ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        });

        if (toggleRegPwd) {
            toggleRegPwd.addEventListener('click', () => {
                const isText = regPassword.type === 'text';
                regPassword.type   = isText ? 'password' : 'text';
                regEyeIcon.className = isText ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            });
        }

    });
    </script>

</body>
</html>
