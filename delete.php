<?php
require_once 'db.php';
require_once 'auth.php';

// Must be authenticated to delete
require_auth('login.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid user ID specified for deletion.';
        header('Location: index.php');
        exit();
    }

    // Access control: Non-admins can only delete their own account
    if (!is_admin() && $id !== current_user_id()) {
        $_SESSION['error'] = 'Access denied. You do not have permission to delete other users.';
        header('Location: index.php');
        exit();
    }

    // Retrieve name and avatar for cleanup & feedback
    $name_stmt = $conn->prepare("SELECT `name`, `avatar` FROM `users` WHERE `id` = ?");
    $user_name = '';
    $user_avatar = null;
    if ($name_stmt) {
        $name_stmt->bind_param("i", $id);
        $name_stmt->execute();
        $name_stmt->bind_result($user_name, $user_avatar);
        $name_stmt->fetch();
        $name_stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Delete avatar file from disk if exists
                if (!empty($user_avatar)) {
                    $avatar_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user_avatar;
                    if (file_exists($avatar_path) && is_file($avatar_path)) {
                        @unlink($avatar_path);
                    }
                }

                // If user deleted their own account, log them out
                if (is_logged_in() && current_user_id() === $id) {
                    logout_user();
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION['info'] = 'Your account was deleted successfully.';
                    header('Location: login.php');
                    exit();
                }

                $_SESSION['success'] = 'User ' . ($user_name ? '"' . $user_name . '" (ID #' . $id . ')' : 'ID #' . $id) . ' was deleted successfully.';
            } else {
                $_SESSION['error'] = 'User ID #' . $id . ' was not found or has already been deleted.';
            }
        } else {
            $_SESSION['error'] = 'Failed to delete user: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Query preparation failed: ' . $conn->error;
    }
}

header('Location: index.php');
exit();
?>
