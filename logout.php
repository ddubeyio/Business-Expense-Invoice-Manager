<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

logActivity('User Logout', "User {$_SESSION['user_email']} logged out");

session_destroy();
redirect('login.php');
