<?php
$pageTitle = "Payment History";
require_once 'includes/header.php';
requireLogin();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $invoice_id = $_POST['invoice_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_mode = sanitize($_POST['payment_mode']);
    $note = sanitize($_POST['note']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, amount, payment_date, payment_mode, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $amount, $payment_date, $payment_mode, $note]);

        // Check if invoice is fully paid
        $stmt = $pdo->prepare("SELECT total, (SELECT SUM(amount) FROM payments WHERE invoice_id = ?) as total_paid FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id, $invoice_id]);
        $inv = $stmt->fetch();

        if ($inv->total_paid >= $inv->total) {
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
            $stmt->execute([$invoice_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ?");
            $stmt->execute([$invoice_id]);
        }

        $pdo->commit();
        setMessage("Payment recorded successfully!");
        redirect('payments.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        setMessage("Error: " . $e->getMessage(), "danger");
    }
}

// Fetch Payments
$payments = $pdo->query("SELECT p.*, i.invoice_number, cl.name as client_name FROM payments p JOIN invoices i ON p.invoice_id = i.id JOIN clients cl ON i.client_id = cl.id ORDER BY p.payment_date DESC")->fetchAll();

// Fetch Unpaid/Partial Invoices for Modal
$unpaidInvoices = $pdo->query("SELECT i.id, i.invoice_number, i.total, (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount, cl.name as client_name FROM invoices i JOIN clients cl ON i.client_id = cl.id WHERE i.status NOT IN ('paid', 'cancelled')")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Payments</h3>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="fas fa-plus me-2"></i> Record Payment
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Note</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="ps-4 text-secondary"><?php echo date('M d, Y', strtotime($pay->payment_date)); ?></td>
                            <td class="fw-medium text-primary">#<?php echo $pay->invoice_number; ?></td>
                            <td><?php echo $pay->client_name; ?></td>
                            <td class="fw-bold text-success"><?php echo formatCurrency($pay->amount); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $pay->payment_mode; ?></span></td>
                            <td class="text-secondary small"><?php echo $pay->note; ?></td>
                            <td class="pe-4 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Delete this payment recording?')) window.location.href='delete-payment.php?id=<?php echo $pay->id; ?>'">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($payments)) echo '<tr><td colspan="7" class="text-center py-4">No payments recorded</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Invoice</label>
                        <select name="invoice_id" class="form-select" id="invoiceSelect" required>
                            <option value="">-- Choose Invoice --</option>
                            <?php foreach ($unpaidInvoices as $ui):
                                $bal = $ui->total - ($ui->paid_amount ?: 0);
                            ?>
                                <option value="<?php echo $ui->id; ?>" data-balance="<?php echo $bal; ?>">
                                    #<?php echo $ui->invoice_number; ?> - <?php echo $ui->client_name; ?> (Bal: <?php echo formatCurrency($bal); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI / Digital Pocket</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Reference number, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="record_payment" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#invoiceSelect').change(function() {
            var balance = $(this).find(':selected').data('balance');
            if (balance) {
                $('#paymentAmount').val(balance.toFixed(2));
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>