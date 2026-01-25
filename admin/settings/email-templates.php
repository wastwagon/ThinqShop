<?php
/**
 * Email Templates Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Check if email_templates table exists
try {
    $conn->query("SELECT 1 FROM email_templates LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist - show error message
    $pageTitle = 'Database Migration Required - Admin - ' . APP_NAME;
    ob_start();
    ?>
    <div class="container-fluid">
        <div class="alert alert-warning">
            <h4>Database Tables Not Found</h4>
            <p>The email_templates table has not been created yet. Please run the database migration first.</p>
            <p><a href="<?php echo BASE_URL; ?>/database/migrations/" class="btn btn-primary">Run Database Migration</a></p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../../includes/layouts/admin-layout.php';
    exit;
}

$success = '';
$error = '';

// Handle template save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token.';
    } else {
        $templateId = intval($_POST['template_id'] ?? 0);
        $subject = sanitize($_POST['subject'] ?? '');
        $body = $_POST['body'] ?? ''; // Don't sanitize HTML body
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($templateId <= 0) {
            $error = 'Invalid template ID.';
        } elseif (empty($subject)) {
            $error = 'Email subject is required.';
        } elseif (empty($body)) {
            $error = 'Email body is required.';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE email_templates SET subject = ?, body = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$subject, $body, $isActive, $templateId]);
                $success = 'Template updated successfully';
                
                // Refresh selected template
                $stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
                $stmt->execute([$templateId]);
                $selectedTemplate = $stmt->fetch();
            } catch (Exception $e) {
                $error = 'Failed to update template: ' . $e->getMessage();
            }
        }
    }
}

// Get all templates
try {
    $stmt = $conn->query("SELECT * FROM email_templates ORDER BY template_name ASC");
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Email templates query error: " . $e->getMessage());
    $templates = [];
    $error = 'Error loading templates: ' . $e->getMessage();
}

// Get selected template
$selectedTemplate = null;
if (isset($_GET['id'])) {
    $templateId = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $selectedTemplate = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Email template fetch error: " . $e->getMessage());
        $error = 'Error loading template: ' . $e->getMessage();
    }
}

// Prepare content for layout
ob_start();
?>
<div class="container-fluid">
    <div class="page-title-section mb-4">
        <h1 class="page-title">Email Templates</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Available Templates</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($templates)): ?>
                        <div class="list-group-item">
                            <p class="text-muted mb-2">No email templates found.</p>
                            <a href="<?php echo BASE_URL; ?>/database/migrations/" class="btn btn-sm btn-primary">
                                Insert Default Templates
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <a href="?id=<?php echo $template['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $selectedTemplate && $selectedTemplate['id'] == $template['id'] ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($template['template_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($template['template_key']); ?></small>
                                    </div>
                                    <?php if ($template['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if ($selectedTemplate): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Template: <?php echo htmlspecialchars($selectedTemplate['template_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="template_id" value="<?php echo $selectedTemplate['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Template Key</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($selectedTemplate['template_key']); ?>" disabled>
                                <small class="text-muted">This is the unique identifier for this template</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <p class="text-muted"><?php echo htmlspecialchars($selectedTemplate['description'] ?? 'No description'); ?></p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Available Variables</label>
                                <?php
                                $variables = json_decode($selectedTemplate['variables'] ?? '[]', true);
                                if (!empty($variables)):
                                ?>
                                    <div class="alert alert-info">
                                        <strong>Variables:</strong> 
                                        <code><?php echo implode('</code>, <code>', array_map(function($v) { return '{{' . $v . '}}'; }, $variables)); ?></code>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No variables defined for this template.</p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" 
                                       value="<?php echo htmlspecialchars($selectedTemplate['subject']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Body (HTML) <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control" rows="15" required><?php echo htmlspecialchars($selectedTemplate['body']); ?></textarea>
                                <small class="text-muted">Use HTML to format the email. Variables should be in {{VARIABLE_NAME}} format.</small>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                       <?php echo $selectedTemplate['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>

                            <button type="submit" name="save_template" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Template
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Select a template from the list to edit</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Email Templates - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>
