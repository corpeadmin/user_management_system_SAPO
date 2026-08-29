<?php
// Authentication & Session Helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is currently authenticated.
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Check if the logged-in user has Admin role.
 * @return bool
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}

/**
 * Get currently logged-in user's ID.
 * @return int|null
 */
function current_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Fetch complete logged-in user record from database.
 * @param mysqli $conn
 * @return array|null
 */
function get_logged_in_user(mysqli $conn): ?array {
    $uid = current_user_id();
    if (!$uid) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, name, email, role, avatar, phone, bio, created_at, updated_at FROM `users` WHERE `id` = ?");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // User was deleted from database
        logout_user();
        return null;
    }

    // Keep session in sync with fresh database values
    refresh_session_user($user);

    return $user;
}

/**
 * Enforce authentication for protected pages.
 * By default, unauthenticated users cannot access any data.
 * @param string $redirect_to
 */
function require_auth(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['error'] = "Authentication required. Please log in to access this portal.";
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        header("Location: " . $redirect_to);
        exit();
    }
}

/**
 * Enforce Admin role for privileged actions.
 * @param string $redirect_to
 */
function require_admin(string $redirect_to = 'dashboard.php'): void {
    require_auth('login.php');
    if (!is_admin()) {
        $_SESSION['error'] = "Access denied. Administrator privileges required.";
        header("Location: " . $redirect_to);
        exit();
    }
}

/**
 * Enforce guest state (e.g. for login/register pages).
 * @param string $redirect_to
 */
function require_guest(string $redirect_to = 'dashboard.php'): void {
    if (is_logged_in()) {
        header("Location: " . $redirect_to);
        exit();
    }
}

/**
 * Log in a user and populate session variables.
 * @param array $user
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'User';
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    $_SESSION['user_phone'] = $user['phone'] ?? null;
    $_SESSION['user_bio'] = $user['bio'] ?? null;
}

/**
 * Refresh current session data with fresh user array.
 * @param array $user
 */
function refresh_session_user(array $user): void {
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'] ?? 'User';
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    $_SESSION['user_phone'] = $user['phone'] ?? null;
    $_SESSION['user_bio'] = $user['bio'] ?? null;
}

/**
 * Log out user and destroy session completely.
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Resolve avatar image URL or generate styled initials avatar.
 * @param string|null $avatar_filename
 * @param string $name
 * @return string
 */
function get_avatar_url(?string $avatar_filename, string $name = 'User'): string {
    if (!empty($avatar_filename)) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $avatar_filename;
        if (file_exists($filePath)) {
            return 'uploads/avatars/' . htmlspecialchars($avatar_filename);
        }
    }
    $encodedName = urlencode($name ?: 'User');
    return "https://ui-avatars.com/api/?name={$encodedName}&background=0d6efd&color=ffffff&bold=true&size=150";
}
?>
