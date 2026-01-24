<?php
/**
 * User Notifications Center
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

$errors = [];
$messages = [];

// Handle "mark all as read"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            $messages[] = 'All notifications marked as read.';
        } catch (Exception $e) {
            error_log("User notifications mark-all error: " . $e->getMessage());
            $errors[] = 'Failed to mark notifications as read. Please try again.';
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$totalNotifications = (int)$stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadNotifications = (int)$stmt->fetch()['unread'];

$totalPages = max(1, (int)ceil($totalNotifications / $perPage));
if ($page > $totalPages && $totalNotifications > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$stmt = $conn->prepare("
    SELECT id, type, title, message, link, is_read, created_at
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute(['user_id' => $userId]);
$notifications = $stmt->fetchAll();

function getUserNotificationIcon(string $type): string {
    $icons = [
        'order' => 'fa-shopping-bag',
        'transfer' => 'fa-money-bill-transfer',
        'shipment' => 'fa-truck',
        'ticket' => 'fa-life-ring',
        'wallet' => 'fa-wallet',
        'default' => 'fa-bell'
    ];
    return $icons[$type] ?? $icons['default'];
}

function getUserNotificationBadge(string $type): string {
    $badges = [
        'order' => 'bg-blue-notif',
        'transfer' => 'bg-green-notif',
        'shipment' => 'bg-indigo-notif',
        'ticket' => 'bg-amber-notif',
        'wallet' => 'bg-purple-notif'
    ];
    return $badges[$type] ?? 'bg-gray-notif';
}

$pageTitle = 'Journal - ' . APP_NAME;

$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-notifications.css'
];

ob_start();
?>

<div class="page-title-section d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Notification Center</h1>
        <p class="text-muted small mb-0">Stay updated on your orders, transfers, and account activity.</p>
    </div>
</div>

<?php if (!empty($messages)): ?>
    <div class="alert alert-success border-0 rounded-4 px-4 py-3 mb-4 shadow-sm small fw-medium">
        <?php foreach ($messages as $msg) echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="card border-1 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="notifications-header-premium p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h6 class="fw-800 mb-1 text-dark text-uppercase letter-spacing-1">Journal Registry</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-soft text-primary rounded-pill px-3 py-1 fw-800 x-small">
                    <?php echo $unreadNotifications; ?> UNSEEN UPDATES
                </span>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <button type="submit" name="mark_all" class="btn btn-outline-primary rounded-pill px-4 fw-800 shadow-none x-small"
                    <?php echo $unreadNotifications === 0 ? 'disabled' : ''; ?>>
                <i class="fas fa-check-double me-2"></i> CLEAR REGISTRY
            </button>
        </form>
    </div>
    
    <div class="card-body p-0 bg-white">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-bell-slash text-muted opacity-30 fa-2x"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">REGISTRY CLEAR</h5>
                <p class="text-muted small mb-0">No operational updates found in the journal.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): 
                    $badgeClass = getUserNotificationBadge($notification['type']);
                    $iconClass = getUserNotificationIcon($notification['type']);
                ?>
                    <div class="notif-card-premium <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                         onclick="<?php echo !empty($notification['link']) ? "window.location.href='".htmlspecialchars($notification['link'])."'" : ""; ?>">
                        <div class="notif-icon-wrapper <?php echo $badgeClass; ?> shadow-sm">
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notif-content-premium flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                            <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <div class="notif-meta-premium">
                                <span><i class="far fa-clock me-1"></i> <?php echo timeAgo($notification['created_at']); ?></span>
                                <?php if (!empty($notification['link'])): ?>
                                    <span class="text-primary"><i class="fas fa-external-link-alt me-1"></i> VIEW ACTION</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white p-4 border-top-0">
        <nav aria-label="Notifications pagination">
            <ul class="pagination pagination-premium justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill" href="?page=<?php echo max(1, $page - 1); ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 1): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link rounded-circle mx-1" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php elseif ($i == $page - 2 || $i == $page + 2): ?>
                    <li class="page-item disabled px-2"><span>...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill" href="?page=<?php echo min($totalPages, $page + 1); ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>



<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layouts/user-layout.php';
?>
