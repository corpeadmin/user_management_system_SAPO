<?php
require_once 'db.php';
require_once 'auth.php';

// Must be authenticated to access
require_auth('login.php');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: index.php");
    exit();
}

// Access Control: Non-admins cannot edit other users' data
if (!is_admin() && $id !== current_user_id()) {
    $_SESSION['error'] = "Access denied. You do not have permission to view or edit other users' profiles.";
    header("Location: profile.php");
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

$avatar_url = get_avatar_url($user['avatar'] ?? null, $user['name']);
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$page_title = "Edit User #" . $user['id'];
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content py-5">
    <div class="container" style="max-width: 680px;">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Edit User #<?= htmlspecialchars($user['id']) ?></h4>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <span class="fw-semibold">User Details</span>
                <span class="badge bg-white text-primary"><?= htmlspecialchars($user['role'] ?? 'User') ?></span>
            </div>
            <div class="card-body p-4">
                <form action="update.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

                    <!-- Avatar Preview & Upload -->
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3 border">
                        <img id="editAvatarPreview" src="<?= htmlspecialchars($avatar_url) ?>" 
                             alt="<?= htmlspecialchars($user['name']) ?>" width="64" height="64" 
                             class="rounded-circle border object-fit-cover">
                        <div class="flex-grow-1">
                            <label for="avatar" class="form-label fw-semibold small mb-1">Update Profile Photo</label>
                            <input type="file" class="form-control form-control-sm" id="avatar" name="avatar" 
                                   accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewEditAvatar(this)">
                            <?php if (!empty($user['avatar'])): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar" value="1">
                                    <label class="form-check-label small text-danger" for="remove_avatar">
                                        Remove current photo and revert to initials
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small text-secondary">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small text-secondary">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label fw-semibold small text-secondary">Role</label>
                            <?php if (is_admin()): ?>
                                <select class="form-select" id="role" name="role">
                                    <option value="User" <?= ($user['role'] ?? 'User') === 'User' ? 'selected' : '' ?>>User</option>
                                    <option value="Admin" <?= ($user['role'] ?? 'User') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['role'] ?? 'User') ?>" disabled readonly>
                                <input type="hidden" name="role" value="<?= htmlspecialchars($user['role'] ?? 'User') ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small text-secondary">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1 555-0100">
                        </div>

                        <div class="col-12">
                            <label for="bio" class="form-label fw-semibold small text-secondary">About / Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="2"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label fw-semibold small text-secondary">New Password <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep existing password">
                            <div class="form-text">Leave blank if you do not want to change this user's password.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Update User
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary px-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function previewEditAvatar(input) {
    const preview = document.getElementById('editAvatarPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
