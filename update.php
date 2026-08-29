<?php
require_once 'db.php';
require_once 'auth.php';

// Must be authenticated
require_auth('login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header('Location: dashboard.php');
    exit();
}

// Access Control: Non-admins can only edit their own profile
if (!is_admin() && $id !== current_user_id()) {
    $_SESSION['error'] = "Access denied. You can only edit your own profile.";
    header('Location: profile.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'User');
$phone = trim($_POST['phone'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate role
if (!in_array($role, ['User', 'Admin'])) {
    $role = 'User';
}

// Input Validation
if (empty($name) || empty($email)) {
    $_SESSION['error'] = 'Name and Email are required.';
    header('Location: edit.php?id=' . $id);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please provide a valid email address.';
    header('Location: edit.php?id=' . $id);
    exit();
}

// Check if email is already taken by another user
$check_stmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ?");
$check_stmt->bind_param("si", $email, $id);
$check_stmt->execute();
$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    $_SESSION['error'] = 'Email "' . $email . '" is already used by another user.';
    $check_stmt->close();
    header('Location: edit.php?id=' . $id);
    exit();
}
$check_stmt->close();

// Handle Avatar Upload
$avatar_filename = null;
$remove_avatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == 1;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] <= $max_size) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($mime, $allowed_types) && in_array($ext, $allowed_exts)) {
                $avatar_filename = 'avatar_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $avatar_filename;

                // Delete old avatar if exists
                $old_avatar_stmt = $conn->prepare("SELECT avatar FROM `users` WHERE id = ?");
                $old_avatar_stmt->bind_param("i", $id);
                $old_avatar_stmt->execute();
                $old_avatar_result = $old_avatar_stmt->get_result();
                $old_avatar = $old_avatar_result->fetch_assoc();
                if (!empty($old_avatar['avatar'])) {
                    $old_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $old_avatar['avatar'];
                    if (file_exists($old_path) && is_file($old_path)) {
                        @unlink($old_path);
                    }
                }
                $old_avatar_stmt->close();

                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $avatar_filename = null;
                }
            }
        }
    }
}

// Build update query
$update_fields = [];
$params = [];
$types = "";

$update_fields[] = "name = ?";
$params[] = $name;
$types .= "s";

$update_fields[] = "email = ?";
$params[] = $email;
$types .= "s";

// Only admin can change role
if (is_admin()) {
    $update_fields[] = "role = ?";
    $params[] = $role;
    $types .= "s";
}

$update_fields[] = "phone = ?";
$params[] = $phone;
$types .= "s";

$update_fields[] = "bio = ?";
$params[] = $bio;
$types .= "s";

// Handle avatar
if ($remove_avatar) {
    // Remove avatar from database and disk
    $old_avatar_stmt = $conn->prepare("SELECT avatar FROM `users` WHERE id = ?");
    $old_avatar_stmt->bind_param("i", $id);
    $old_avatar_stmt->execute();
    $old_avatar_result = $old_avatar_stmt->get_result();
    $old_avatar = $old_avatar_result->fetch_assoc();
    if (!empty($old_avatar['avatar'])) {
        $old_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $old_avatar['avatar'];
        if (file_exists($old_path) && is_file($old_path)) {
            @unlink($old_path);
        }
    }
    $old_avatar_stmt->close();
    $update_fields[] = "avatar = NULL";
} elseif ($avatar_filename) {
    $update_fields[] = "avatar = ?";
    $params[] = $avatar_filename;
    $types .= "s";
}

// Update password if provided
if (!empty($password)) {
    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
        header('Location: edit.php?id=' . $id);
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_fields[] = "password = ?";
    $params[] = $hashed_password;
    $types .= "s";
}

// Add ID to params
$params[] = $id;
$types .= "i";

$sql = "UPDATE `users` SET " . implode(", ", $update_fields) . " WHERE `id` = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        // Refresh session data if user edited their own profile
        if ($id === current_user_id()) {
            $refresh_stmt = $conn->prepare("SELECT id, name, email, role, avatar, phone, bio, created_at, updated_at FROM `users` WHERE `id` = ?");
            $refresh_stmt->bind_param("i", $id);
            $refresh_stmt->execute();
            $updated_user = $refresh_stmt->get_result()->fetch_assoc();
            if ($updated_user) {
                refresh_session_user($updated_user);
            }
            $refresh_stmt->close();
        }
        $_SESSION['success'] = 'User profile updated successfully!';
    } else {
        $_SESSION['error'] = 'Database error: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = 'Query preparation failed: ' . $conn->error;
}

// Redirect based on role
if (is_admin()) {
    header('Location: dashboard.php');
} else {
    header('Location: profile.php');
}
exit();
?>