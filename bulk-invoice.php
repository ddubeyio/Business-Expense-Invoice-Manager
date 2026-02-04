<?php
$pageTitle = "Bulk Invoice Generation";
require_once 'includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_bulk'])) {
    $client_ids = $_POST['client_ids'] ?? [];
    $description = sanitize($_POST['description']);
    $amount = (float)$_POST['amount'];
    $due_date = $_POST['due_date'];

    if (empty($client_ids) || empty($description) || $amount <= 0) {
        setMessage("Please select clients and enter valid invoice details.", "danger");
    } else {
        try {
            $pdo->beginTransaction();
            $stmtInv = $pdo->prepare("INSERT INTO invoices (client_id, invoice_number, hash, subtotal, total, due_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, 'sent', ?)");
            $stmtItem = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, rate, total) VALUES (?, ?, 1, ?, ?)");

            $prefix = getSetting('invoice_prefix') ?: 'INV-';
            $successCount = 0;

            foreach ($client_ids as $cid) {
                $invNum = $prefix . mt_rand(100000, 999999);
                $hash = bin2hex(random_bytes(16));

                $stmtInv->execute([$cid, $invNum, $hash, $amount, $amount, $due_date, "Bulk generated invoice."]);
                $invId = $pdo->lastInsertId();

                $stmtItem->execute([$invId, $description, $amount, $amount]);
                $successCount++;
            }

            $pdo->commit();
            setMessage("Successfully generated $successCount invoices.");
            redirect('invoices.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }
}

$clients = $pdo->query("SELECT id, name, email FROM clients ORDER BY name ASC")->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-3">
                <h5 class="mb-0">Generate Bulk Invoices</h5>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. Select Clients</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="form-check mb-2">
                                    <input type="checkbox" id="checkAll" class="form-check-input">
                                    <label class="form-check-label fw-bold text-primary">Select All Clients</label>
                                </div>
                                <hr>
                                <?php foreach ($clients as $c): ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="client_ids[]" value="<?php echo $c->id; ?>" class="form-check-input client-check">
                                        <label class="form-check-label"><?php echo $c->name; ?> (<?php echo $c->email; ?>)</label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. Invoice Details</label>
                            <div class="mb-3">
                                <label class="small text-secondary">Description of Service/Item</label>
                                <input type="text" name="description" class="form-control" placeholder="e.g. Monthly Maintenance Fee" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="small text-secondary">Amount per Unit</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                                </div>
                                <div class="col-6">
                                    <label class="small text-secondary">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                            </div>
                            <div class="alert alert-info small border-0">
                                <i class="fas fa-info-circle me-2"></i> This will create a separate invoice for each selected client with the details provided above.
                            </div>
                            <button type="submit" name="generate_bulk" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-magic me-2"></i> Generate Invoices Now
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        const checks = document.querySelectorAll('.client-check');
        checks.forEach(c => c.checked = this.checked);
    });
</script>

<?php require_once 'includes/footer.php'; ?>