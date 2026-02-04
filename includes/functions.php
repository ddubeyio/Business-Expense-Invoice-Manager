<?php

/**
 * Helper Functions
 */

// Redirect function
function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit();
}

// Sanitize user input
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role)
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Require login
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Get setting value from database
function getSetting($key)
{
    global $pdo;
    static $settings = [];

    if (empty($settings)) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        } catch (PDOException $e) {
            return null;
        }
    }

    return isset($settings[$key]) ? $settings[$key] : null;
}

// Multi-currency Support
function updateExchangeRates()
{
    global $pdo;
    $apiKey = getSetting('exchange_api_key');
    $baseCurrency = getSetting('currency') ?: 'USD';

    if (empty($apiKey)) return false;

    // Check last update
    $stmt = $pdo->prepare("SELECT MAX(updated_at) as last_update FROM currency_rates");
    $stmt->execute();
    $lastUpdate = $stmt->fetch()->last_update;

    if ($lastUpdate && (time() - strtotime($lastUpdate)) < 86400) {
        return true; // Already updated within last 24h
    }

    $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['result'] === 'success') {
            $rates = $data['conversion_rates'];
            $stmt = $pdo->prepare("INSERT INTO currency_rates (code, rate) VALUES (?, ?) ON DUPLICATE KEY UPDATE rate = ?");
            foreach ($rates as $code => $rate) {
                $stmt->execute([$code, $rate, $rate]);
            }
            return true;
        }
    }
    return false;
}

function convertCurrency($amount, $toCurrency, $fromCurrency = null)
{
    global $pdo;
    if (!$fromCurrency) $fromCurrency = getSetting('currency') ?: 'USD';
    if ($fromCurrency == $toCurrency) return $amount;

    // Fetch rates
    $stmt = $pdo->prepare("SELECT code, rate FROM currency_rates WHERE code IN (?, ?)");
    $stmt->execute([$fromCurrency, $toCurrency]);
    $rates = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (isset($rates[$fromCurrency]) && isset($rates[$toCurrency])) {
        // Convert to intermediate base (1.0) then to target
        $baseAmount = $amount / $rates[$fromCurrency];
        return $baseAmount * $rates[$toCurrency];
    }

    return $amount; // Fallback
}

function formatCurrency($amount, $currency = null)
{
    if (!$currency) $currency = getSetting('currency') ?: 'USD';

    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'JPY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'AED' => 'DH'
    ];
    $symbol = $symbols[$currency] ?? $currency;

    return $symbol . ' ' . number_format($amount, 2);
}

// Log user activity
function logActivity($action, $details = '')
{
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $action, $details, $_SERVER['REMOTE_ADDR']]);
    }
}

// Success/Error Flash messages
function setMessage($msg, $type = 'success')
{
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = $type;
}

function displayMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $msg
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// CSRF Token generation and validation
function generateCSRF()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRF($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
