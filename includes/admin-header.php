<?php
/**
 * Admin Dashboard Header Component
 * Modern Premium Design
 */

$currentDate = date('l, d F Y');
$adminName = $_SESSION['admin_username'] ?? 'Admin';
?>


<!-- Admin Header Styles now in admin-dashboard.css -->

<script>
// Notification functionality for admin
let adminNotificationDropdownOpen = false;
let adminNotificationInterval = null;
let adminNotificationsLoaded = false;

function toggleNotificationDropdown(type) {
    if (type === 'admin') {
        const dropdown = document.getElementById('adminNotificationDropdown');
        adminNotificationDropdownOpen = !adminNotificationDropdownOpen;
        
        if (adminNotificationDropdownOpen) {
            dropdown.classList.add('active');
            loadAdminNotifications({ silent: false, updateList: true });
            
            // While dropdown is open, refresh list periodically
            if (adminNotificationInterval) {
                clearInterval(adminNotificationInterval);
            }
            adminNotificationInterval = setInterval(() => {
                loadAdminNotifications({ silent: true, updateList: true });
            }, 60000);
        } else {
            dropdown.classList.remove('active');
            if (adminNotificationInterval) {
                clearInterval(adminNotificationInterval);
                adminNotificationInterval = null;
            }
        }
    }
}

function closeNotificationDropdown(type) {
    if (type === 'admin') {
        const dropdown = document.getElementById('adminNotificationDropdown');
        dropdown.classList.remove('active');
        adminNotificationDropdownOpen = false;
        if (adminNotificationInterval) {
            clearInterval(adminNotificationInterval);
            adminNotificationInterval = null;
        }
    }
}

function loadAdminNotifications(options = {}) {
    const { silent = false, updateList = false } = options;
    const list = document.getElementById('adminNotificationList');
    
    if (!silent && updateList && list) {
        list.innerHTML = '<div class="notification-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
    
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    let timeoutId = null;
    
    if (controller) {
        timeoutId = setTimeout(() => controller.abort(), 8000);
    }
    
    fetch('<?php echo BASE_URL; ?>/api/ajax/get-admin-notifications.php', {
        credentials: 'same-origin',
        signal: controller ? controller.signal : undefined
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAdminNotificationBadge(data.unread_count);
                adminNotificationsLoaded = true;
                if (updateList && list) {
                    displayAdminNotifications(data.notifications);
                }
            } else if (!silent && list && updateList) {
                list.innerHTML = '<div class="notification-empty">Failed to load notifications</div>';
            }
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                if (!silent && list && updateList) {
                    list.innerHTML = '<div class="notification-empty">Request timed out. Please try again.</div>';
                }
            } else {
                console.error('Error loading notifications:', error);
                if (!silent && list && updateList) {
                    list.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
                }
            }
        })
        .finally(() => {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        });
}

let adminMarkAllInFlight = false;

function markAllAdminNotifications(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (adminMarkAllInFlight) {
        return;
    }
    
    const button = event ? event.currentTarget : null;
    const originalLabel = button ? button.innerHTML : '';
    
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Working...';
    }
    
    adminMarkAllInFlight = true;
    
    const formData = new FormData();
    formData.append('is_admin', 'true');
    
    fetch('<?php echo BASE_URL; ?>/api/ajax/mark-all-notifications-read.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadAdminNotifications({ silent: false, updateList: true });
                updateAdminNotificationBadge(0);
            } else if (data.message) {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error marking all notifications:', error);
            alert('Unable to mark notifications right now. Please try again.');
        })
        .finally(() => {
            adminMarkAllInFlight = false;
            if (button) {
                button.disabled = false;
                button.innerHTML = originalLabel || '<i class="fas fa-check-double"></i> Mark all';
            }
        });
}

function updateAdminNotificationBadge(count) {
    const badge = document.getElementById('adminNotificationBadge');
    badge.textContent = count;
    if (count > 0) {
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function displayAdminNotifications(notifications) {
    const list = document.getElementById('adminNotificationList');
    
    if (notifications.length === 0) {
        list.innerHTML = '<div class="notification-empty">No notifications</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const iconClass = getNotificationIconClass(notif.type);
        const unreadClass = !notif.is_read ? 'unread' : '';
        const link = notif.link || '#';
        
        return `
            <div class="notification-item ${unreadClass}" onclick="markNotificationRead(${notif.id}, true, '${link}')">
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
    
    fetch('<?php echo BASE_URL; ?>/api/ajax/mark-notification-read.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadAdminNotifications();
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
    const dropdown = document.getElementById('adminNotificationDropdown');
    const button = document.getElementById('adminNotificationBtn');
    
    if (adminNotificationDropdownOpen && 
        !dropdown.contains(event.target) && 
        !button.contains(event.target)) {
        closeNotificationDropdown('admin');
    }
});

// Load notification badge count on page load (silent, no spinner)
document.addEventListener('DOMContentLoaded', function() {
    loadAdminNotifications({ silent: true, updateList: false });
    
    // Refresh badge count every 60 seconds without affecting UI
    setInterval(() => {
        loadAdminNotifications({ silent: true, updateList: false });
    }, 60000);
});
</script>

<div class="admin-header">
    <button class="sidebar-toggle" onclick="toggleAdminSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="header-left">
        <h2 class="welcome-text">Welcome, <?php echo htmlspecialchars($adminName); ?></h2>
        <p class="date-text"><?php echo $currentDate; ?></p>
    </div>
    
    <div class="header-right">
        <div class="search-bar d-none d-lg-block">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
        
        <div class="header-actions">
            <div class="notification-wrapper">
                <button class="notification-btn" id="adminNotificationBtn" title="Notifications" onclick="toggleNotificationDropdown('admin')">
                <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="adminNotificationBadge">0</span>
                </button>
                <div class="notification-dropdown" id="adminNotificationDropdown">
                    <div class="notification-header">
                        <h6>Notifications</h6>
                        <div class="notification-header-actions">
                            <button class="btn-mark-all" onclick="markAllAdminNotifications(event)">
                                <i class="fas fa-check-double"></i> Mark all
                            </button>
                            <button class="btn-close-notifications" onclick="closeNotificationDropdown('admin')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="notification-list" id="adminNotificationList">
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="<?php echo BASE_URL; ?>/admin/notifications.php" class="view-all-link">View All Notifications</a>
                    </div>
                </div>
            </div>
            
            <div class="user-avatar" title="<?php echo htmlspecialchars($adminName); ?>">
                <?php 
                $initials = strtoupper(substr($adminName, 0, 1));
                echo $initials;
                ?>
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
