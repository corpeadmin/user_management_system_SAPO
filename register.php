<?php
require_once 'db.php';
require_once 'auth.php';

// Redirect if already logged in
require_guest('dashboard.php');

$error = '';
$success = '';

$name_val = '';
$email_val = '';
$phone_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $name_val = $name;
    $email_val = $email;
    $phone_val = $phone;

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, Email, and Password are required fields.";
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $error = "Full Name must be between 2 and 100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please re-type your password.";
    } else {
        // Check if email is already taken
        $check_stmt = $conn->prepare("SELECT id FROM `users` WHERE `email` = ? LIMIT 1");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $error = "An account with the email \"{$email}\" already exists.";
                $check_stmt->close();
            } else {
                $check_stmt->close();

                // Handle Optional Profile Avatar Upload
                $avatar_filename = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['avatar'];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $max_size = 5 * 1024 * 1024; // 5MB
                        if ($file['size'] > $max_size) {
                            $error = "Avatar image must be smaller than 5MB.";
                        } else {
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);

                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                            if (in_array($mime, $allowed_types) && in_array($ext, $allowed_exts)) {
                                $avatar_filename = 'avatar_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                $target_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $avatar_filename;
                                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                                    $avatar_filename = null;
                                }
                            } else {
                                $error = "Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.";
                            }
                        }
                    } else {
                        $error = "File upload error occurred. Error code: " . $file['error'];
                    }
                }

                // If no errors so far, proceed to insert
                if (empty($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'User';

                    $stmt = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `avatar`, `phone`) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $avatar_filename, $phone);
                        if ($stmt->execute()) {
                            $new_user_id = $stmt->insert_id;
                            $stmt->close();

                            // Log in newly created user directly
                            $user = [
                                'id' => $new_user_id,
                                'name' => $name,
                                'email' => $email,
                                'role' => $role,
                                'avatar' => $avatar_filename,
                                'phone' => $phone,
                                'bio' => null
                            ];
                            login_user($user);

                            $_SESSION['success'] = "Account created successfully! Welcome to your dashboard, {$name}!";
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Registration failed: " . $stmt->error;
                            $stmt->close();
                        }
                    } else {
                        $error = "Query preparation failed: " . $conn->error;
                    }
                }
            }
        } else {
            $error = "Database query failed: " . $conn->error;
        }
    }
}

$page_title = "Create Account - User Management System";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="main-content d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7 col-xl-6">
                
                <!-- Flash messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card auth-card bg-white p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-person-plus-fill fs-2"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Create Account</h3>
                        <p class="text-muted small">Register to access your personal dashboard and profile</p>
                    </div>

                    <form method="POST" action="register.php" enctype="multipart/form-data">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold small text-secondary">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" id="name" name="name" 
                                       placeholder="e.g. John Doe" value="<?= htmlspecialchars($name_val) ?>" required autofocus>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control bg-light border-start-0" id="email" name="email" 
                                       placeholder="e.g. user@example.com" value="<?= htmlspecialchars($email_val) ?>" required>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold small text-secondary">Phone Number <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control bg-light border-start-0" id="phone" name="phone" 
                                       placeholder="e.g. +1 555-0199" value="<?= htmlspecialchars($phone_val) ?>">
                            </div>
                        </div>

                        <!-- Password & Confirm Password -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="password" class="form-label fw-semibold small text-secondary">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control bg-light border-start-0 border-end-0" id="password" name="password" 
                                           placeholder="Min. 6 chars" minlength="6" required>
                                    <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePass1" 
                                            onclick="togglePasswordVisibility('password', 'togglePass1')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="confirm_password" class="form-label fw-semibold small text-secondary">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" class="form-control bg-light border-start-0 border-end-0" id="confirm_password" name="confirm_password" 
                                           placeholder="Re-type password" minlength="6" required>
                                    <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePass2" 
                                            onclick="togglePasswordVisibility('confirm_password', 'togglePass2')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Avatar Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-secondary">Profile Avatar <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <img id="registerAvatarPreview" src="https://ui-avatars.com/api/?name=New+User&background=e2e8f0&color=64748b&size=80" 
                                     alt="Preview" width="56" height="56" class="rounded-circle border object-fit-cover">
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control form-control-sm bg-light" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewRegisterAvatar(this)">
                                    <div class="form-text" style="font-size: 0.75rem;">JPG, PNG, GIF, WEBP up to 5MB</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3 shadow-sm">
                            <i class="bi bi-person-check me-1"></i> Register Account
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="text-muted small mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-primary fw-semibold text-decoration-none">Sign In here</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function previewRegisterAvatar(input) {
    const preview = document.getElementById('registerAvatarPreview');
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
