<?php
require_once 'db.php';
require_once 'auth.php';

// Must be authenticated
require_auth('login.php');

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: dashboard.php");
    exit();
}

// Access Control: Non-admins can only view their own profile
if (!is_admin() && $user_id !== current_user_id()) {
    $_SESSION['error'] = "Access denied. You can only view your own profile.";
    header("Location: profile.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$view_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$view_user) {
    $_SESSION['error'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}

$avatar_url = get_avatar_url($view_user['avatar'] ?? null, $view_user['name']);
$is_viewing_self = ($user_id === current_user_id());

$page_title = "View User - " . htmlspecialchars($view_user['name']);
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content py-5">
    <div class="container" style="max-width: 800px;">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">User Profile</h4>
            <div>
                <a href="<?= $is_viewing_self ? 'profile.php' : 'dashboard.php' ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><?= htmlspecialchars($view_user['name']) ?></span>
                <span class="badge <?= $view_user['role'] === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                    <?= htmlspecialchars($view_user['role'] ?? 'User') ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Avatar Section -->
                    <div class="col-md-4 text-center">
                        <img src="<?= htmlspecialchars($avatar_url) ?>"
                             alt="<?= htmlspecialchars($view_user['name']) ?>"
                             class="rounded-circle border shadow-sm"
                             width="150" height="150"
                             style="object-fit: cover;">
                        <div class="mt-2">
                            <span class="badge bg-secondary">ID: #<?= $view_user['id'] ?></span>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="text-muted small fw-semibold">Full Name</label>
                                <div class="fs-5 fw-bold"><?= htmlspecialchars($view_user['name']) ?></div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-semibold">Email Address</label>
                                <div><a href="mailto:<?= htmlspecialchars($view_user['email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($view_user['email']) ?>
                                </a></div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small fw-semibold">Phone Number</label>
                                <div><?= !empty($view_user['phone']) ? htmlspecialchars($view_user['phone']) : '<em class="text-muted">Not specified</em>' ?></div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small fw-semibold">Role</label>
                                <div><span class="badge <?= $view_user['role'] === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?>">
                                    <?= htmlspecialchars($view_user['role'] ?? 'User') ?>
                                </span></div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-semibold">About / Bio</label>
                                <div class="p-3 bg-light rounded-3 text-secondary">
                                    <?= !empty($view_user['bio']) ? nl2br(htmlspecialchars($view_user['bio'])) : '<em class="text-muted">No bio provided</em>' ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr>
                                <div class="row text-muted small">
                                    <div class="col-6">
                                        <label class="fw-semibold">Joined</label>
                                        <div><?= htmlspecialchars(date('F d, Y \a\t h:i A', strtotime($view_user['created_at'] ?? 'now'))) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="fw-semibold">Last Updated</label>
                                        <div><?= htmlspecialchars(date('F d, Y \a\t h:i A', strtotime($view_user['updated_at'] ?? 'now'))) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <?php if ($is_admin_user || $is_viewing_self): ?>
                    <a href="edit.php?id=<?= urlencode($view_user['id']) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </a>
                <?php endif; ?>
                <a href="<?= $is_viewing_self ? 'profile.php' : 'dashboard.php' ?>" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>