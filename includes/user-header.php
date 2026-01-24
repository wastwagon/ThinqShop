<?php
/**
 * User Dashboard Header Component
 * Modern Premium Design
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    if (!defined('SESSION_NAME')) {
        require_once __DIR__ . '/../config/constants.php';
    }
    session_name(SESSION_NAME ?? 'thinqshop_session');
    session_start();
}

// Load required files
if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/functions.php';
}
if (!class_exists('Database')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/constants.php';
}

$currentDate = date('l, d F Y');
$userId = $_SESSION['user_id'] ?? null;

// Get user safely
try {
    $user = getCurrentUser();
} catch (Exception $e) {
    error_log("Error in user-header getCurrentUser: " . $e->getMessage());
    $user = ['email' => 'User'];
}

// Get user profile
$profile = null;
if ($userId) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in user-header profile fetch: " . $e->getMessage());
        $profile = null;
    }
}

$userName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
if (empty($userName)) {
    $userName = $user['email'] ?? 'User';
}
$userInitials = strtoupper(substr($userName, 0, 1));
?>

<?php
// Notification styles moved to user-dashboard.css
?>

<script>
// Notification functionality for user
let userNotificationDropdownOpen = false;
let userMarkAllInFlight = false;

function toggleNotificationDropdown(type) {
    if (type === 'user') {
        const dropdown = document.getElementById('userNotificationDropdown');
        userNotificationDropdownOpen = !userNotificationDropdownOpen;
        
        if (userNotificationDropdownOpen) {
            dropdown.classList.add('active');
            loadUserNotifications();
        } else {
            dropdown.classList.remove('active');
        }
    }
}

function closeNotificationDropdown(type) {
    if (type === 'user') {
        const dropdown = document.getElementById('userNotificationDropdown');
        dropdown.classList.remove('active');
        userNotificationDropdownOpen = false;
    }
}

function loadUserNotifications() {
    const list = document.getElementById('userNotificationList');
    if (!list) {
        console.warn('Notification list element not found');
        return;
    }
    
    list.innerHTML = '<div class="notification-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch('/api/ajax/get-notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateUserNotificationBadge(data.unread_count);
                displayUserNotifications(data.notifications);
            } else {
                if (list) {
                    list.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            if (list) {
                list.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
            }
        });
}

function markAllUserNotifications(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (userMarkAllInFlight) {
        return;
    }
    
    const button = event ? event.currentTarget : null;
    const originalLabel = button ? button.innerHTML : '';
    
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Working...';
    }
    
    userMarkAllInFlight = true;
    
    const formData = new FormData();
    formData.append('is_admin', 'false');
    
    fetch('/api/ajax/mark-all-notifications-read.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadUserNotifications();
                updateUserNotificationBadge(0);
            } else if (data.message) {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error marking all notifications:', error);
            alert('Unable to mark notifications right now. Please try again.');
        })
        .finally(() => {
            userMarkAllInFlight = false;
            if (button) {
                button.disabled = false;
                button.innerHTML = originalLabel || '<i class="fas fa-check-double"></i> Mark all';
            }
        });
}

function updateUserNotificationBadge(count) {
    const badge = document.getElementById('userNotificationBadge');
    if (!badge) {
        return;
    }
    badge.textContent = count;
    if (count > 0) {
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function displayUserNotifications(notifications) {
    const list = document.getElementById('userNotificationList');
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="notification-empty">No notifications</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const iconClass = getNotificationIconClass(notif.type);
        const unreadClass = !notif.is_read ? 'unread' : '';
        const link = notif.link || '#';
        
        return `
            <div class="notification-item ${unreadClass}" onclick="markNotificationRead(${notif.id}, false, '${link}')">
                <div class="notification-icon ${iconClass}">
                    <i class="fas ${getNotificationIcon(notif.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notif.title)}</div>
                    <div class="notification-message">${escapeHtml(notif.message)}</div>
                    <div class="notification-time">${notif.time_ago}</div>
                </div>
            </div>
        `;
    }).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'order': 'fa-shopping-bag',
        'transfer': 'fa-exchange-alt',
        'shipment': 'fa-truck',
        'ticket': 'fa-ticket-alt',
        'payment': 'fa-credit-card',
        'default': 'fa-bell'
    };
    return icons[type] || icons.default;
}

function getNotificationIconClass(type) {
    const classes = {
        'order': 'order',
        'transfer': 'transfer',
        'shipment': 'shipment',
        'ticket': 'ticket',
        'default': 'order'
    };
    return classes[type] || classes.default;
}

function markNotificationRead(notificationId, isAdmin, link) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    formData.append('is_admin', isAdmin ? 'true' : 'false');
    
    fetch('/api/ajax/mark-notification-read.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUserNotifications();
            if (link && link !== '#') {
                window.location.href = link;
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        if (link && link !== '#') {
            window.location.href = link;
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userNotificationDropdown');
    const button = document.getElementById('userNotificationBtn');
    
    if (userNotificationDropdownOpen && 
        !dropdown.contains(event.target) && 
        !button.contains(event.target)) {
        closeNotificationDropdown('user');
    }
});

// Load notification count on page load (non-blocking)
document.addEventListener('DOMContentLoaded', function() {
    // Use setTimeout to ensure page is fully loaded before making API call
    setTimeout(function() {
        const notificationBtn = document.getElementById('userNotificationBtn');
        const notificationList = document.getElementById('userNotificationList');
        
        if (notificationBtn && notificationList) {
            // Load notification count only, not full list (to avoid blocking)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
            
            fetch('/api/ajax/get-notifications.php', {
                signal: controller.signal
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateUserNotificationBadge(data.unread_count);
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    if (error.name !== 'AbortError') {
                        console.error('Error loading notification count:', error);
                    }
                });
            
            // Refresh notification count every 30 seconds (not full list)
            setInterval(function() {
                const refreshController = new AbortController();
                const refreshTimeout = setTimeout(() => refreshController.abort(), 5000);
                
                fetch('/api/ajax/get-notifications.php', {
                    signal: refreshController.signal
                })
                    .then(response => {
                        clearTimeout(refreshTimeout);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            updateUserNotificationBadge(data.unread_count);
                        }
                    })
                    .catch(error => {
                        clearTimeout(refreshTimeout);
                        if (error.name !== 'AbortError') {
                            console.error('Error refreshing notification count:', error);
                        }
                    });
            }, 30000);
        }
    }, 100); // Small delay to ensure page is interactive
});
</script>

<div class="user-header">
    <button class="sidebar-toggle" onclick="toggleUserSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="header-left">
        <?php
        $pageName = 'Dashboard';
        $subPage = 'Overview';
        
        $scriptName = basename($_SERVER['PHP_SELF']);
        $requestUri = $_SERVER['REQUEST_URI'];
        
        if (strpos($requestUri, '/orders') !== false) {
            $pageName = 'My Orders';
            $subPage = (isset($_GET['id'])) ? 'Order Details' : 'History';
        } elseif (strpos($requestUri, '/shop') !== false) {
            $pageName = 'Marketplace';
            $subPage = 'Browse';
        } elseif (strpos($requestUri, '/wishlist') !== false) {
            $pageName = 'My Wishlist';
            $subPage = 'Saved Items';
        } elseif (strpos($requestUri, '/transfers') !== false) {
            $pageName = 'Money Transfers';
            $subPage = 'History';
        } elseif (strpos($requestUri, '/shipments') !== false) {
            $pageName = 'Logistics';
            $subPage = 'Shipments';
        } elseif (strpos($requestUri, '/wallet') !== false) {
            $pageName = 'My Wallet';
            $subPage = 'Balance';
        } elseif (strpos($requestUri, '/profile') !== false) {
            $pageName = 'Account';
            $subPage = 'Profile';
        } elseif (strpos($requestUri, '/tickets') !== false) {
            $pageName = 'Support';
            $subPage = 'Tickets';
        } elseif (strpos($requestUri, '/procurement') !== false) {
            $pageName = 'Procurement';
            $subPage = 'Requests';
        }
        ?>
        <h2 class="welcome-text fw-700 text-dark mb-0"><?php echo $pageName; ?> <span class="text-primary opacity-50 ms-2">/</span> <span class="text-muted small ms-2"><?php echo $subPage; ?></span></h2>
        <p class="date-text text-uppercase letter-spacing-1 fw-700 x-small mt-1"><?php echo $currentDate; ?></p>
    </div>
    
    <div class="header-right">
        <div class="search-bar d-none d-lg-block">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search orders, transfers...">
        </div>
        
        <div class="header-actions">
            <div class="notification-wrapper">
                <button class="notification-btn" id="userNotificationBtn" title="Notifications" onclick="toggleNotificationDropdown('user')">
                <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="userNotificationBadge">0</span>
                </button>
                <div class="notification-dropdown" id="userNotificationDropdown">
                    <div class="notification-header">
                        <h6>Notifications</h6>
                        <div class="notification-header-actions">
                            <button class="btn-mark-all" onclick="markAllUserNotifications(event)">
                                <i class="fas fa-check-double"></i> Mark all
                            </button>
                            <button class="btn-close-notifications" onclick="closeNotificationDropdown('user')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="notification-list" id="userNotificationList">
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="/user/notifications.php" class="view-all-link">View All Notifications</a>
                    </div>
                </div>
            </div>
            
            <div class="user-avatar" title="<?php echo htmlspecialchars($userName); ?>">
                <?php 
                $profileImage = $profile['profile_image'] ?? null;
                $imagePath = __DIR__ . '/../assets/images/profiles/' . ($profileImage ?? '');
                if ($profileImage && file_exists($imagePath) && filesize($imagePath) > 0): 
                ?>
                    <img src="/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" 
                         alt="<?php echo htmlspecialchars($userName); ?>" 
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo $userInitials; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Menu (shown only on mobile) -->
<?php 
if (!isset($mobileMenuIncluded)) {
    include __DIR__ . '/mobile-menu.php'; 
}
?>
