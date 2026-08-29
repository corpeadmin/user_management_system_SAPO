<?php
// Navbar include
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$logged_in = is_logged_in();
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'User';
$user_avatar = $_SESSION['user_avatar'] ?? null;
$avatar_url = get_avatar_url($user_avatar, $user_name);
$is_admin = is_admin();
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $logged_in ? 'dashboard.php' : 'login.php' ?>">
            <i class="bi bi-person-gear fs-4 text-primary"></i>
            <span class="fs-5">User<span class="text-primary">Management</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <?php if ($logged_in): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page === 'index.php' || $current_page === 'edit.php') ? 'active' : '' ?>" href="index.php">
                                <i class="bi bi-people me-1"></i> User Directory
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'profile.php') ? 'active' : '' ?>" href="profile.php">
                            <i class="bi bi-person-badge me-1"></i> My Profile
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle gap-2 text-dark" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= htmlspecialchars($avatar_url) ?>" alt="<?= htmlspecialchars($user_name) ?>" width="36" height="36" class="avatar-img border">
                            <div class="d-none d-sm-block text-start lh-1">
                                <div class="fw-semibold small"><?= htmlspecialchars($user_name) ?></div>
                                <span class="badge <?= $user_role === 'Admin' ? 'badge-role-admin' : 'badge-role-user' ?> mt-1" style="font-size: 0.65rem;">
                                    <?= htmlspecialchars($user_role) ?>
                                </span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="userDropdown">
                            <li class="px-3 py-2 border-bottom d-sm-none">
                                <div class="fw-bold"><?= htmlspecialchars($user_name) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></small>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="profile.php">
                                    <i class="bi bi-person me-2 text-primary"></i> My Profile & Avatar
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="profile.php#password-tab">
                                    <i class="bi bi-key me-2 text-warning"></i> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'login.php') ? 'active' : '' ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="register.php">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
