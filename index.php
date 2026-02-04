<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';
requireLogin();

// Fetch statistics
try {
    // Total Expenses (Current Month)
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE()) AND status = 'approved'");
    $stmt->execute();
    $monthExpenses = $stmt->fetch()->total ?: 0;

    // Total Invoiced (Current Month)
    $stmt = $pdo->prepare("SELECT SUM(total) as total FROM invoices WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'cancelled'");
    $stmt->execute();
    $monthInvoiced = $stmt->fetch()->total ?: 0;

    // Total Paid (All time)
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments");
    $stmt->execute();
    $totalPaid = $stmt->fetch()->total ?: 0;

    // Pending Invoices
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE status IN ('sent', 'overdue')");
    $stmt->execute();
    $pendingInvoicesCount = $stmt->fetch()->count;

    // Recent Expenses
    $stmt = $pdo->query("SELECT e.*, c.name as category_name FROM expenses e LEFT JOIN categories c ON e.category_id = c.id ORDER BY expense_date DESC LIMIT 5");
    $recentExpenses = $stmt->fetchAll();

    // Recent Invoices
    $stmt = $pdo->query("SELECT i.*, cl.name as client_name FROM invoices i LEFT JOIN clients cl ON i.client_id = cl.id ORDER BY i.created_at DESC LIMIT 5");
    $recentInvoices = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 stat-card">
            <div class="card-body">
                <div class="stat-icon bg-emerald-light">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h6 class="text-secondary mb-1">Monthly Sales</h6>
                <h3 class="mb-0 fw-bold"><?php echo formatCurrency($monthInvoiced); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 stat-card">
            <div class="card-body">
                <div class="stat-icon bg-rose-light">
                    <i class="fas fa-receipt"></i>
                </div>
                <h6 class="text-secondary mb-1">Monthly Expenses</h6>
                <h3 class="mb-0 fw-bold"><?php echo formatCurrency($monthExpenses); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 stat-card">
            <div class="card-body">
                <div class="stat-icon bg-indigo-light">
                    <i class="fas fa-wallet"></i>
                </div>
                <h6 class="text-secondary mb-1">Total Payments</h6>
                <h3 class="mb-0 fw-bold"><?php echo formatCurrency($totalPaid); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 stat-card">
            <div class="card-body">
                <div class="stat-icon bg-amber-light">
                    <i class="fas fa-clock"></i>
                </div>
                <h6 class="text-secondary mb-1">Pending Invoices</h6>
                <h3 class="mb-0 fw-bold"><?php echo $pendingInvoicesCount; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Invoices -->
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Invoices</span>
                <a href="invoices.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInvoices as $inv): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-primary">#<?php echo $inv->invoice_number; ?></td>
                                    <td><?php echo $inv->client_name; ?></td>
                                    <td><?php echo formatCurrency($inv->total); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'paid' => 'bg-success',
                                            'draft' => 'bg-secondary',
                                            'sent' => 'bg-primary',
                                            'overdue' => 'bg-danger',
                                            'cancelled' => 'bg-dark'
                                        ][$inv->status];
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                            <?php echo ucfirst($inv->status); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-secondary"><?php echo date('M d, Y', strtotime($inv->created_at)); ?></td>
                                </tr>
                            <?php endforeach;
                            if (empty($recentInvoices)) echo '<tr><td colspan="5" class="text-center py-4">No recent invoices</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Expenses</span>
                <a href="expenses.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentExpenses as $exp): ?>
                        <li class="list-group-item p-3 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium"><?php echo $exp->vendor ?: 'Direct Expense'; ?></span>
                                <span class="fw-bold text-danger">- <?php echo formatCurrency($exp->amount); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-secondary"><?php echo $exp->category_name; ?></span>
                                <span class="small text-secondary"><?php echo date('M d', strtotime($exp->expense_date)); ?></span>
                            </div>
                        </li>
                    <?php endforeach;
                    if (empty($recentExpenses)) echo '<li class="list-group-item text-center py-4">No recent expenses</li>'; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>