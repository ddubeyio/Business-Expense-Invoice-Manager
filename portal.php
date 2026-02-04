<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$hash = $_GET['v'] ?? '';

if (empty($hash)) {
    die("Invalid Access Link.");
}

try {
    $stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.address as client_address, c.tax_id as client_tax_id FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.hash = ?");
    $stmt->execute([$hash]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die("Invoice not found or link expired.");
    }

    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice->id]);
    $items = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$invoice->id]);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = "Invoice View - " . $invoice->invoice_number;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
        }

        .invoice-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            padding: 50px;
        }

        .invoice-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                background: #fff;
            }

            .invoice-card {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h4 class="mb-0 text-secondary">Client Portal</h4>
            <div>
                <button onclick="window.print()" class="btn btn-outline-dark me-2"><i class="fas fa-print me-2"></i> Print</button>
                <?php if ($invoice->due_amount > 0): ?>
                    <button class="btn btn-primary"><i class="fas fa-credit-card me-2"></i> Pay Now</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="invoice-card mx-auto" style="max-width: 900px;">
            <div class="invoice-header d-flex justify-content-between align-items-start">
                <div>
                    <?php $logo = getSetting('company_logo'); ?>
                    <?php if ($logo): ?>
                        <img src="assets/uploads/company/<?php echo $logo; ?>" style="height: 60px; margin-bottom: 15px;">
                    <?php endif; ?>
                    <h3 class="fw-bold mb-1"><?php echo getSetting('company_name'); ?></h3>
                    <p class="text-secondary small mb-0">
                        <?php echo nl2br(getSetting('company_address')); ?><br>
                        Email: <?php echo getSetting('company_email'); ?>
                    </p>
                </div>
                <div class="text-end">
                    <h1 class="text-uppercase text-secondary opacity-25 fw-bold mb-1" style="font-size: 3rem;">INVOICE</h1>
                    <h5 class="mb-0">#<?php echo $invoice->invoice_number; ?></h5>
                    <p class="text-secondary small">Date: <?php echo date('M d, Y', strtotime($invoice->created_at)); ?></p>
                    <div class="badge <?php echo $invoice->status == 'paid' ? 'bg-success' : 'bg-warning'; ?> text-uppercase px-3 py-2">
                        <?php echo $invoice->status; ?>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-6">
                    <label class="text-secondary small fw-bold text-uppercase mb-2">Billed To:</label>
                    <h5 class="fw-bold mb-1"><?php echo $invoice->client_name; ?></h5>
                    <p class="text-secondary small mb-0">
                        <?php echo nl2br($invoice->client_address); ?><br>
                        Email: <?php echo $invoice->client_email; ?><br>
                        Tax ID: <?php echo $invoice->client_tax_id; ?>
                    </p>
                </div>
                <div class="col-6 text-end">
                    <label class="text-secondary small fw-bold text-uppercase mb-2">Payment Details:</label>
                    <p class="mb-1 fw-bold">Due Date: <span class="text-danger"><?php echo date('M d, Y', strtotime($invoice->due_date)); ?></span></p>
                    <p class="mb-0">Base Currency: <?php echo getSetting('currency'); ?></p>
                </div>
            </div>

            <table class="table table-borderless">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-bottom">
                            <td class="ps-3 py-3">
                                <div class="fw-bold"><?php echo $item->description; ?></div>
                            </td>
                            <td class="text-center py-3"><?php echo $item->quantity; ?></td>
                            <td class="text-end py-3"><?php echo formatCurrency($item->rate); ?></td>
                            <td class="text-end py-3 pe-3 fw-bold"><?php echo formatCurrency($item->total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row justify-content-end mt-4">
                <div class="col-md-5">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary font-weight-bold">Subtotal</span>
                        <span class="fw-bold"><?php echo formatCurrency($invoice->subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tax (<?php echo getSetting('tax_name'); ?>)</span>
                        <span class="fw-bold"><?php echo formatCurrency($invoice->tax_amount); ?></span>
                    </div>
                    <?php if ($invoice->discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Discount</span>
                            <span>- <?php echo formatCurrency($invoice->discount); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                        <h5 class="fw-bold">Total Amount</h5>
                        <h5 class="fw-bold text-primary"><?php echo formatCurrency($invoice->total); ?></h5>
                    </div>
                    <div class="d-flex justify-content-between text-success">
                        <span>Paid to date</span>
                        <span class="fw-bold"><?php echo formatCurrency($invoice->paid_amount); ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2 bg-light p-2 rounded">
                        <h5 class="fw-bold mb-0">Balance Due</h5>
                        <h5 class="fw-bold text-danger mb-0"><?php echo formatCurrency($invoice->total - $invoice->paid_amount); ?></h5>
                    </div>
                </div>
            </div>

            <?php if ($invoice->notes): ?>
                <div class="mt-5 pt-4 border-top">
                    <label class="text-secondary small fw-bold text-uppercase mb-2">Notes / Terms:</label>
                    <p class="text-secondary small mb-0"><?php echo nl2br($invoice->notes); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 text-secondary small no-print">
            &copy; <?php echo date('Y'); ?> <?php echo getSetting('company_name'); ?>. All rights reserved.
        </div>
    </div>

</body>

</html>