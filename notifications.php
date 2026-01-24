<?php
/**
 * Admin Notifications Center
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/admin-auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$adminId = $_SESSION['admin_id'];

$errors = [];
$messages = [];

// Handle "mark all as read"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $messages[] = 'All notifications have been marked as read.';
        } catch (Exception $e) {
            error_log("Admin notifications mark-all error: " . $e->getMessage());
            $errors[] = 'Failed to mark all notifications as read. Please try again.';
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count totals
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM admin_notifications WHERE admin_id = ?");
$stmt->execute([$adminId]);
$totalNotifications = (int)$stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM admin_notifications WHERE admin_id = ? AND is_read = 0");
$stmt->execute([$adminId]);
$unreadNotifications = (int)$stmt->fetch()['unread'];

$totalPages = max(1, (int)ceil($totalNotifications / $perPage));
if ($page > $totalPages && $totalNotifications > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$stmt = $conn->prepare("
    SELECT id, type, title, message, link, is_read, created_at
    FROM admin_notifications
    WHERE admin_id = :admin_id
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute(['admin_id' => $adminId]);
$notifications = $stmt->fetchAll();

function getNotificationIcon(string $type): string {
    $icons = [
        'order' => 'fa-shopping-bag',
        'transfer' => 'fa-exchange-alt',
        'shipment' => 'fa-truck',
        'ticket' => 'fa-life-ring',
        'payment' => 'fa-credit-card'
    ];
    return $icons[$type] ?? 'fa-bell';
}

function getNotificationBadge(string $type): string {
    $badges = [
        'order' => 'bg-primary',
        'transfer' => 'bg-success',
        'shipment' => 'bg-info',
        'ticket' => 'bg-warning text-dark',
        'payment' => 'bg-purple'
    ];
    return $badges[$type] ?? 'bg-secondary';
}

$pageTitle = 'Notifications - Admin - ' . APP_NAME;

ob_start();
?>

<?php
$additionalCSS = [
    BASE_URL . '/assets/css/pages/admin-notifications.css'
];
?>

<div class="container-fluid notifications-page">
    <div class="page-title-section mb-4">
        <h1 class="page-title mb-2">Notifications Center</h1>
        <p class="text-muted mb-0">
            Stay on top of every order, transfer, shipment, ticket, and payment event.
        </p>
    </div>
    
    <?php if (!empty($messages)): ?>
        <div class="alert alert-success">
            <?php echo implode('<br>', array_map('htmlspecialchars', $messages)); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1">Notifications</h5>
                <small class="text-muted">
                    <?php echo number_format($totalNotifications); ?> total • 
                    <?php echo number_format($unreadNotifications); ?> unread
                </small>
            </div>
            <form method="POST" class="d-flex align-items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit" name="mark_all" class="btn btn-outline-primary"
                        <?php echo $unreadNotifications === 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-double me-1"></i> Mark all as read
                </button>
            </form>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <img src="<?php echo BASE_URL; ?>/assets/images/illustrations/empty-state.svg" alt="" class="mb-4" style="max-width: 220px;">
                    <h5 class="mb-1">No notifications yet</h5>
                    <p class="text-muted mb-0">You’re all caught up! We’ll let you know when there’s something new.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $iconClass = getNotificationIcon($notification['type']);
                        $badgeClass = getNotificationBadge($notification['type']);
                        ?>
                        <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                            <div class="notification-icon <?php echo $badgeClass; ?>">
                                <i class="fas <?php echo $iconClass; ?>"></i>
                            </div>
                            <div class="notification-content flex-grow-1">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </h5>
                                <p class="mb-0">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <div class="notification-time">
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </div>
                            </div>
                            <?php if (!empty($notification['link'])): ?>
                                <div class="notification-actions d-flex align-items-center">
                                    <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-outline-secondary btn-sm">
                                        View details
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Notifications pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <li class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layouts/admin-layout.php';

