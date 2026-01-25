<?php
/**
 * Admin Dashboard - Modern Premium Design
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/admin-auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get comprehensive statistics
$stats = [];

// Total users
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$stats['total_users'] = $stmt->fetch()['count'];

// Total revenue (all time)
$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'success'
");
$stats['total_revenue'] = floatval($stmt->fetch()['total']);

// Today's revenue
$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'success' 
    AND DATE(created_at) = CURDATE()
");
$stats['today_revenue'] = floatval($stmt->fetch()['total']);

// Total orders
$stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $stmt->fetch()['count'];

// Products sold (sum of all order items)
$stmt = $conn->query("SELECT COALESCE(SUM(quantity), 0) as count FROM order_items");
$stats['products_sold'] = $stmt->fetch()['count'];

// Visitors (using orders as proxy - can be enhanced with analytics)
$stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM orders");
$stats['visitors'] = $stmt->fetch()['count'];

// Pending orders
$stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'processing')");
$stats['pending_orders'] = $stmt->fetch()['count'];

// Pending transfers
$stmt = $conn->query("SELECT COUNT(*) as count FROM money_transfers WHERE status IN ('payment_received', 'processing')");
$stats['pending_transfers'] = $stmt->fetch()['count'];

// Pending shipments
$stmt = $conn->query("SELECT COUNT(*) as count FROM shipments WHERE status IN ('booked', 'pickup_scheduled', 'picked_up', 'in_transit')");
$stats['pending_shipments'] = $stmt->fetch()['count'];

// Pending procurement requests
$stmt = $conn->query("SELECT COUNT(*) as count FROM procurement_requests WHERE status IN ('submitted', 'quote_provided')");
$stats['pending_procurements'] = $stmt->fetch()['count'];

// Money Transfer stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM money_transfers");
$stats['total_transfers'] = $stmt->fetch()['count'];
$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM money_transfers WHERE status = 'completed'");
$stats['transfers_revenue'] = floatval($stmt->fetch()['total']);

// Logistics stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM shipments");
$stats['total_shipments'] = $stmt->fetch()['count'];
$stmt = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM shipments WHERE status = 'delivered'");
$stats['shipments_revenue'] = floatval($stmt->fetch()['total']);

// Procurement stats
$stmt = $conn->query("SELECT COUNT(*) as count FROM procurement_requests");
$stats['total_procurements'] = $stmt->fetch()['count'];
$stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM procurement_orders WHERE status = 'delivered'");
$stats['procurements_revenue'] = floatval($stmt->fetch()['total']);

// Low stock products
$stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= low_stock_threshold AND is_active = 1");
$stats['low_stock'] = $stmt->fetch()['count'];

// Monthly sales data for chart (last 12 months)
$stmt = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(amount), 0) as total
    FROM payments 
    WHERE status = 'success' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlySales = $stmt->fetchAll();

// Sales by service type
$stmt = $conn->query("
    SELECT 
        service_type,
        COALESCE(SUM(amount), 0) as total
    FROM payments 
    WHERE status = 'success'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY service_type
");
$salesByService = $stmt->fetchAll();

// Recent customers
$stmt = $conn->query("
    SELECT u.*, up.first_name, up.last_name
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.is_active = 1
    ORDER BY u.created_at DESC
    LIMIT 5
");
$recentCustomers = $stmt->fetchAll();

// Top selling products
$stmt = $conn->query("
    SELECT 
        p.name,
        p.sku,
        COALESCE(SUM(oi.quantity), 0) as sales,
        COALESCE(SUM(oi.total), 0) as amount,
        p.stock_quantity
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled' OR o.status IS NULL
    GROUP BY p.id, p.name, p.sku, p.stock_quantity
    ORDER BY sales DESC
    LIMIT 5
");
$topProducts = $stmt->fetchAll();

// Recent orders
$stmt = $conn->query("
    SELECT o.*, u.email, u.phone 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recent_orders = $stmt->fetchAll();

// Calculate growth percentages (simple - compare to previous period)
$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'success' 
    AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
");
$yesterdayRevenue = floatval($stmt->fetch()['total']);
$revenueGrowth = $yesterdayRevenue > 0 ? round((($stats['today_revenue'] - $yesterdayRevenue) / $yesterdayRevenue) * 100) : 0;

$stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdayOrders = $stmt->fetch()['count'];
$ordersGrowth = $yesterdayOrders > 0 ? round((($stats['pending_orders'] - $yesterdayOrders) / $yesterdayOrders) * 100) : 0;

// Prepare chart data
$ecommerceData = [];
$transferData = [];
foreach ($monthlySales as $month) {
    // Parse month to get year and month
    $monthParts = explode('-', $month['month']);
    $year = intval($monthParts[0]);
    $monthNum = intval($monthParts[1]);
    
    // Use YEAR() and MONTH() to avoid collation issues with DATE_FORMAT
    // Cast columns to utf8mb4_general_ci to match string literals
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE CAST(service_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci = 'ecommerce'
        AND CAST(status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci = 'success'
        AND YEAR(created_at) = ? 
        AND MONTH(created_at) = ?
    ");
    $stmt->execute([$year, $monthNum]);
    $ecommerceData[] = floatval($stmt->fetch()['total']);
    
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE CAST(service_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci = 'money_transfer'
        AND CAST(status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci = 'success'
        AND YEAR(created_at) = ? 
        AND MONTH(created_at) = ?
    ");
    $stmt->execute([$year, $monthNum]);
    $transferData[] = floatval($stmt->fetch()['total']);
}

$pageTitle = 'Admin Dashboard - ' . APP_NAME;

// Include Chart.js
$includeCharts = true;

// Dashboard-specific styles
$additionalCSS = [
    BASE_URL . '/assets/css/pages/admin-dashboard.css'
];

// Start output buffering for dashboard content
ob_start();
?>
<div class="container-fluid">
        <div class="page-title-section">
            <h1 class="page-title">Overview</h1>
            <a href="#" class="download-report-btn" onclick="downloadReport()">
                <i class="fas fa-download"></i>
                <span>Download Report</span>
            </a>
        </div>

        <!-- Metrics Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="metric-title">Revenue</div>
                <div class="metric-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                <div class="metric-growth">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo abs($revenueGrowth); ?>%</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon orders">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <div class="metric-title">Total orders</div>
                <div class="metric-value"><?php echo number_format($stats['total_orders']); ?></div>
                <div class="metric-growth">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo abs($ordersGrowth); ?>%</span>
        </div>
    </div>

            <div class="metric-card">
                <div class="metric-icon products">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="metric-title">Product Sold</div>
                <div class="metric-value"><?php echo number_format($stats['products_sold']); ?></div>
                <div class="metric-growth">
                    <i class="fas fa-arrow-up"></i>
                    <span>+3%</span>
        </div>
    </div>
    
            <div class="metric-card">
                <div class="metric-icon visitors">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-title">Visitors</div>
                <div class="metric-value"><?php echo number_format($stats['visitors']); ?></div>
                <div class="metric-growth">
                    <i class="fas fa-arrow-up"></i>
                    <span>+3%</span>
                </div>
            </div>
        </div>
        
        <!-- Additional Service Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon transfers">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="metric-title">Money Transfers</div>
                <div class="metric-value"><?php echo number_format($stats['total_transfers']); ?></div>
                <div class="metric-growth">
                    <small class="text-muted">Revenue: <?php echo formatCurrency($stats['transfers_revenue']); ?></small>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon logistics">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="metric-title">Logistics</div>
                <div class="metric-value"><?php echo number_format($stats['total_shipments']); ?></div>
                <div class="metric-growth">
                    <small class="text-muted">Revenue: <?php echo formatCurrency($stats['shipments_revenue']); ?></small>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon procurement">
                    <i class="fas fa-box"></i>
                </div>
                <div class="metric-title">Procurement</div>
                <div class="metric-value"><?php echo number_format($stats['total_procurements']); ?></div>
                <div class="metric-growth">
                    <small class="text-muted">Revenue: <?php echo formatCurrency($stats['procurements_revenue']); ?></small>
            </div>
        </div>
        
            <div class="metric-card">
                <div class="metric-icon revenue">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="metric-title">Pending Actions</div>
                <div class="metric-value">
                    <?php echo number_format($stats['pending_orders'] + $stats['pending_transfers'] + $stats['pending_shipments'] + $stats['pending_procurements']); ?>
                </div>
                <div class="metric-growth">
                    <small class="text-muted">Requires attention</small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Sales Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-section">
                    <div class="chart-header">
                        <h3 class="chart-title">Sales Figures</h3>
                        <div class="chart-legend">
                            <?php foreach ($salesByService as $service): ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background: <?php 
                                        echo $service['service_type'] === 'ecommerce' ? '#dc3545' : '#fd7e14'; 
                                    ?>;"></span>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $service['service_type'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <canvas id="salesChart" height="80"></canvas>
                </div>
            </div>

            <!-- Customer List -->
            <div class="col-lg-4 mb-4">
                <div class="data-section">
                    <h3 class="data-section-title">Customer</h3>
                    <div class="customer-list">
                        <?php foreach ($recentCustomers as $customer): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="customer-avatar">
                                    <?php 
                                    $name = ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '');
                                    $initials = !empty(trim($name)) ? strtoupper(substr(trim($name), 0, 1)) : strtoupper(substr($customer['email'] ?? 'U', 0, 1));
                                    echo $initials;
                                    ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?php echo htmlspecialchars(trim($name) ?: $customer['email'] ?? 'User'); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($customer['email'] ?? ''); ?></div>
        </div>
    </div>
                        <?php endforeach; ?>
    </div>
                </div>
            </div>
        </div>

        <!-- Product Sales Table -->
        <div class="data-section">
            <h3 class="data-section-title">Product Sale</h3>
                        <div class="table-responsive">
                <table class="data-table">
                                <thead>
                                    <tr>
                            <th>Product Name</th>
                            <th>Sales</th>
                                        <th>Amount</th>
                            <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No product sales data yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo number_format($product['sales']); ?></td>
                                    <td><?php echo formatCurrency($product['amount']); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                        <?php endif; ?>
                                </tbody>
                            </table>
            </div>
        </div>
    </div>

<?php
// Dashboard-specific JavaScript
$inlineJS = '
    // Sales Chart
    const salesCtx = document.getElementById("salesChart");
    const monthlyData = ' . json_encode(array_column($monthlySales, 'total')) . ';
    const monthlyLabels = ' . json_encode(array_map(function($m) { 
        return date('M', strtotime($m . '-01')); 
    }, array_column($monthlySales, 'month'))) . ';

    new Chart(salesCtx, {
        type: "line",
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: "E-commerce",
                    data: ' . json_encode($ecommerceData) . ',
                    borderColor: "#dc3545",
                    backgroundColor: "rgba(220, 53, 69, 0.1)",
                    tension: 0.4,
                    fill: true
                },
                {
                    label: "Money Transfer",
                    data: ' . json_encode($transferData) . ',
                    borderColor: "#fd7e14",
                    backgroundColor: "rgba(253, 126, 20, 0.1)",
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: "index",
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return "₵" + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                mode: "nearest",
                axis: "x",
                intersect: false
            }
        }
    });

    function downloadReport() {
        alert("Report download feature will be implemented soon!");
    }
';

// Get dashboard content
$content = ob_get_clean();

// Include admin layout
include __DIR__ . '/../includes/layouts/admin-layout.php';
?>