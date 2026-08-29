<?php
require_once 'db.php';
require_once 'auth.php';

// Must be authenticated and must be an Admin to create users directly
require_auth('login.php');
require_admin('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'User');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (!in_array($role, ['User', 'Admin'])) {
        $role = 'User';
    }

    // Input Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'All fields (Name, Email, Password) are required.';
        header('Location: index.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please provide a valid email address.';
        header('Location: index.php');
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
        header('Location: index.php');
        exit();
    }

    // Check if email is already taken
    $check_stmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $_SESSION['error'] = 'A user with email "' . $email . '" already exists.';
            $check_stmt->close();
            header('Location: index.php');
            exit();
        }
        $check_stmt->close();
    }

    // Handle Avatar Upload
    $avatar_filename = null;
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
                    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                        $avatar_filename = null;
                    }
                }
            }
        }
    }

    // Securely hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user record
    $stmt = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `avatar`, `phone`, `bio`) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssssss", $name, $email, $hashed_password, $role, $avatar_filename, $phone, $bio);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User "' . $name . '" registered successfully!';
        } else {
            $_SESSION['error'] = 'Database error inserting record: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Query preparation failed: ' . $conn->error;
    }
}

header('Location: index.php');
exit();
?>