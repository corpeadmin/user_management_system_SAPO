<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

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

    // Securely hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user record
    $stmt = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $hashed_password);
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