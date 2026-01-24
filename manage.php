<?php
/**
 * Admin Users Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle user actions
if (isset($_GET['toggle_status']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_GET['toggle_status']);
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/users/manage.php', 'Invalid security token.', 'danger');
    }
    
    try {
        // Toggle user status
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'toggle_user_status', 'users', $userId);
        }
        redirect('/admin/users/manage.php', 'User status updated successfully.', 'success');
        
    } catch (Exception $e) {
        error_log("Toggle User Status Error: " . $e->getMessage());
        redirect('/admin/users/manage.php', 'Failed to update user status.', 'danger');
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "u.is_active = ?";
    $params[] = $statusFilter === 'active' ? 1 : 0;
}

if ($search) {
    $where[] = "(u.email LIKE ? OR u.phone LIKE ? OR up.first_name LIKE ? OR up.last_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total 
             FROM users u
             LEFT JOIN user_profiles up ON u.id = up.user_id
             $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users
$sql = "SELECT u.*, up.first_name, up.last_name, up.profile_image,
               (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
               (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND payment_status = 'success') as total_spent
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Customer Management</h1>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by email, phone, or name" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="text-muted text-center py-5">No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            if (empty($userName)) {
                                $userName = explode('@', $user['email'])[0];
                            }
                            $profileImage = $user['profile_image'] ?? null;
                            $imagePath = __DIR__ . '/../../assets/images/profiles/' . ($profileImage ?? '');
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($profileImage && file_exists($imagePath) && filesize($imagePath) > 0): ?>
                                        <img src="<?php echo BASE_URL; ?>/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" 
                                             alt="<?php echo htmlspecialchars($userName); ?>" 
                                             class="rounded-circle me-2" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 40px; height: 40px; font-weight: 600;">
                                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($userName); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['phone']): ?>
                                    <i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars($user['phone']); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo $user['order_count']; ?> orders</span>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($user['total_spent']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="btn btn-outline-primary" title="Email">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <form method="POST" action="?toggle_status=<?php echo $user['id']; ?>" 
                                          class="d-inline" onsubmit="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" 
                                                title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Users pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Customer Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';

