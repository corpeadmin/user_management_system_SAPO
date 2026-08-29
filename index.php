<?php
require_once 'db.php';
require_once 'auth.php';

// By default, users MUST be authenticated to access the site and view data
require_auth('login.php');

$current_user = get_logged_in_user($conn);
$is_admin_user = is_admin();
$current_uid = current_user_id();

// Action: Create/initialize users table (Admin only)
if (isset($_GET['action']) && $_GET['action'] === 'init_table') {
    require_admin('index.php');
    
    $create_sql = "CREATE TABLE IF NOT EXISTS `users` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_sql)) {
        $_SESSION['success'] = "Table `users` created and configured successfully.";
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
if ($table_exists && $is_admin_user) {
    $result = $conn->query("SELECT id, name, email, role, avatar, phone, bio, created_at FROM `users` ORDER BY `id` DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Flash messages
$success_msg = $_SESSION['success'] ?? '';
$error_msg = $_SESSION['error'] ?? '';
$info_msg = $_SESSION['info'] ?? '';
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);

$page_title = $is_admin_user ? "User Directory & Management" : "My Account";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content py-4">
    <div class="container-fluid container-xl">
        
        <!-- Header / Actions bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom gap-3">
            <div>
                <h3 class="mb-0 fw-bold"><?= $is_admin_user ? 'User Management Directory' : 'My User Account' ?></h3>
                <small class="text-muted">
                    Logged in as <strong><?= htmlspecialchars($current_user['name']) ?></strong> 
                    <span class="badge <?= $is_admin_user ? 'badge-role-admin' : 'badge-role-user' ?> ms-1"><?= htmlspecialchars($current_user['role']) ?></span>
                    <?php if ($is_admin_user): ?>
                        &bull; Total Registered: <strong><?= count($users) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="dashboard.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
                <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-badge me-1"></i> My Profile
                </a>
                <?php if ($is_admin_user): ?>
                    <a href="seed.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-database-fill-add me-1"></i> Seed Demo Data
                    </a>
                    <a href="export.php" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Users CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($info_msg)): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <?= htmlspecialchars($info_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Table Missing Warning -->
        <?php if (!$table_exists && $is_admin_user): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm">
                <div><i class="bi bi-exclamation-triangle-fill me-2"></i> Table <code>users</code> not found in database <code>sample</code>.</div>
                <a href="index.php?action=init_table" class="btn btn-sm btn-warning">Create Table Now</a>
            </div>
        <?php endif; ?>

        <?php if ($is_admin_user): ?>
            <!-- ================= ADMIN: USER MANAGEMENT DIRECTORY ================= -->
            <div class="row g-4">
                <!-- Add User Form -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 80px; z-index: 10;">
                        <div class="card-header bg-primary text-white fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus-fill"></i> Add New User
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <form action="create.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold small text-secondary">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold small text-secondary">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="user@example.com" required>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="role" class="form-label fw-semibold small text-secondary">Role</label>
                                        <select class="form-select" id="role" name="role">
                                            <option value="User" selected>User</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label for="phone" class="form-label fw-semibold small text-secondary">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" placeholder="+1 555...">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold small text-secondary">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password (min 6 chars)" minlength="6" required>
                                    <div class="form-text">Hashed with <code>PASSWORD_DEFAULT</code>.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="avatar" class="form-label fw-semibold small text-secondary">Profile Image <span class="text-muted fw-normal">(Optional)</span></label>
                                    <input type="file" class="form-control form-control-sm" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-check-circle me-1"></i> Add User Record
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people-fill text-primary"></i>
                                <h5 class="mb-0 fw-bold">User Accounts (<?= count($users) ?>)</h5>
                            </div>
                            <div class="input-group input-group-sm" style="max-width: 240px;">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="filterInput" class="form-control border-start-0" placeholder="Search users...">
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                                    No users registered yet. Click "Add New User" or "Seed Demo Data".
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="usersTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;">ID</th>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Phone</th>
                                                <th>Registered</th>
                                                <th class="text-end" style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $u): 
                                                $u_avatar = get_avatar_url($u['avatar'] ?? null, $u['name']);
                                                $is_self = ($u['id'] == $current_uid);
                                            ?>
                                            <tr class="<?= $is_self ? 'table-primary bg-opacity-25' : '' ?>">
                                                <td><strong>#<?= htmlspecialchars($u['id']) ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= htmlspecialchars($u_avatar) ?>" alt="<?= htmlspecialchars($u['name']) ?>" 
                                                             width="36" height="36" class="avatar-img border flex-shrink-0">
                                                        <div>
                                                            <div class="fw-semibold text-dark">
                                                                <?= htmlspecialchars($u['name']) ?>
                                                                <?php if ($is_self): ?>
                                                                    <span class="badge bg-primary text-white" style="font-size: 0.65rem;">You</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-muted small text-decoration-none">
                                                                <?= htmlspecialchars($u['email']) ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= ($u['role'] ?? 'User') === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                                                        <?= htmlspecialchars($u['role'] ?? 'User') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '—' ?></small>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($u['created_at'] ?? 'now'))) ?></small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= $is_self ? 'profile.php' : 'edit.php?id=' . urlencode($u['id']) ?>" 
                                                           class="btn btn-outline-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="delete.php?id=<?= urlencode($u['id']) ?>" 
                                                           class="btn btn-outline-danger" title="Delete"
                                                           onclick="return confirm('Delete user \'<?= htmlspecialchars(addslashes($u['name'])) ?>\'?');">
                                                            <i class="bi bi-trash3"></i>
                                                        </a>
                                                    </div>
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

        <?php else: ?>
            <!-- ================= REGULAR USER: PERSONAL ACCOUNT OVERVIEW ================= -->
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm text-center p-4 p-md-5">
                        <img src="<?= htmlspecialchars(get_avatar_url($current_user['avatar'], $current_user['name'])) ?>" 
                             alt="<?= htmlspecialchars($current_user['name']) ?>" 
                             class="rounded-circle border border-3 border-primary shadow mx-auto mb-3" width="110" height="110" style="object-fit: cover;">
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($current_user['name']) ?></h4>
                        <p class="text-muted mb-2"><?= htmlspecialchars($current_user['email']) ?></p>
                        <div class="mb-4">
                            <span class="badge badge-role-user px-3 py-1">Standard Account (ID #<?= htmlspecialchars($current_user['id']) ?>)</span>
                        </div>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="profile.php" class="btn btn-primary px-4">
                                <i class="bi bi-person-badge me-1"></i> Edit Profile & Photo
                            </a>
                            <a href="profile.php#password-tab" class="btn btn-outline-secondary px-3">
                                <i class="bi bi-key me-1"></i> Change Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Live table filter
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

<?php require_once 'includes/footer.php'; ?>