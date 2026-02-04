<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Auto-update exchange rates if API key is set
if (isLoggedIn() && getSetting('exchange_api_key')) {
    updateExchangeRates();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . getSetting('company_name') : getSetting('company_name'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #64748b;
            --bg-color: #f8fafc;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: #1e293b;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* Sidebar Style */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: all 0.3s;
            min-height: 100vh;
        }

        #sidebar.active {
            margin-left: -260px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #0f172a;
            text-align: center;
        }

        #sidebar .sidebar-header h3 {
            color: #fff;
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul p {
            color: #fff;
            padding: 10px;
        }

        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1rem;
            display: block;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: 0.3s;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar ul li.active>a {
            color: #fff;
            background: var(--primary-color);
        }

        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Content Style */
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.2rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .bg-indigo-light {
            background-color: #e0e7ff;
            color: #4338ca;
        }

        .bg-emerald-light {
            background-color: #d1fae5;
            color: #059669;
        }

        .bg-rose-light {
            background-color: #ffe4e6;
            color: #e11d48;
        }

        .bg-amber-light {
            background-color: #fef3c7;
            color: #d97706;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px;
            }

            #sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <?php if (isLoggedIn()): ?>
            <!-- Sidebar -->
            <nav id="sidebar">
                <div class="sidebar-header">
                    <h3><?php echo APP_NAME; ?></h3>
                </div>

                <ul class="list-unstyled components">
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'expenses') !== false ? 'active' : ''; ?>">
                        <a href="expenses.php"><i class="fas fa-receipt"></i> Expenses</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'invoices') !== false ? 'active' : ''; ?>">
                        <a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'bulk-invoice') !== false ? 'active' : ''; ?>">
                        <a href="bulk-invoice.php"><i class="fas fa-layer-group"></i> Bulk Invoices</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'clients') !== false ? 'active' : ''; ?>">
                        <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'payments') !== false ? 'active' : ''; ?>">
                        <a href="payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a>
                    </li>
                    <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>">
                        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
                    </li>
                    <?php if (hasRole('admin')): ?>
                        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : ''; ?>">
                            <a href="users.php"><i class="fas fa-user-shield"></i> User Management</a>
                        </li>
                        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : ''; ?>">
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Page Content -->
        <div id="content">
            <?php if (isLoggedIn()): ?>
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-align-left"></i>
                        </button>

                        <h4 class="mb-0 d-none d-md-block"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h4>

                        <div class="ms-auto d-flex align-items-center">
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <img src="assets/uploads/profiles/<?php echo $_SESSION['user_image'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="35" height="35">
                                    <span class="fw-semibold"><?php echo $_SESSION['user_name']; ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> Profile</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
            <?php endif; ?>

            <div class="container-fluid">
                <?php displayMessage(); ?>