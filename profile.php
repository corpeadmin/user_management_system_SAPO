<?php
require_once 'db.php';
require_once 'auth.php';

// Enforce login
require_auth('login.php');

$user = get_logged_in_user($conn);
if (!$user) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$user['id'];
$success_msg = $_SESSION['success'] ?? '';
$error_msg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPDATE PERSONAL INFO
    if ($action === 'update_info') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if (empty($name) || empty($email)) {
            $_SESSION['error'] = "Name and Email are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address.";
        } else {
            // Check email uniqueness
            $check_stmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? AND `id` != ? LIMIT 1");
            if ($check_stmt) {
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();
                if ($check_stmt->num_rows > 0) {
                    $_SESSION['error'] = "The email address \"{$email}\" is already used by another account.";
                } else {
                    $upd_stmt = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ?, `phone` = ?, `bio` = ? WHERE `id` = ?");
                    if ($upd_stmt) {
                        $upd_stmt->bind_param("ssssi", $name, $email, $phone, $bio, $user_id);
                        if ($upd_stmt->execute()) {
                            $_SESSION['success'] = "Profile details updated successfully!";
                        } else {
                            $_SESSION['error'] = "Database update failed: " . $upd_stmt->error;
                        }
                        $upd_stmt->close();
                    }
                }
                $check_stmt->close();
            }
        }
        header("Location: profile.php#info-tab");
        exit();
    }

    // 2. UPLOAD PROFILE AVATAR
    elseif ($action === 'upload_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = "Please choose an image file to upload.";
        } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "Upload error occurred. Code: " . $_FILES['avatar']['error'];
        } else {
            $file = $_FILES['avatar'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if ($file['size'] > $max_size) {
                $_SESSION['error'] = "Image file is too large. Maximum size allowed is 5MB.";
            } else {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($mime, $allowed_types) && in_array($ext, $allowed_exts)) {
                    $avatar_filename = 'avatar_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $target_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $avatar_filename;

                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Delete old avatar file if it existed on disk
                        if (!empty($user['avatar'])) {
                            $old_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user['avatar'];
                            if (file_exists($old_path) && is_file($old_path)) {
                                @unlink($old_path);
                            }
                        }

                        // Save in database
                        $stmt = $conn->prepare("UPDATE `users` SET `avatar` = ? WHERE `id` = ?");
                        if ($stmt) {
                            $stmt->bind_param("si", $avatar_filename, $user_id);
                            $stmt->execute();
                            $stmt->close();
                            $_SESSION['user_avatar'] = $avatar_filename;
                            $_SESSION['success'] = "Profile picture updated successfully!";
                        }
                    } else {
                        $_SESSION['error'] = "Failed to save the uploaded image to server destination.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid file format. Please upload a JPG, PNG, GIF, or WEBP image.";
                }
            }
        }
        header("Location: profile.php#avatar-tab");
        exit();
    }

    // 3. REMOVE PROFILE AVATAR
    elseif ($action === 'remove_avatar') {
        if (!empty($user['avatar'])) {
            $old_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user['avatar'];
            if (file_exists($old_path) && is_file($old_path)) {
                @unlink($old_path);
            }
        }
        $stmt = $conn->prepare("UPDATE `users` SET `avatar` = NULL WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_avatar'] = null;
            $_SESSION['success'] = "Profile picture removed successfully.";
        }
        header("Location: profile.php");
        exit();
    }

    // 4. CHANGE PASSWORD
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "All password fields are required.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error'] = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New password and confirmation password do not match.";
        } else {
            // Retrieve current password hash
            $stmt = $conn->prepare("SELECT password FROM `users` WHERE `id` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($current_hash);
                $stmt->fetch();
                $stmt->close();

                if (password_verify($current_password, $current_hash)) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd_stmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
                    if ($upd_stmt) {
                        $upd_stmt->bind_param("si", $new_hash, $user_id);
                        if ($upd_stmt->execute()) {
                            $_SESSION['success'] = "Password changed successfully!";
                        } else {
                            $_SESSION['error'] = "Failed to update password: " . $upd_stmt->error;
                        }
                        $upd_stmt->close();
                    }
                } else {
                    $_SESSION['error'] = "Current password is incorrect.";
                }
            }
        }
        header("Location: profile.php#password-tab");
        exit();
    }
}

// Refresh user info after possible redirect or page load
$user = get_logged_in_user($conn);
$avatar_url = get_avatar_url($user['avatar'], $user['name']);

$page_title = "User Profile - User Management System";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content py-4">
    <div class="container-fluid container-xl">
        
        <!-- Header breadcrumb -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">My User Profile</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
                    </ol>
                </nav>
            </div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Flash alerts -->
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

        <div class="row g-4">
            
            <!-- Left Column: User Summary Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm text-center p-4 mb-4">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="<?= htmlspecialchars($user['name']) ?>" 
                             class="avatar-preview-box" id="sidebarAvatarImg">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2" title="Active"></span>
                    </div>

                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="mb-3">
                        <span class="badge <?= $user['role'] === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?> px-3 py-1">
                            <i class="bi <?= $user['role'] === 'Admin' ? 'bi-shield-check' : 'bi-person' ?> me-1"></i>
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </div>

                    <?php if (!empty($user['bio'])): ?>
                        <div class="p-3 bg-light rounded-3 text-start small mb-3 text-muted">
                            <i class="bi bi-quote text-primary me-1"></i>
                            <?= nl2br(htmlspecialchars($user['bio'])) ?>
                        </div>
                    <?php endif; ?>

                    <hr class="my-3">

                    <div class="text-start small text-muted">
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-telephone me-2 text-primary"></i> Phone:</span>
                            <span class="text-dark fw-semibold"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<em>Not set</em>' ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-calendar3 me-2 text-primary"></i> Joined:</span>
                            <span class="text-dark fw-semibold"><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-clock-history me-2 text-primary"></i> Last Update:</span>
                            <span class="text-dark fw-semibold"><?= !empty($user['updated_at']) ? htmlspecialchars(date('M d, Y H:i', strtotime($user['updated_at']))) : 'N/A' ?></span>
                        </div>
                    </div>

                    <?php if (!empty($user['avatar'])): ?>
                        <form method="POST" action="profile.php" class="mt-3" onsubmit="return confirm('Are you sure you want to remove your custom profile photo?');">
                            <input type="hidden" name="action" value="remove_avatar">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-trash3 me-1"></i> Remove Photo
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Settings Tabs -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white p-0">
                        <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="profileTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                    <i class="bi bi-person me-1"></i> Personal Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="avatar-tab" data-bs-toggle="tab" data-bs-target="#avatarPane" type="button" role="tab">
                                    <i class="bi bi-camera me-1"></i> Upload Photo
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="password-tab" data-bs-toggle="tab" data-bs-target="#passwordPane" type="button" role="tab">
                                    <i class="bi bi-shield-lock me-1"></i> Security / Password
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="tab-content" id="profileTabContent">
                            
                            <!-- TAB 1: PERSONAL INFORMATION -->
                            <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                <h5 class="fw-bold mb-3">General Information</h5>
                                <form method="POST" action="profile.php">
                                    <input type="hidden" name="action" value="update_info">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label fw-semibold small text-secondary">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="email" class="form-label fw-semibold small text-secondary">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="phone" class="form-label fw-semibold small text-secondary">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-secondary">Account Role</label>
                                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['role']) ?>" disabled readonly>
                                        </div>

                                        <div class="col-12">
                                            <label for="bio" class="form-label fw-semibold small text-secondary">About / Bio</label>
                                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us a little about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                            <div class="form-text">Brief bio or notes for your account profile.</div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-check-lg me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- TAB 2: UPLOAD AVATAR IMAGE -->
                            <div class="tab-pane fade" id="avatarPane" role="tabpanel" aria-labelledby="avatar-tab">
                                <h5 class="fw-bold mb-2">Upload Profile Photo</h5>
                                <p class="text-muted small mb-4">Choose a portrait photo to represent you in the User Management System.</p>

                                <form method="POST" action="profile.php" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="upload_avatar">

                                    <div class="row align-items-center g-4 mb-4">
                                        <div class="col-auto text-center">
                                            <div class="position-relative">
                                                <img id="avatarLivePreview" src="<?= htmlspecialchars($avatar_url) ?>" 
                                                     alt="Avatar Preview" class="avatar-preview-box">
                                            </div>
                                            <small class="d-block text-muted mt-2">Current Photo</small>
                                        </div>

                                        <div class="col">
                                            <div class="p-3 border rounded-3 bg-light">
                                                <label for="avatarInput" class="form-label fw-semibold small mb-2">Select Image File</label>
                                                <input class="form-control" type="file" id="avatarInput" name="avatar" 
                                                       accept="image/jpeg,image/png,image/gif,image/webp" required onchange="previewUploadImage(this)">
                                                <div class="d-flex flex-wrap gap-2 text-muted small mt-2">
                                                    <span><i class="bi bi-check2 text-success"></i> JPG, PNG, GIF, WEBP</span>
                                                    <span>&bull;</span>
                                                    <span><i class="bi bi-check2 text-success"></i> Maximum 5 MB</span>
                                                    <span>&bull;</span>
                                                    <span><i class="bi bi-check2 text-success"></i> Square 1:1 recommended</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload & Save Photo
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- TAB 3: CHANGE PASSWORD -->
                            <div class="tab-pane fade" id="passwordPane" role="tabpanel" aria-labelledby="password-tab">
                                <h5 class="fw-bold mb-3">Change Password</h5>
                                <p class="text-muted small mb-4">Ensure your account is using a strong password.</p>

                                <form method="POST" action="profile.php">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="mb-3" style="max-width: 450px;">
                                        <label for="current_password" class="form-label fw-semibold small text-secondary">Current Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleCurPass" onclick="togglePasswordVisibility('current_password', 'toggleCurPass')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3" style="max-width: 450px;">
                                        <label for="new_password" class="form-label fw-semibold small text-secondary">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleNewPass" onclick="togglePasswordVisibility('new_password', 'toggleNewPass')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Must be at least 6 characters long.</div>
                                    </div>

                                    <div class="mb-4" style="max-width: 450px;">
                                        <label for="confirm_password" class="form-label fw-semibold small text-secondary">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfPass" onclick="togglePasswordVisibility('confirm_password', 'toggleConfPass')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-key-fill me-1"></i> Update Password
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Live Image Preview for Profile Upload
function previewUploadImage(input) {
    const preview = document.getElementById('avatarLivePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Tab activation from URL hash
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash) {
        const triggerEl = document.querySelector(`button[data-bs-target="${hash.replace('-tab', '')}"], button[id="${hash.substring(1)}"]`);
        if (triggerEl) {
            const tab = bootstrap.Tab.getOrCreateInstance(triggerEl);
            if (tab) tab.show();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
