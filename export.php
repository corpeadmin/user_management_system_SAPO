<?php
session_start();
require_once 'db.php';

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if (!$table_check || $table_check->num_rows === 0) {
    $_SESSION['error'] = 'Cannot export: table `users` does not exist.';
    header('Location: index.php');
    exit();
}

$result = $conn->query("SELECT `id`, `name`, `email`, `password`, `created_at` FROM `users` ORDER BY `id` ASC");

$filename = "users_export_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header
fputcsv($output, ['ID', 'Full Name', 'Email Address', 'Password Hash', 'Date Created']);

// Rows
if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['password'],
            $row['created_at'] ?? ''
        ]);
    }
}

fclose($output);
exit();
?>
