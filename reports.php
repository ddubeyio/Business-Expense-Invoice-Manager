<?php
$pageTitle = "Financial Reports";
require_once 'includes/header.php';
requireLogin();

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

try {
    // Total Income (Paid Invoices)
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $totalIncome = $stmt->fetch()->total ?: 0;

    // Total Expenses
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ? AND status = 'approved'");
    $stmt->execute([$start_date, $end_date]);
    $totalExpense = $stmt->fetch()->total ?: 0;

    // Net Profit
    $netProfit = $totalIncome - $totalExpense;

    // Monthly Expense Breakdown by Category
    $stmt = $pdo->prepare("SELECT c.name, SUM(e.amount) as total FROM expenses e JOIN categories c ON e.category_id = c.id WHERE e.expense_date BETWEEN ? AND ? AND e.status = 'approved' GROUP BY c.id");
    $stmt->execute([$start_date, $end_date]);
    $categoryData = $stmt->fetchAll();

    // Last 6 months trend
    $stmt = $pdo->query("
        SELECT 
            month_name,
            SUM(expense_amt) as expense_total,
            SUM(income_amt) as income_total
        FROM (
            SELECT DATE_FORMAT(dt, '%b %Y') as month_name, 0 as expense_amt, 0 as income_amt, dt
            FROM (
                SELECT CURDATE() - INTERVAL (a.a) MONTH as dt
                FROM (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) AS a
            ) m
            UNION ALL
            SELECT DATE_FORMAT(expense_date, '%b %Y'), amount, 0, expense_date
            FROM expenses 
            WHERE status = 'approved' AND expense_date >= CURDATE() - INTERVAL 6 MONTH
            UNION ALL
            SELECT DATE_FORMAT(payment_date, '%b %Y'), 0, amount, payment_date
            FROM payments 
            WHERE payment_date >= CURDATE() - INTERVAL 6 MONTH
        ) combined
        GROUP BY month_name
        ORDER BY MIN(dt) ASC
    ");
    $trendData = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Report Error: " . $e->getMessage();
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i> Apply Filter
                </button>
            </div>
            <div class="col-md-1">
                <a href="export.php?type=reports&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-outline-success w-100" title="Export to CSV">
                    <i class="fas fa-file-csv"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <h6 class="opacity-75">Total Revenue</h6>
                <h2 class="fw-bold mb-0"><?php echo formatCurrency($totalIncome); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger text-white">
            <div class="card-body p-4">
                <h6 class="opacity-75">Total Expenses</h6>
                <h2 class="fw-bold mb-0"><?php echo formatCurrency($totalExpense); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm <?php echo $netProfit >= 0 ? 'bg-success' : 'bg-dark'; ?> text-white">
            <div class="card-body p-4">
                <h6 class="opacity-75">Net Profit/Loss</h6>
                <h2 class="fw-bold mb-0"><?php echo formatCurrency($netProfit); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Revenue vs Expense Trend</h5>
            </div>
            <div class="card-body">
                <div style="height: 350px; position: relative;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Expense by Category</h5>
            </div>
            <div class="card-body">
                <div style="height: 350px; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trendData, 'month_name')); ?>,
                datasets: [{
                        label: 'Income',
                        data: <?php echo json_encode(array_column($trendData, 'income_total')); ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Expense',
                        data: <?php echo json_encode(array_column($trendData, 'expense_total')); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($categoryData, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($categoryData, 'total')); ?>,
                    backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#8b5cf6', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>