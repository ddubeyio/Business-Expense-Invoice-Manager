<?php
$pageTitle = "User Management";
require_once 'includes/header.php';
requireLogin();

if (!hasRole('admin')) {
    setMessage("Access denied.", "danger");
    redirect('index.php');
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $role, $password]);
            setMessage("User created successfully!");
            redirect('users.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }

    if (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status'];

        try {
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $params = [$name, $email, $role, $status, $id];

            if (!empty($_POST['password'])) {
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?";
                $params = [$name, $email, $role, $status, password_hash($_POST['password'], PASSWORD_DEFAULT), $id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            setMessage("User updated successfully!");
            redirect('users.php');
        } catch (PDOException $e) {
            setMessage("Error: " . $e->getMessage(), "danger");
        }
    }
}

// Fetch Users
$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Users</h3>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-2"></i> Add New User
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="assets/uploads/profiles/<?php echo $u->profile_image ?: 'default.png'; ?>" class="rounded-circle me-3" width="40" height="40">
                                    <div>
                                        <div class="fw-bold"><?php echo $u->name; ?></div>
                                        <div class="text-secondary small"><?php echo $u->email; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $u->role == 'admin' ? 'bg-indigo-light text-indigo' : 'bg-light text-dark border'; ?> px-3 py-2">
                                    <?php echo ucfirst($u->role); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u->status): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small"><?php echo date('M d, Y', strtotime($u->created_at)); ?></td>
                            <td class="pe-4 text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="" method="POST" id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="role" class="form-select">
                                <option value="staff">Staff / Accountant</option>
                                <option value="admin">Admin</option>
                                <option value="client">Client (View Only)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="passLabel">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <small class="text-secondary d-none" id="passHelp">Leave blank to keep current password.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" id="saveBtn" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editUser(user) {
        document.getElementById('modalTitle').innerText = 'Edit User';
        document.getElementById('saveBtn').name = 'update_user';
        document.getElementById('user_id').value = user.id;
        document.getElementById('name').value = user.name;
        document.getElementById('email').value = user.email;
        document.getElementById('role').value = user.role;
        document.getElementById('status').value = user.status;

        document.getElementById('password').required = false;
        document.getElementById('passHelp').classList.remove('d-none');
        document.getElementById('passLabel').innerText = 'Change Password (Optional)';

        var modal = new bootstrap.Modal(document.getElementById('addUserModal'));
        modal.show();
    }

    $('#addUserModal').on('hidden.bs.modal', function() {
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('saveBtn').name = 'add_user';
        document.getElementById('userForm').reset();
        document.getElementById('password').required = true;
        document.getElementById('passHelp').classList.add('d-none');
        document.getElementById('passLabel').innerText = 'Password';
    });
</script>

<?php require_once 'includes/footer.php'; ?>