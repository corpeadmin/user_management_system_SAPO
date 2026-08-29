<?php
// Header include
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = $page_title ?? 'User Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - User Management System</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            --sidebar-bg: #1e293b;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .navbar-custom {
            background: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1.5rem;
        }

        .navbar-brand {
            font-weight: 700;
            color: #1e293b !important;
            letter-spacing: -0.02em;
        }

        .navbar-brand i {
            color: #4f46e5;
        }

        .nav-link {
            font-weight: 500;
            color: #64748b !important;
            padding: 0.5rem 0.85rem !important;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #4f46e5 !important;
            background-color: #eff6ff;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: var(--card-shadow-hover);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            padding: 1rem 1.25rem;
            border-top-left-radius: 0.85rem !important;
            border-top-right-radius: 0.85rem !important;
        }

        .btn-primary {
            background: #4f46e5;
            border-color: #4f46e5;
            font-weight: 600;
        }

        .btn-primary:hover, .btn-primary:focus {
            background: #4338ca;
            border-color: #4338ca;
        }

        .badge-role-admin {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 600;
            border: 1px solid #fecaca;
        }

        .badge-role-user {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            border: 1px solid #bae6fd;
        }

        .avatar-img {
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-preview-box {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .stat-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
        }

        .stat-card .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .auth-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .form-control:focus, .form-select:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
    </style>
</head>
<body>
