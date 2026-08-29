<?php
require_once 'db.php';
require_once 'auth.php';

// Redirect if already logged in
require_guest('dashboard.php');

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

$email_val = $_COOKIE['remember_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    // Validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Query user
        $stmt = $conn->prepare("SELECT id, name, email, password, role, avatar, phone, bio FROM `users` WHERE `email` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                // Password matches
                login_user($user);

                // Remember email cookie
                if ($remember) {
                    setcookie('remember_email', $email, time() + (86400 * 30), "/");
                } else {
                    setcookie('remember_email', '', time() - 3600, "/");
                }

                $_SESSION['success'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";
                
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
                exit();
            } else {
                $error = "Invalid email address or password. Please try again.";
            }
        } else {
            $error = "Database query failed: " . $conn->error;
        }
    }
}

$page_title = "Log In - User Management System";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5 col-xl-4">
                
                <!-- Flash messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card auth-card bg-white p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Sign In</h3>
                        <p class="text-muted small">Access your User Management account</p>
                    </div>

                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light border-start-0" id="email" name="email" 
                                       placeholder="name@example.com" value="<?= htmlspecialchars($email_val) ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control bg-light border-start-0 border-end-0" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn" 
                                        onclick="togglePasswordVisibility('password', 'togglePasswordBtn')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" <?= !empty($email_val) ? 'checked' : '' ?>>
                                <label class="form-check-label small text-muted" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <!-- Quick Demo Credentials Fill -->
                    <div class="border-top pt-3 mt-2">
                        <div class="text-center text-muted small mb-2 fw-semibold">Quick Demo Login:</div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start d-flex justify-content-between align-items-center" onclick="fillDemo('admin@example.com', 'AdminPass123!')">
                                <span><i class="bi bi-shield-lock me-1 text-danger"></i> Admin User</span>
                                <small class="text-muted">admin@example.com</small>
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-muted small mb-0">
                            Don't have an account? 
                            <a href="register.php" class="text-primary fw-semibold text-decoration-none">Create Account</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once 'includes/footer.php'; ?>
