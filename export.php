<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if ($type == 'reports') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    // Fetch Payments (Income)
    $stmt = $pdo->prepare("SELECT p.*, i.invoice_number, c.name as client_name FROM payments p JOIN invoices i ON p.invoice_id = i.id JOIN clients c ON i.client_id = c.id WHERE p.payment_date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Expenses
    $stmt = $pdo->prepare("SELECT e.*, c.name as category_name FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.expense_date BETWEEN ? AND ? AND e.status = 'approved'");
    $stmt->execute([$start_date, $end_date]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Financial_Report_{$start_date}_to_{$end_date}.csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Income Section
    fputcsv($output, ['INCOME / PAYMENTS']);
    fputcsv($output, ['Date', 'Invoice #', 'Client', 'Method', 'Amount']);
    foreach ($payments as $row) {
        fputcsv($output, [$row['payment_date'], $row['invoice_number'], $row['client_name'], $row['payment_mode'], $row['amount']]);
    }

    fputcsv($output, []); // Empty row

    // Expense Section
    fputcsv($output, ['EXPENSES']);
    fputcsv($output, ['Date', 'Vendor', 'Category', 'Mode', 'Amount']);
    foreach ($expenses as $row) {
        fputcsv($output, [$row['expense_date'], $row['vendor'], $row['category_name'], $row['payment_mode'], $row['amount']]);
    }

    fclose($output);
    exit();
}

if ($type == 'invoices') {
    $stmt = $pdo->query("SELECT i.*, c.name as client_name FROM invoices i JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Invoices_Export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Invoice #', 'Client', 'Total', 'Status', 'Date', 'Due Date']);
    foreach ($invoices as $row) {
        fputcsv($output, [$row['invoice_number'], $row['client_name'], $row['total'], $row['status'], $row['created_at'], $row['due_date']]);
    }
    fclose($output);
    exit();
}

redirect('index.php');
