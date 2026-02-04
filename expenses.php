<?php
$pageTitle = "Expense Management";
require_once 'includes/header.php';
requireLogin();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_expense'])) {
        $category_id = $_POST['category_id'];
        $vendor = sanitize($_POST['vendor']);
        $amount = (float)$_POST['amount'];
        $tax = (float)$_POST['tax'];
        $expense_date = $_POST['expense_date'];
        $payment_mode = sanitize($_POST['payment_mode']);
        $description = sanitize($_POST['description']);
        $user_id = $_SESSION['user_id'];
        $status = hasRole('admin') ? 'approved' : 'pending';

        // File Upload
        $attachment = '';
        if (!empty($_FILES['attachment']['name'])) {
            $targetDir = "assets/uploads/expenses/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            $targetFilePath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
                $attachment = $fileName;
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (category_id, user_id, vendor, amount, tax, expense_date, payment_mode, description, attachment, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $user_id, $vendor, $amount, $tax, $expense_date, $payment_mode, $description, $attachment, $status]);
            setMessage("Expense recorded successfully!");
            logActivity('Expense Added', "Added expense: $vendor - " . formatCurrency($amount));
            redirect('expenses.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }

    if (isset($_POST['delete_expense'])) {
        $id = $_POST['expense_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            setMessage("Expense deleted successfully!");
            redirect('expenses.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }
}

// Fetch Expenses
$expenses = $pdo->query("SELECT e.*, c.name as category_name, u.name as user_name FROM expenses e LEFT JOIN categories c ON e.category_id = c.id LEFT JOIN users u ON e.user_id = u.id ORDER BY expense_date DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'expense'")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Expenses</h3>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
        <i class="fas fa-plus me-2"></i> Add Expense
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Vendor / Details</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td class="ps-4 text-secondary"><?php echo date('M d, Y', strtotime($exp->expense_date)); ?></td>
                            <td>
                                <div class="fw-medium text-dark"><?php echo $exp->vendor ?: 'Not specified'; ?></div>
                                <div class="small text-secondary"><?php echo $exp->description ?: 'No description'; ?></div>
                            </td>
                            <td><?php echo $exp->category_name; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo formatCurrency($exp->amount); ?></div>
                                <?php if ($exp->tax > 0) echo '<div class="small text-secondary">Tax: ' . formatCurrency($exp->tax) . '</div>'; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $exp->payment_mode; ?></span></td>
                            <td>
                                <?php
                                $statusColor = [
                                    'approved' => 'text-success',
                                    'pending' => 'text-warning',
                                    'rejected' => 'text-danger'
                                ][$exp->status];
                                ?>
                                <i class="fas fa-circle <?php echo $statusColor; ?> me-1 small"></i>
                                <span class="small fw-medium"><?php echo ucfirst($exp->status); ?></span>
                            </td>
                            <td class="pe-4 text-end">
                                <?php if ($exp->attachment): ?>
                                    <a href="assets/uploads/expenses/<?php echo $exp->attachment; ?>" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View Attachment">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $exp->id; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($expenses)) echo '<tr><td colspan="7" class="text-center py-4">No expenses found</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Record New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Expense Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Vendor Name</label>
                            <input type="text" name="vendor" class="form-control" placeholder="e.g. Amazon, Stationery Shop">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax (Optional)</label>
                            <input type="number" step="0.01" name="tax" class="form-control" placeholder="0.00" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="UPI">UPI / Digital Wallet</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt/Bill</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_expense" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" action="" method="POST" style="display:none;">
    <input type="hidden" name="expense_id" id="delete_expense_id">
    <input type="hidden" name="delete_expense" value="1">
</form>

<script>
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this expense?')) {
            document.getElementById('delete_expense_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>