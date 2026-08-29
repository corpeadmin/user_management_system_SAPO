<?php
session_start();
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid user ID specified for deletion.';
        header('Location: index.php');
        exit();
    }

    // Retrieve name for better feedback
    $name_stmt = $conn->prepare("SELECT `name` FROM `users` WHERE `id` = ?");
    $user_name = '';
    if ($name_stmt) {
        $name_stmt->bind_param("i", $id);
        $name_stmt->execute();
        $name_stmt->bind_result($user_name);
        $name_stmt->fetch();
        $name_stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
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
