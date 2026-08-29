<?php
session_start();
require_once 'db.php';

// Auto-create table if needed
$create_sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_sql);

$sample_users = [
    ['Alex Rivera', 'alex.rivera@example.com', 'AdminPass123!'],
    ['Sarah Connor', 'sarah.c@cyberdyne.org', 'Resistance2026'],
    ['Marcus Vance', 'marcus.v@security.local', 'P@ssw0rd!_99'],
    ['Elena Rostova', 'elena.r@techcorp.io', 'SkyNet#Protected']
];

$inserted = 0;
foreach ($sample_users as $u) {
    $name = $u[0];
    $email = $u[1];
    $hashed_password = password_hash($u[2], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT IGNORE INTO `users` (`name`, `email`, `password`) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $inserted++;
        }
        $stmt->close();
    }
}

if ($inserted > 0) {
    $_SESSION['success'] = "Successfully populated database with {$inserted} demo user(s)!";
} else {
    $_SESSION['info'] = "Sample users are already present in the database.";
}

header('Location: index.php');
exit();
?>
