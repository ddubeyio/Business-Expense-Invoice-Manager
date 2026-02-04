<?php
ob_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_manage');

// Application Settings
define('BASE_URL', 'http://localhost/expense-manage/');
define('APP_NAME', 'BizManager');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default Timezone
date_default_timezone_set('UTC');
