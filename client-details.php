<?php
$pageTitle = "Client History";
require_once 'includes/header.php';
requireLogin();

if (!isset($_GET['id'])) {
    redirect('clients.php');
}

$id = $_GET['id'];

try {
    // Fetch Client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch();

    if (!$client) {
        setMessage("Client not found.", "danger");
        redirect('clients.php');
    }

    // Fetch Invoices
    $stmt = $pdo->prepare("SELECT i.*, (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount FROM invoices i WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $invoices = $stmt->fetchAll();

    // Summary Stats
    $billed = array_sum(array_column($invoices, 'total'));
    $paid = array_sum(array_column($invoices, 'paid_amount'));
    $outstanding = $billed - $paid;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold"><?php echo $client->name; ?></h4>
                        <span class="text-secondary small">Client since <?php echo date('M Y', strtotime($client->created_at)); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small text-secondary fw-bold text-uppercase">Contact Information</label>
                    <div class="mt-1">
                        <p class="mb-1"><i class="fas fa-envelope me-2 text-primary"></i> <?php echo $client->email ?: 'N/A'; ?></p>
                        <p class="mb-1"><i class="fas fa-phone me-2 text-primary"></i> <?php echo $client->phone ?: 'N/A'; ?></p>
                        <p class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i> <?php echo $client->address ?: 'N/A'; ?></p>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="small text-secondary fw-bold text-uppercase">Tax Configuration</label>
                    <div class="mt-1">
                        <p class="mb-0 fw-medium">VAT/GST ID: <?php echo $client->tax_id ?: 'Not provided'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-indigo-light">
            <div class="card-body p-4">
                <h6 class="text-indigo fw-bold text-uppercase small mb-3">Financial Standing</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Total Billed</span>
                    <span class="fw-bold"><?php echo formatCurrency($billed); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>Total Paid</span>
                    <span class="fw-bold"><?php echo formatCurrency($paid); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between text-danger">
                    <span class="fw-bold">Outstanding</span>
                    <h5 class="fw-bold mb-0"><?php echo formatCurrency($outstanding); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Invoice History</h5>
                <a href="create-invoice.php?client_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">New Invoice</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv):
                                $statusClass = [
                                    'paid' => 'bg-success-subtle text-success',
                                    'draft' => 'bg-secondary-subtle text-secondary',
                                    'sent' => 'bg-primary-subtle text-primary',
                                    'overdue' => 'bg-danger-subtle text-danger',
                                    'cancelled' => 'bg-dark-subtle text-dark'
                                ][$inv->status];
                            ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-primary">#<?php echo $inv->invoice_number; ?></td>
                                    <td class="text-secondary small"><?php echo date('M d, Y', strtotime($inv->created_at)); ?></td>
                                    <td class="fw-bold"><?php echo formatCurrency($inv->total); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?> px-3 py-2 rounded">
                                            <?php echo ucfirst($inv->status); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="view-invoice.php?id=<?php echo $inv->id; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach;
                            if (empty($invoices)) echo '<tr><td colspan="5" class="text-center py-5 text-secondary">No invoices found for this client.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>