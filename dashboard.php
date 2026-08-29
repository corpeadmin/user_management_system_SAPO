<?php
require_once 'db.php';
require_once 'auth.php';

// Enforce authentication
require_auth('login.php');

$user = get_logged_in_user($conn);
if (!$user) {
    header("Location: login.php");
    exit();
}

$is_admin_user = is_admin();
$success_msg = $_SESSION['success'] ?? '';
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Query Statistics
$total_users = 0;
$admin_count = 0;
$user_count = 0;

$stats_res = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN role = 'Admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role != 'Admin' OR role IS NULL THEN 1 ELSE 0 END) as regular_count
    FROM `users`");

if ($stats_res && $row = $stats_res->fetch_assoc()) {
    $total_users = (int)$row['total'];
    $admin_count = (int)$row['admin_count'];
    $user_count = (int)$row['regular_count'];
}

// Calculate profile completion percentage
$profile_score = 50; // Base: name + email
if (!empty($user['avatar'])) $profile_score += 25;
if (!empty($user['phone'])) $profile_score += 15;
if (!empty($user['bio'])) $profile_score += 10;

$avatar_url = get_avatar_url($user['avatar'], $user['name']);

$page_title = "Dashboard - User Management System";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content py-4">
    <div class="container-fluid container-xl">

        <!-- Flash alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Hero Banner -->
        <div class="card border-0 mb-4 text-white overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #4338ca 0%, #3b82f6 100%);">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-auto">
                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="<?= htmlspecialchars($user['name']) ?>"
                             class="rounded-circle border border-3 border-white shadow" width="90" height="90" style="object-fit: cover;">
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
                            <span class="badge bg-white text-primary fw-semibold px-2 py-1"><?= htmlspecialchars($user['role']) ?></span>
                        </div>
                        <p class="text-white-50 mb-3 small">
                            <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?>
                            &bull;
                            <i class="bi bi-calendar3 ms-1 me-1"></i> User ID: #<?= htmlspecialchars($user['id']) ?> &bull; Joined <?= htmlspecialchars(date('M Y', strtotime($user['created_at'] ?? 'now'))) ?>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="profile.php" class="btn btn-light btn-sm text-primary fw-semibold shadow-sm">
                                <i class="bi bi-person-circle me-1"></i> My Profile
                            </a>
                            <a href="profile.php#avatar-tab" class="btn btn-outline-light btn-sm fw-semibold">
                                <i class="bi bi-camera me-1"></i> Upload Photo
                            </a>
                            <?php if ($is_admin_user): ?>
                                <a href="index.php" class="btn btn-outline-light btn-sm fw-semibold">
                                    <i class="bi bi-people me-1"></i> Manage Users
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Users Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100 bg-white border-0">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Users</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_users ?></h3>
                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> In system database</small>
                        </div>
                        <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Administrators Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100 bg-white border-0">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Account Tier</span>
                            <h3 class="fw-bold <?= $is_admin_user ? 'text-danger' : 'text-primary' ?> mb-0 mt-1">
                                <?= htmlspecialchars($user['role']) ?>
                            </h3>
                            <small class="text-muted"><?= $is_admin_user ? "{$admin_count} Admin / {$user_count} Users" : "Authenticated Account" ?></small>
                        </div>
                        <div class="icon-wrapper <?= $is_admin_user ? 'bg-danger bg-opacity-10 text-danger' : 'bg-primary bg-opacity-10 text-primary' ?>">
                            <i class="bi <?= $is_admin_user ? 'bi-shield-lock-fill' : 'bi-person-badge-fill' ?>"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Health Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100 bg-white border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Profile Health</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $profile_score ?>%</h3>
                            </div>
                            <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                                <i class="bi bi-person-check-fill"></i>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $profile_score ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card shadow-sm h-100 bg-white border-0">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">System Status</span>
                            <h3 class="fw-bold text-success mb-0 mt-1">Active</h3>
                            <small class="text-muted">PHP 8.2 &bull; MariaDB</small>
                        </div>
                        <div class="icon-wrapper bg-info bg-opacity-10 text-info">
                            <i class="bi bi-hdd-network-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content Grid -->
        <div class="row g-4">

            <?php if ($is_admin_user): ?>
                <!-- Admin View: FULL User Management with CRUD -->
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people text-primary fs-5"></i>
                                <h5 class="mb-0 fw-bold">User Management</h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= $total_users ?> total</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="create.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus me-1"></i> Add New
                                </a>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-right ms-1"></i> Full Directory
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Search/Filter Bar -->
                            <div class="p-3 border-bottom bg-light">
                                <form method="GET" action="" class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold mb-0">Search</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" name="search" class="form-control"
                                                   placeholder="Name, email, phone..."
                                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-0">Role</label>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="">All Roles</option>
                                            <option value="Admin" <?= ($_GET['role'] ?? '') == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                            <option value="User" <?= ($_GET['role'] ?? '') == 'User' ? 'selected' : '' ?>>User</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-0">Per Page</label>
                                        <select name="limit" class="form-select form-select-sm">
                                            <option value="5" <?= ($_GET['limit'] ?? 5) == 5 ? 'selected' : '' ?>>5</option>
                                            <option value="10" <?= ($_GET['limit'] ?? 5) == 10 ? 'selected' : '' ?>>10</option>
                                            <option value="25" <?= ($_GET['limit'] ?? 5) == 25 ? 'selected' : '' ?>>25</option>
                                            <option value="50" <?= ($_GET['limit'] ?? 5) == 50 ? 'selected' : '' ?>>50</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-funnel me-1"></i> Filter
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <?php
                            // Build query with filters
                            $search = $_GET['search'] ?? '';
                            $role_filter = $_GET['role'] ?? '';
                            $limit = (int)($_GET['limit'] ?? 5);
                            $page = (int)($_GET['page'] ?? 1);
                            $offset = ($page - 1) * $limit;

                            $where = [];
                            $params = [];
                            $types = "";

                            if (!empty($search)) {
                                $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                                $search_param = "%$search%";
                                $params[] = $search_param;
                                $params[] = $search_param;
                                $params[] = $search_param;
                                $types .= "sss";
                            }

                            if (!empty($role_filter)) {
                                $where[] = "role = ?";
                                $params[] = $role_filter;
                                $types .= "s";
                            }

                            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                            // Count total records
                            $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
                            $count_stmt = $conn->prepare($count_sql);
                            if (!empty($params)) {
                                $count_stmt->bind_param($types, ...$params);
                            }
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $total_records = $count_result->fetch_assoc()['total'];
                            $total_pages = ceil($total_records / $limit);

                            // Fetch users
                            $sql = "SELECT id, name, email, role, avatar, phone, bio, created_at, updated_at
                                    FROM users $where_clause
                                    ORDER BY id DESC
                                    LIMIT ? OFFSET ?";

                            $params[] = $limit;
                            $params[] = $offset;
                            $types .= "ii";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $users = $result->fetch_all(MYSQLI_ASSOC);
                            ?>

                            <?php if (empty($users)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    <h6>No users found</h6>
                                    <p class="small">Try adjusting your search or filters</p>
                                    <a href="create.php" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-person-plus me-1"></i> Add First User
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Contact</th>
                                                <th>Joined</th>
                                                <th class="text-end" style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $index => $u):
                                                $u_avatar = get_avatar_url($u['avatar'], $u['name']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="text-muted small"><?= $offset + $index + 1 ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= htmlspecialchars($u_avatar) ?>" alt="<?= htmlspecialchars($u['name']) ?>"
                                                             width="38" height="38" class="avatar-img border rounded-circle" style="object-fit: cover;">
                                                        <div>
                                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                                            <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $u['role'] === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                                                        <?= htmlspecialchars($u['role'] ?? 'User') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">
                                                        <?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '<span class="text-muted fst-italic">—</span>' ?>
                                                    </small>
                                                    <?php if (!empty($u['bio'])): ?>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($u['bio']) ?>">
                                                            <?= htmlspecialchars(substr($u['bio'], 0, 30)) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">
                                                        <?= htmlspecialchars(date('M d, Y', strtotime($u['created_at'] ?? 'now'))) ?>
                                                    </small>
                                                    <small class="text-muted" style="font-size: 0.7rem;">
                                                        <?= htmlspecialchars(date('h:i A', strtotime($u['created_at'] ?? 'now'))) ?>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="view.php?id=<?= urlencode($u['id']) ?>"
                                                           class="btn btn-outline-info"
                                                           title="View Details"
                                                           data-bs-toggle="tooltip">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?= urlencode($u['id']) ?>"
                                                           class="btn btn-outline-primary"
                                                           title="Edit User"
                                                           data-bs-toggle="tooltip">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button"
                                                                class="btn btn-outline-danger"
                                                                title="Delete User"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal<?= $u['id'] ?>">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Delete Modal for each user -->
                                            <div class="modal fade" id="deleteModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold">Confirm Deletion</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-center py-4">
                                                            <div class="mb-3">
                                                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3.5rem;"></i>
                                                            </div>
                                                            <h6 class="mb-2">Are you sure you want to delete this user?</h6>
                                                            <div class="bg-light p-3 rounded-3">
                                                                <div class="d-flex align-items-center justify-content-center gap-3">
                                                                    <img src="<?= htmlspecialchars($u_avatar) ?>" alt="<?= htmlspecialchars($u['name']) ?>"
                                                                         width="48" height="48" class="rounded-circle border">
                                                                    <div class="text-start">
                                                                        <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                                                                        <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                                                        <span class="badge <?= $u['role'] === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                                                                            <?= htmlspecialchars($u['role'] ?? 'User') ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-3">
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                                                    <i class="bi bi-exclamation-circle me-1"></i> This action cannot be undone!
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="bi bi-x-circle me-1"></i> Cancel
                                                            </button>
                                                            <a href="delete.php?id=<?= urlencode($u['id']) ?>"
                                                               class="btn btn-danger">
                                                                <i class="bi bi-trash3 me-1"></i> Delete Permanently
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">
                                        Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> users
                                    </span>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&limit=<?= $limit ?>">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php
                                            $start = max(1, $page - 2);
                                            $end = min($total_pages, $page + 2);
                                            for ($i = $start; $i <= $end; $i++):
                                            ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&limit=<?= $limit ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&limit=<?= $limit ?>">
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Regular User View: My Account Details -->
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-lines-fill text-primary fs-5"></i>
                                <h5 class="mb-0 fw-bold">My Account Information</h5>
                            </div>
                            <a href="profile.php" class="btn btn-sm btn-outline-primary">
                                Edit Profile <i class="bi bi-pencil ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="text-muted small fw-semibold">Full Name</label>
                                    <div class="fs-6 fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="text-muted small fw-semibold">Email Address</label>
                                    <div class="fs-6 fw-semibold text-dark"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="text-muted small fw-semibold">Phone Number</label>
                                    <div class="fs-6 text-dark"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<em class="text-muted">Not specified</em>' ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="text-muted small fw-semibold">Account Role</label>
                                    <div><span class="badge badge-role-user"><?= htmlspecialchars($user['role']) ?></span></div>
                                </div>
                                <div class="col-12 mt-3">
                                    <label class="text-muted small fw-semibold">About / Bio</label>
                                    <div class="p-3 bg-light rounded-3 text-secondary">
                                        <?= !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : '<em>You have not added a bio yet. Click "Edit Profile" to add one.</em>' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Right Column: Quick Actions & Profile Overview -->
            <div class="col-lg-4">

                <!-- Quick Actions Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <?php if ($is_admin_user): ?>
                            <a href="create.php" class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between p-2">
                                <span><i class="bi bi-person-plus-fill me-2"></i> Add New User</span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2">
                                <span><i class="bi bi-people-fill me-2"></i> Manage User Directory</span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2">
                            <span><i class="bi bi-person-badge me-2"></i> Edit My Profile</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="profile.php#avatar-tab" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2">
                            <span><i class="bi bi-camera-fill me-2"></i> Upload Profile Photo</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="profile.php#password-tab" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2">
                            <span><i class="bi bi-key-fill me-2"></i> Change Password</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="export.php" class="btn btn-outline-success text-start d-flex align-items-center justify-content-between p-2">
                            <span><i class="bi bi-file-earmark-spreadsheet me-2"></i> <?= $is_admin_user ? 'Export User Directory (CSV)' : 'Export My Profile (CSV)' ?></span>
                            <i class="bi bi-download text-muted small"></i>
                        </a>
                        <?php if ($is_admin_user): ?>
                            <a href="seed.php" class="btn btn-outline-info text-start d-flex align-items-center justify-content-between p-2">
                                <span><i class="bi bi-database-fill-add me-2"></i> Seed Demo Users</span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Snapshot -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-check text-primary me-1"></i> Account Overview</h6>
                        <a href="profile.php" class="small text-decoration-none">Edit</a>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="<?= htmlspecialchars($user['name']) ?>"
                             class="rounded-circle border mb-2 shadow-sm" width="70" height="70" style="object-fit: cover;">
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                        <div class="mt-2">
                            <span class="badge <?= $is_admin_user ? 'badge-role-admin' : 'badge-role-user' ?> px-2 py-1">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </div>
                        <?php if ($is_admin_user): ?>
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-shield-lock"></i> Admin Access
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>