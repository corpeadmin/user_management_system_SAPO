<?php
session_start();
require_once 'db.php';

// Action: Create/initialize users table
if (isset($_GET['action']) && $_GET['action'] === 'init_table') {
    $create_sql = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_sql)) {
        $_SESSION['success'] = "Table `users` created successfully.";
    } else {
        $_SESSION['error'] = "Error creating table: " . $conn->error;
    }
    header("Location: index.php");
    exit();
}

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
$table_exists = ($table_check && $table_check->num_rows > 0);

$users = [];
if ($table_exists) {
    $result = $conn->query("SELECT * FROM `users` ORDER BY `id` DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Flash messages
$success_msg = $_SESSION['success'] ?? '';
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management System</title>
    <!-- Simple Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }
        .container {
            max-width: 1100px;
        }
        .hash-preview {
            font-family: monospace;
            font-size: 0.8rem;
            max-width: 150px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="py-4">

<div class="container">
    <!-- Simple Header -->
    <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h3 class="mb-0 fw-bold">User Management System</h3>
            <small class="text-muted">MySQL Database: <strong>sample</strong></small>
        </div>
        <div class="d-flex gap-2">
            <a href="seed.php" class="btn btn-sm btn-outline-secondary">Seed Demo Data</a>
            <a href="export.php" class="btn btn-sm btn-outline-success">Export CSV</a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Table Missing Warning -->
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>Table <code>users</code> not found in database <code>sample</code>.</div>
            <a href="index.php?action=init_table" class="btn btn-sm btn-warning">Create Table</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add User Form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    Add New User
                </div>
                <div class="card-body">
                    <form action="create.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="user@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <div class="form-text">Hashed with <code>PASSWORD_DEFAULT</code>.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Add User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Users List (<?= count($users) ?>)</h5>
                    <input type="text" id="filterInput" class="form-control form-control-sm" placeholder="Search..." style="max-width: 180px;">
                </div>
                <div class="card-body p-0">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5 text-muted">
                            No users registered yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Password Hash</th>
                                        <th>Registered</th>
                                        <th class="text-end" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($u['id']) ?></strong></td>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                                        <td><span class="hash-preview" title="<?= htmlspecialchars($u['password']) ?>"><?= htmlspecialchars($u['password']) ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($u['created_at'] ?? 'N/A') ?></small></td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= urlencode($u['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                                            <a href="delete.php?id=<?= urlencode($u['id']) ?>" 
                                               class="btn btn-sm btn-outline-danger py-0 px-2"
                                               onclick="return confirm('Delete user <?= htmlspecialchars(addslashes($u['name'])) ?>?');">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simple real-time search filter
const filterInput = document.getElementById('filterInput');
const usersTable = document.getElementById('usersTable');
if (filterInput && usersTable) {
    filterInput.addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        const rows = usersTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>