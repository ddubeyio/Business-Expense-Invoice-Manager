<?php
$pageTitle = "View Invoice";
require_once 'includes/header.php';
requireLogin();

if (!isset($_GET['id'])) {
    redirect('invoices.php');
}

$id = $_GET['id'];

try {
    // Fetch Invoice
    $stmt = $pdo->prepare("SELECT i.*, cl.name as client_name, cl.email as client_email, cl.phone as client_phone, cl.address as client_address, cl.tax_id as client_tax_id FROM invoices i JOIN clients cl ON i.client_id = cl.id WHERE i.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        setMessage("Invoice not found.", "danger");
        redirect('invoices.php');
    }

    // Fetch Items
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    // Fetch Payments
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ?");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll();
    $totalPaid = array_sum(array_column($payments, 'amount'));
    $balance = $invoice->total - $totalPaid;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="row mb-4 d-print-none">
    <div class="col-8">
        <a href="invoices.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Invoices</a>
    </div>
    <div class="col-4 text-end">
        <button onclick="window.print();" class="btn btn-primary"><i class="fas fa-print me-2"></i> Print Invoice</button>
    </div>
</div>

<div class="invoice-container bg-white p-5 shadow-sm rounded-3">
    <div class="row mb-5">
        <div class="col-6">
            <h1 class="fw-bold mb-0 text-primary"><?php echo getSetting('company_name'); ?></h1>
            <p class="text-secondary mb-0"><?php echo getSetting('company_address'); ?></p>
            <p class="text-secondary mb-0">Email: <?php echo getSetting('company_email'); ?></p>
            <p class="text-secondary">Phone: <?php echo getSetting('company_phone'); ?></p>
        </div>
        <div class="col-6 text-end">
            <h2 class="text-uppercase fw-bold text-secondary">Invoice</h2>
            <div class="h5 mb-1">#<?php echo $invoice->invoice_number; ?></div>
            <div class="text-secondary">Date: <?php echo date('M d, Y', strtotime($invoice->created_at)); ?></div>
            <div class="text-secondary">Due Date: <?php echo date('M d, Y', strtotime($invoice->due_date)); ?></div>
            <div class="mt-3">
                <?php
                $statusColor = [
                    'paid' => 'bg-success',
                    'sent' => 'bg-primary',
                    'overdue' => 'bg-danger',
                    'draft' => 'bg-secondary'
                ][$invoice->status];
                ?>
                <span class="badge <?php echo $statusColor; ?> px-4 py-2 text-uppercase"><?php echo $invoice->status; ?></span>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-uppercase text-secondary small fw-bold">Bill To:</h6>
            <h5 class="fw-bold mb-1"><?php echo $invoice->client_name; ?></h5>
            <p class="text-secondary mb-0"><?php echo $invoice->client_address; ?></p>
            <p class="text-secondary mb-0">Phone: <?php echo $invoice->client_phone; ?></p>
            <p class="text-secondary mb-0">Email: <?php echo $invoice->client_email; ?></p>
            <?php if ($invoice->client_tax_id): ?>
                <p class="text-secondary">VAT/Tax ID: <?php echo $invoice->client_tax_id; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table class="table table-borderless mb-5">
        <thead>
            <tr class="border-bottom text-uppercase small text-secondary">
                <th class="py-3">Description</th>
                <th class="py-3 text-center" style="width: 100px;">Qty</th>
                <th class="py-3 text-end" style="width: 150px;">Rate</th>
                <th class="py-3 text-end" style="width: 150px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="border-bottom">
                    <td class="py-4">
                        <div class="fw-bold"><?php echo $item->description; ?></div>
                    </td>
                    <td class="py-4 text-center"><?php echo (float)$item->quantity; ?></td>
                    <td class="py-4 text-end"><?php echo formatCurrency($item->rate); ?></td>
                    <td class="py-4 text-end fw-medium"><?php echo formatCurrency($item->total); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"></td>
                <td class="pt-4 text-end text-secondary">Subtotal</td>
                <td class="pt-4 text-end fw-medium"><?php echo formatCurrency($invoice->subtotal); ?></td>
            </tr>
            <?php if ($invoice->discount > 0): ?>
                <tr>
                    <td colspan="2"></td>
                    <td class="text-end text-secondary">Discount</td>
                    <td class="text-end text-danger">- <?php echo formatCurrency($invoice->discount); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td colspan="2"></td>
                <td class="text-end text-secondary"><?php echo getSetting('tax_name'); ?> (<?php echo getSetting('tax_rate'); ?>%)</td>
                <td class="text-end fw-medium"><?php echo formatCurrency($invoice->tax_amount); ?></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="text-end h4 fw-bold">Total</td>
                <td class="text-end h4 fw-bold text-primary"><?php echo formatCurrency($invoice->total); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row">
        <div class="col-8">
            <h6 class="text-uppercase text-secondary small fw-bold">Notes:</h6>
            <p class="text-secondary"><?php echo $invoice->notes ?: 'No additional notes provided.'; ?></p>

            <h6 class="text-uppercase text-secondary small fw-bold mt-4">Payment History:</h6>
            <div class="table-responsive">
                <table class="table table-sm table-borderless small">
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="text-secondary"><?php echo date('M d, Y', strtotime($p->payment_date)); ?></td>
                                <td class="text-secondary"><?php echo $p->payment_mode; ?></td>
                                <td class="text-end fw-medium"><?php echo formatCurrency($p->amount); ?></td>
                            </tr>
                        <?php endforeach;
                        if (empty($payments)) echo '<tr><td colspan="3" class="text-secondary">No payments recorded yet.</td></tr>'; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-4">
            <div class="bg-light p-4 rounded-3 text-center">
                <h6 class="text-uppercase text-secondary small fw-bold mb-2">Amount Due</h6>
                <h3 class="fw-bold mb-0 text-danger"><?php echo formatCurrency($balance); ?></h3>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background-color: #fff !important;
        }

        #sidebar,
        .navbar,
        .d-print-none {
            display: none !important;
        }

        #content {
            padding: 0 !important;
            width: 100% !important;
            margin: 0 !important;
        }

        .invoice-container {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        .wrapper {
            display: block !important;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>