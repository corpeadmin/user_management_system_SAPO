<?php
session_start();
require_once 'db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error'] = "User ID #{$id} not found.";
    header("Location: index.php");
    exit();
}

$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - #<?= htmlspecialchars($user['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; padding-top: 50px; }
        .form-container { max-width: 550px; margin: 0 auto; }
    </style>
</head>
<body>

<div class="container form-container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit User #<?= htmlspecialchars($user['id']) ?></h5>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <form action="update.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-bold">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                    <div class="form-text text-muted">Leave empty if you do not want to change the password.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Update User</button>
                    <a href="index.php" class="btn btn-secondary px-3">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
