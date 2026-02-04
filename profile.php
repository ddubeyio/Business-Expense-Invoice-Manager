<?php
$pageTitle = "My Profile";
require_once 'includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$u = $user->fetch();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);

    // Image Upload
    $image_name = $u->profile_image;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "assets/uploads/profiles/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['profile_image']['name']);
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetDir . $fileName)) {
            $image_name = $fileName;
            $_SESSION['user_image'] = $fileName;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$name, $email, $image_name, $user_id]);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        setMessage("Profile updated successfully!");
        redirect('profile.php');
    } catch (PDOException $e) {
        setMessage("Error: " . $e->getMessage(), "danger");
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (password_verify($current, $u->password)) {
        if ($new === $confirm) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            setMessage("Password changed successfully!");
            redirect('profile.php');
        } else {
            setMessage("Passwords do not match.", "danger");
        }
    } else {
        setMessage("Incorrect current password.", "danger");
    }
}
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mb-3">
                <img src="assets/uploads/profiles/<?php echo $u->profile_image; ?>" class="rounded-circle img-thumbnail" width="150" height="150">
            </div>
            <h4 class="mb-1"><?php echo $u->name; ?></h4>
            <p class="text-secondary mb-3"><?php echo ucfirst($u->role); ?></p>
            <div class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                Active Member
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <h5 class="mb-0">Edit Profile Details</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $u->name; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $u->email; ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Change Profile Picture</label>
                            <input type="file" name="profile_image" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary mt-4">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-3">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-outline-primary mt-4">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>