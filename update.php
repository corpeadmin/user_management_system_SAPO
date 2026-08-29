<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic ID check
    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid user ID specified.';
        header('Location: index.php');
        exit();
    }

    // Input Validation
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = 'Name and Email are required fields.';
        header('Location: index.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: index.php');
        exit();
    }

    // Check if another user already owns this email
    $check_stmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ?");
    if ($check_stmt) {
        $check_stmt->bind_param("si", $email, $id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $_SESSION['error'] = 'Another user with email "' . $email . '" already exists.';
            $check_stmt->close();
            header('Location: index.php');
            exit();
        }
        $check_stmt->close();
    }

    // Update with or without new password
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ?, `password` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $name, $email, $hashed_password, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User "' . $name . '" (ID #' . $id . ') updated successfully with a new password!';
            } else {
                $_SESSION['error'] = 'Failed to update user: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Query preparation failed: ' . $conn->error;
        }
    } else {
        $stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $name, $email, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'User "' . $name . '" (ID #' . $id . ') updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update user: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Query preparation failed: ' . $conn->error;
        }
    }
}

header('Location: index.php');
exit();
?>
