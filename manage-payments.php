<?php
$pageTitle = "Manage Payments";
require_once 'includes/header.php';
requireLogin();

$invoice_id = $_GET['invoice_id'] ?? null;
if (!$invoice_id) redirect('invoices.php');

// Fetch Invoice Details
$stmt = $pdo->prepare("SELECT i.*, cl.name as client_name, (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as total_paid FROM invoices i JOIN clients cl ON i.client_id = cl.id WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) redirect('invoices.php');

$balance = $invoice->total - ($invoice->total_paid ?: 0);

// Fetch Payments for this invoice
$stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$stmt->execute([$invoice_id]);
$payments = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white p-3">
                <h5 class="mb-0">Record New Payment</h5>
            </div>
            <div class="card-body">
                <div class="mb-4 bg-light p-3 rounded-2 text-center">
                    <span class="text-secondary small d-block">Outstanding Balance</span>
                    <h3 class="fw-bold text-danger mb-0"><?php echo formatCurrency($balance); ?></h3>
                </div>

                <?php if ($balance > 0): ?>
                    <form action="payments.php" method="POST">
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $balance; ?>" max="<?php echo $balance; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" name="record_payment" class="btn btn-primary w-100">Record Payment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle me-2"></i> This invoice is fully paid.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white p-3 d-flex justify-content-between">
                <h5 class="mb-0">Payment History for #<?php echo $invoice->invoice_number; ?></h5>
                <span class="small text-secondary"><?php echo $invoice->client_name; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Mode</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td class="ps-4"><?php echo date('M d, Y', strtotime($p->payment_date)); ?></td>
                                    <td><?php echo $p->payment_mode; ?></td>
                                    <td class="text-end pe-4 fw-medium"><?php echo formatCurrency($p->amount); ?></td>
                                </tr>
                            <?php endforeach;
                            if (empty($payments)) echo '<tr><td colspan="3" class="text-center py-4">No payments found</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>