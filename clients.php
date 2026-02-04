<?php
$pageTitle = "Client Management";
require_once 'includes/header.php';
requireLogin();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_client'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $tax_id = sanitize($_POST['tax_id']);

        try {
            $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, address, tax_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $address, $tax_id]);
            setMessage("Client added successfully!");
            redirect('clients.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }

    if (isset($_POST['update_client'])) {
        $id = $_POST['client_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $tax_id = sanitize($_POST['tax_id']);

        try {
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, address = ?, tax_id = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $tax_id, $id]);
            setMessage("Client updated successfully!");
            redirect('clients.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }
}

// Fetch Clients
$clients = $pdo->query("SELECT c.*, (SELECT SUM(total) FROM invoices WHERE client_id = c.id) as total_billed, (SELECT SUM(p.amount) FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.client_id = c.id) as total_paid FROM clients c ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Clients</h3>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="fas fa-plus me-2"></i> Add Client
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Client Name</th>
                        <th>Contact info</th>
                        <th>Tax ID / VAT</th>
                        <th>Total Billed</th>
                        <th>Outstanding</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $cl):
                        $billed = $cl->total_billed ?: 0;
                        $paid = $cl->total_paid ?: 0;
                        $outstanding = $billed - $paid;
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo $cl->name; ?></div>
                                <div class="text-secondary small">ID: #<?php echo str_pad($cl->id, 5, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope me-1 small text-secondary"></i> <?php echo $cl->email; ?></div>
                                <div><i class="fas fa-phone me-1 small text-secondary"></i> <?php echo $cl->phone; ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $cl->tax_id ?: '-'; ?></span></td>
                            <td class="fw-medium"><?php echo formatCurrency($billed); ?></td>
                            <td class="text-danger fw-bold"><?php echo formatCurrency($outstanding); ?></td>
                            <td class="pe-4 text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editClient(<?php echo json_encode($cl); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="client-details.php?id=<?php echo $cl->id; ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-history"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($clients)) echo '<tr><td colspan="6" class="text-center py-4">No clients found</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="" method="POST" id="clientForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="client_id" id="client_id">
                    <div class="mb-3">
                        <label class="form-label">Client / Business Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax ID (GST/VAT)</label>
                        <input type="text" name="tax_id" id="tax_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_client" id="saveBtn" class="btn btn-primary">Save Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editClient(client) {
        document.getElementById('modalTitle').innerText = 'Edit Client';
        document.getElementById('saveBtn').name = 'update_client';
        document.getElementById('client_id').value = client.id;
        document.getElementById('name').value = client.name;
        document.getElementById('email').value = client.email;
        document.getElementById('phone').value = client.phone;
        document.getElementById('tax_id').value = client.tax_id;
        document.getElementById('address').value = client.address;

        var modal = new bootstrap.Modal(document.getElementById('addClientModal'));
        modal.show();
    }

    // Reset modal on close
    $('#addClientModal').on('hidden.bs.modal', function() {
        document.getElementById('modalTitle').innerText = 'Add New Client';
        document.getElementById('saveBtn').name = 'add_client';
        document.getElementById('clientForm').reset();
        document.getElementById('client_id').value = '';
    });
</script>

<?php require_once 'includes/footer.php'; ?>