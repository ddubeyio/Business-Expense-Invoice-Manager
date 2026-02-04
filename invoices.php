<?php
$pageTitle = "Invoice Management";
require_once 'includes/header.php';
requireLogin();

// Fetch Invoices
$invoices = $pdo->query("SELECT i.*, cl.name as client_name, (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount FROM invoices i LEFT JOIN clients cl ON i.client_id = cl.id ORDER BY i.created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Invoices</h3>
    <a href="create-invoice.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Create Invoice
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Client</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv):
                        $paid = $inv->paid_amount ?: 0;
                        $balance = $inv->total - $paid;
                        $statusClass = [
                            'paid' => 'bg-success-subtle text-success',
                            'draft' => 'bg-secondary-subtle text-secondary',
                            'sent' => 'bg-primary-subtle text-primary',
                            'overdue' => 'bg-danger-subtle text-danger',
                            'cancelled' => 'bg-dark-subtle text-dark'
                        ][$inv->status];
                    ?>
                        <tr>
                            <td class="ps-4 fw-medium">#<?php echo $inv->invoice_number; ?></td>
                            <td><?php echo $inv->client_name; ?></td>
                            <td class="fw-bold"><?php echo formatCurrency($inv->total); ?></td>
                            <td class="text-success"><?php echo formatCurrency($paid); ?></td>
                            <td class="text-danger"><?php echo formatCurrency($balance); ?></td>
                            <td>
                                <span class="badge <?php echo $statusClass; ?> px-3 py-2 rounded">
                                    <?php echo ucfirst($inv->status); ?>
                                </span>
                            </td>
                            <td><?php echo $inv->due_date ? date('M d, Y', strtotime($inv->due_date)) : '-'; ?></td>
                            <td class="pe-4 text-end">
                                <div class="btn-group">
                                    <a href="view-invoice.php?id=<?php echo $inv->id; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="portal.php?v=<?php echo $inv->hash; ?>" target="_blank"><i class="fas fa-external-link-alt me-2"></i> Client Portal</a></li>
                                        <li><a class="dropdown-item" href="view-invoice.php?id=<?php echo $inv->id; ?>&download=1"><i class="fas fa-download me-2"></i> Download PDF</a></li>
                                        <li><a class="dropdown-item" href="manage-payments.php?invoice_id=<?php echo $inv->id; ?>"><i class="fas fa-money-bill me-2"></i> Record Payment</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="delete-invoice.php?id=<?php echo $inv->id; ?>" onclick="return confirm('Delete this invoice?')"><i class="fas fa-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($invoices)) echo '<tr><td colspan="8" class="text-center py-4">No invoices found</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>