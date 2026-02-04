<?php
$pageTitle = "Create Invoice";
require_once 'includes/header.php';
requireLogin();

// Fetch Clients
$clients = $pdo->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_invoice'])) {
    $client_id = $_POST['client_id'];
    $invoice_number = sanitize($_POST['invoice_number']);
    $due_date = $_POST['due_date'];
    $notes = sanitize($_POST['notes']);
    $discount = (float)$_POST['discount'];
    $tax_rate = (float)getSetting('tax_rate');

    $descriptions = $_POST['desc'];
    $quantities = $_POST['qty'];
    $rates = $_POST['rate'];

    $subtotal = 0;
    foreach ($quantities as $i => $qty) {
        $subtotal += $qty * $rates[$i];
    }

    $tax_amount = ($subtotal - $discount) * ($tax_rate / 100);
    $total = $subtotal - $discount + $tax_amount;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO invoices (client_id, user_id, invoice_number, subtotal, tax_amount, discount, total, due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)");
        $stmt->execute([$client_id, $_SESSION['user_id'], $invoice_number, $subtotal, $tax_amount, $discount, $total, $due_date, $notes]);
        $invoice_id = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, rate, tax_percent, total) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($descriptions as $i => $desc) {
            $item_qty = (float)$quantities[$i];
            $item_rate = (float)$rates[$i];
            $item_total = $item_qty * $item_rate;
            $stmt_item->execute([$invoice_id, sanitize($desc), $item_qty, $item_rate, $tax_rate, $item_total]);
        }

        $pdo->commit();
        setMessage("Invoice created successfully!");
        redirect('invoices.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        setMessage("Error: " . $e->getMessage(), "danger");
    }
}

// Generate Invoice Number
$last_inv = $pdo->query("SELECT invoice_number FROM invoices ORDER BY id DESC LIMIT 1")->fetch();
$prefix = getSetting('invoice_prefix') ?: 'INV-';
$next_number = $last_inv ? (int)substr($last_inv->invoice_number, strlen($prefix)) + 1 : 1001;
$suggested_inv = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
?>

<form action="" method="POST" id="invoiceForm">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="mb-0">Create New Invoice</h3>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" name="save_invoice" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Save Invoice
            </button>
        </div>
    </div>

    <div class="row shadow-sm">
        <div class="col-md-8">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Client</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">-- Choose Client --</option>
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?php echo $cl->id; ?>"><?php echo $cl->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2">
                                <a href="clients.php" class="small text-decoration-none">+ Add New Client</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Invoice #</label>
                                    <input type="text" name="invoice_number" class="form-control" value="<?php echo $suggested_inv; ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Description</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row">
                                    <td><input type="text" name="desc[]" class="form-control" placeholder="Item description" required></td>
                                    <td><input type="number" step="0.01" name="qty[]" class="form-control qty" value="1" required></td>
                                    <td><input type="number" step="0.01" name="rate[]" class="form-control rate" value="0.00" required></td>
                                    <td><span class="row-total fw-medium">0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-times"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                            <i class="fas fa-plus me-1"></i> Add Item
                        </button>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold">Notes / Terms</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes to be shown on invoice"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-4 mt-md-0">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h5 class="mb-4">Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="subtotal_display">0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span>Discount</span>
                        <input type="number" step="0.01" name="discount" id="discount_input" class="form-control form-control-sm text-end w-50" value="0.00">
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (<?php echo getSetting('tax_rate'); ?>%)</span>
                        <span id="tax_display">0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-0 h4 fw-bold text-primary">
                        <span>Total</span>
                        <span id="total_display">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        const taxRate = <?php echo (float)getSetting('tax_rate'); ?>;

        function calculateTotals() {
            let subtotal = 0;
            $('.item-row').each(function() {
                const qty = parseFloat($(this).find('.qty').val()) || 0;
                const rate = parseFloat($(this).find('.rate').val()) || 0;
                const rowTotal = qty * rate;
                $(this).find('.row-total').text(rowTotal.toFixed(2));
                subtotal += rowTotal;
            });

            const discount = parseFloat($('#discount_input').val()) || 0;
            const taxableAmount = subtotal - discount;
            const tax = taxableAmount * (taxRate / 100);
            const total = taxableAmount + tax;

            $('#subtotal_display').text(subtotal.toFixed(2));
            $('#tax_display').text(tax.toFixed(2));
            $('#total_display').text(total.toFixed(2));
        }

        $('#addRow').click(function() {
            const newRow = `
            <tr class="item-row">
                <td><input type="text" name="desc[]" class="form-control" placeholder="Item description" required></td>
                <td><input type="number" step="0.01" name="qty[]" class="form-control qty" value="1" required></td>
                <td><input type="number" step="0.01" name="rate[]" class="form-control rate" value="0.00" required></td>
                <td><span class="row-total fw-medium">0.00</span></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fas fa-times"></i></button></td>
            </tr>`;
            $('#itemsTable tbody').append(newRow);
        });

        $(document).on('click', '.remove-row', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
            }
        });

        $(document).on('input', '.qty, .rate, #discount_input', function() {
            calculateTotals();
        });

        calculateTotals();
    });
</script>

<?php require_once 'includes/footer.php'; ?>