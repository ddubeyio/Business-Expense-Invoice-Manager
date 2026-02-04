<?php
$pageTitle = "System Settings";
require_once 'includes/header.php';
requireLogin();

if (!hasRole('admin')) {
    setMessage("Access denied.", "danger");
    redirect('index.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    try {
        $pdo->beginTransaction();

        $settings_to_save = [
            'company_name' => sanitize($_POST['company_name']),
            'company_email' => sanitize($_POST['company_email']),
            'company_phone' => sanitize($_POST['company_phone']),
            'company_address' => sanitize($_POST['company_address']),
            'currency' => sanitize($_POST['currency']),
            'tax_name' => sanitize($_POST['tax_name']),
            'tax_rate' => sanitize($_POST['tax_rate']),
            'invoice_prefix' => sanitize($_POST['invoice_prefix']),
            'financial_year' => sanitize($_POST['financial_year']),
            'invoice_notes' => sanitize($_POST['invoice_notes']),
            'smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
            'smtp_port' => sanitize($_POST['smtp_port'] ?? ''),
            'smtp_user' => sanitize($_POST['smtp_user'] ?? ''),
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_crypto' => sanitize($_POST['smtp_crypto'] ?? 'tls'),
            'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
            'exchange_api_key' => sanitize($_POST['exchange_api_key'] ?? '')
        ];

        // Logo Upload
        if (!empty($_FILES['company_logo']['name'])) {
            $targetDir = "assets/uploads/company/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = 'logo_' . time() . '_' . basename($_FILES['company_logo']['name']);
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetDir . $fileName)) {
                $settings_to_save['company_logo'] = $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        foreach ($settings_to_save as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }

        $pdo->commit();
        setMessage("Settings updated successfully!");
        redirect('settings.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        setMessage("Error: " . $e->getMessage(), "danger");
    }
}
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="list-group list-group-flush rounded-3" id="settingsTabs">
                <a href="#general" class="list-group-item list-group-item-action active p-3" data-section="general">
                    <i class="fas fa-building me-2"></i> Company Profile
                </a>
                <a href="#financial" class="list-group-item list-group-item-action p-3" data-section="financial">
                    <i class="fas fa-coins me-2"></i> Financial & Taxes
                </a>
                <a href="#appearance" class="list-group-item list-group-item-action p-3" data-section="appearance">
                    <i class="fas fa-palette me-2"></i> Appearance
                </a>
                <a href="#email" class="list-group-item list-group-item-action p-3" data-section="email">
                    <i class="fas fa-envelope-open-text me-2"></i> Email & SMTP
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <form action="" method="POST" enctype="multipart/form-data">
            <!-- Company Profile Section -->
            <div class="settings-section" id="section-general">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="mb-0">Company Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Company Logo</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php $logo = getSetting('company_logo') ?: 'default-logo.png'; ?>
                                        <img src="assets/uploads/company/<?php echo $logo; ?>" class="img-thumbnail" style="height: 100px; max-width: 200px; object-fit: contain;">
                                    </div>
                                    <div>
                                        <input type="file" name="company_logo" class="form-control mb-1">
                                        <small class="text-secondary">Recommended size: 200x100px (PNG/JPG)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo getSetting('company_name'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Official Email</label>
                                <input type="email" name="company_email" class="form-control" value="<?php echo getSetting('company_email'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="company_phone" class="form-control" value="<?php echo getSetting('company_phone'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="company_address" class="form-control" rows="3"><?php echo getSetting('company_address'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial & Taxes Section -->
            <div class="settings-section d-none" id="section-financial">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="mb-0">Financial & Taxes</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Default Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="USD" <?php echo getSetting('currency') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo getSetting('currency') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo getSetting('currency') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                    <option value="INR" <?php echo getSetting('currency') == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tax Name</label>
                                <input type="text" name="tax_name" class="form-control" value="<?php echo getSetting('tax_name'); ?>" placeholder="e.g. GST, VAT">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tax Rate (%)</label>
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?php echo getSetting('tax_rate'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Invoice Prefix</label>
                                <input type="text" name="invoice_prefix" class="form-control" value="<?php echo getSetting('invoice_prefix'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Financial Year</label>
                                <input type="text" name="financial_year" class="form-control" value="<?php echo getSetting('financial_year'); ?>" placeholder="e.g. 2025-2026">
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">ExchangeRate-API Key (for Multi-currency)</label>
                                <input type="text" name="exchange_api_key" class="form-control" value="<?php echo getSetting('exchange_api_key'); ?>" placeholder="Enter API Key from exchangerate-api.com">
                                <small class="text-secondary">Used to automatically fetch daily exchange rates.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance Section -->
            <div class="settings-section d-none" id="section-appearance">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="mb-0">Invoice Appearance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Default Invoice Notes/Terms</label>
                                <textarea name="invoice_notes" class="form-control" rows="5"><?php echo getSetting('invoice_notes'); ?></textarea>
                                <small class="text-secondary">These notes will appear at the bottom of all new invoices.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email & SMTP Section -->
            <div class="settings-section d-none" id="section-email">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-3">
                        <h5 class="mb-0">Email & SMTP Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo getSetting('smtp_host'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">SMTP Port</label>
                                <input type="text" name="smtp_port" class="form-control" value="<?php echo getSetting('smtp_port'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Encryption</label>
                                <select name="smtp_crypto" class="form-select">
                                    <option value="ssl" <?php echo getSetting('smtp_crypto') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo getSetting('smtp_crypto') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="none" <?php echo getSetting('smtp_crypto') == 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SMTP Username</label>
                                <input type="text" name="smtp_user" class="form-control" value="<?php echo getSetting('smtp_user'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">SMTP Password</label>
                                <input type="password" name="smtp_pass" class="form-control" value="<?php echo getSetting('smtp_pass'); ?>">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_enabled" value="1" <?php echo getSetting('email_enabled') ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold">Enable Automated Notifications</label>
                                </div>
                                <small class="text-secondary">If enabled, system will send invoices and payment receipts automatically.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mb-5">
                <button type="submit" name="save_settings" class="btn btn-primary px-5 py-2">
                    <i class="fas fa-save me-2"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const listItems = document.querySelectorAll('#settingsTabs .list-group-item');
        const sections = document.querySelectorAll('.settings-section');

        listItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                // Remove active class from all items
                listItems.forEach(li => li.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');

                // Hide all sections
                sections.forEach(sec => sec.classList.add('d-none'));
                // Show target section
                const targetSection = document.getElementById('section-' + this.dataset.section);
                if (targetSection) {
                    targetSection.classList.remove('d-none');
                }
            });
        });

        // Handle hash in URL if any
        const hash = window.location.hash.replace('#', '');
        if (hash) {
            const targetTab = document.querySelector(`[data-section="${hash}"]`);
            if (targetTab) targetTab.click();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>