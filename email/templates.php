<?php
/**
 * Admin Email Templates Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$templateId = $_GET['id'] ?? null;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        if (isset($_POST['save_template'])) {
            $id = intval($_POST['template_id'] ?? 0);
            $templateKey = sanitize($_POST['template_key'] ?? '');
            $templateName = sanitize($_POST['template_name'] ?? '');
            $subject = sanitize($_POST['subject'] ?? '');
            $body = $_POST['body'] ?? '';
            $variables = sanitize($_POST['variables'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($templateKey) || empty($templateName) || empty($subject) || empty($body)) {
                $errors[] = 'All fields are required.';
            } else {
                try {
                    if ($id > 0) {
                        // Update existing template
                        $stmt = $conn->prepare("
                            UPDATE email_templates 
                            SET template_name = ?, subject = ?, body = ?, variables = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$templateName, $subject, $body, $variables, $isActive, $id]);
                    } else {
                        // Create new template
                        $stmt = $conn->prepare("
                            INSERT INTO email_templates (template_key, template_name, subject, body, variables, is_active)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$templateKey, $templateName, $subject, $body, $variables, $isActive]);
                    }
                    $success = 'Template saved successfully.';
                    $action = 'list';
                } catch (Exception $e) {
                    $errors[] = 'Error saving template: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get template for editing
$template = null;
if ($action === 'edit' && $templateId) {
    $stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    if (!$template) {
        $errors[] = 'Template not found.';
        $action = 'list';
    }
}

// Get all templates
$templates = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM email_templates ORDER BY template_name");
    $templates = $stmt->fetchAll();
}

$pageTitle = 'Email Templates - ' . APP_NAME;
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Email Templates</h1>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Template
            </a>
        <?php else: ?>
            <a href="?" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Template Key</th>
                                <th>Template Name</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No templates found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($templates as $tpl): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($tpl['template_key']); ?></code></td>
                                        <td><?php echo htmlspecialchars($tpl['template_name']); ?></td>
                                        <td><?php echo htmlspecialchars($tpl['subject']); ?></td>
                                        <td>
                                            <?php if ($tpl['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $tpl['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="template_id" value="<?php echo $template['id'] ?? 0; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="template_key" class="form-label">Template Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="template_key" name="template_key" 
                                   value="<?php echo htmlspecialchars($template['template_key'] ?? ''); ?>" 
                                   <?php echo ($template) ? 'readonly' : 'required'; ?>>
                            <small class="form-text text-muted">Unique identifier (e.g., welcome_email, order_confirmation)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="template_name" name="template_name" 
                                   value="<?php echo htmlspecialchars($template['template_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?php echo htmlspecialchars($template['subject'] ?? ''); ?>" required>
                        <small class="form-text text-muted">Use {{VARIABLE_NAME}} for dynamic content</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="body" class="form-label">Email Body (HTML) <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="body" name="body" rows="15" required><?php echo htmlspecialchars($template['body'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Use {{VARIABLE_NAME}} for dynamic content. HTML is allowed.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="variables" class="form-label">Available Variables (JSON Array)</label>
                        <textarea class="form-control" id="variables" name="variables" rows="3"><?php echo htmlspecialchars($template['variables'] ?? '[]'); ?></textarea>
                        <small class="form-text text-muted">Example: ["APP_NAME","USER_NAME","ORDER_NUMBER"]</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?php echo (!isset($template) || $template['is_active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="save_template" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Template
                        </button>
                        <a href="?" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>






