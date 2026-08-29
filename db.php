<?php
// Prevent mysqli from throwing uncaught exceptions automatically on connection failure
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sample';

// Attempt connection
$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    $error_message = $conn->connect_error;

    // Check if server itself is running
    $server_conn = @new mysqli($host, $user, $pass);
    $server_online = !$server_conn->connect_error;

    // Auto-create database handler
    if ($server_online && isset($_GET['action']) && $_GET['action'] === 'init_database') {
        $server_conn->query("CREATE DATABASE IF NOT EXISTS `sample` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $server_conn->select_db('sample');
        $server_conn->query("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'User',
            `avatar` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(30) DEFAULT NULL,
            `bio` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Seed default admin if table is empty
        $check_admin = $server_conn->query("SELECT COUNT(*) as count FROM `users`");
        if ($check_admin && ($row = $check_admin->fetch_assoc()) && $row['count'] == 0) {
            $default_pass = password_hash('AdminPass123!', PASSWORD_DEFAULT);
            $server_conn->query("INSERT INTO `users` (`name`, `email`, `password`, `role`, `bio`) VALUES 
                ('Administrator', 'admin@example.com', '{$default_pass}', 'Admin', 'System Administrator account.')");
        }

        header("Location: index.php");
        exit();
    }

    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Error - User Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light py-5">
        <div class="container" style="max-width: 600px;">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Database Connection Failed</h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>

                    <?php if ($server_online): ?>
                        <div class="alert alert-info">
                            MySQL is running, but database <code>sample</code> does not exist yet.
                            <div class="mt-2">
                                <a href="db.php?action=init_database" class="btn btn-sm btn-primary">Create Database Now</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <h6>To fix this in XAMPP:</h6>
                        <ol>
                            <li>Open <strong>XAMPP Control Panel</strong>.</li>
                            <li>Start <strong>MySQL</strong>.</li>
                            <li>Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a> to create database <code>sample</code>.</li>
                        </ol>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="index.php" class="btn btn-secondary">Retry</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$conn->set_charset('utf8mb4');

// Auto-migrate schema if columns are missing in existing table
function check_and_migrate_schema($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($table_check && $table_check->num_rows > 0) {
        $cols = [];
        $col_res = $conn->query("SHOW COLUMNS FROM `users`");
        if ($col_res) {
            while ($c = $col_res->fetch_assoc()) {
                $cols[] = $c['Field'];
            }
        }
        
        if (!in_array('role', $cols)) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'User' AFTER `password`");
        }
        if (!in_array('avatar', $cols)) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL AFTER `role`");
        }
        if (!in_array('phone', $cols)) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `avatar`");
        }
        if (!in_array('bio', $cols)) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `phone`");
        }
        if (!in_array('updated_at', $cols)) {
            $conn->query("ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
        }
    }
}

// Run schema migration check
check_and_migrate_schema($conn);

// Ensure uploads/avatars directory exists
$avatar_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
if (!is_dir($avatar_upload_dir)) {
    @mkdir($avatar_upload_dir, 0777, true);
}
?>