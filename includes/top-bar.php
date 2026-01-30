<?php
/**
 * Premium Top Bar
 * Displayed above the main header
 */
?>
<div class="premium-top-bar">
    <div class="header-container">
        <div class="top-bar-content">
            <!-- Left: Contact -->
            <div class="top-bar-left">
                <a href="tel:<?php echo str_replace(' ', '', BUSINESS_PHONE); ?>" class="top-link">
                    <i class="fas fa-phone-alt"></i>
                    <?php echo BUSINESS_PHONE; ?>
                </a>
                <div class="top-socials">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Center: Flash Sale -->
            <div class="top-bar-center">
                <span class="flash-label">FLASH SALE ENDS IN:</span>
                <div class="countdown-timer" id="topBarCountdown">
                    <span class="time-block"><span class="days">81</span>d</span> : 
                    <span class="time-block"><span class="hours">16</span>h</span> : 
                    <span class="time-block"><span class="minutes">50</span>m</span> : 
                    <span class="time-block"><span class="seconds">25</span>s</span>
                </div>
            </div>

            <!-- Right: Links -->
            <div class="top-bar-right">
                <a href="<?php echo BASE_URL; ?>/public/track.php" class="top-link">Track Order</a>
                <a href="<?php echo BASE_URL; ?>/help.php" class="top-link">Help Center</a>
            </div>
        </div>
    </div>
</div>


<style>
    .premium-top-bar {
        background-color: #0a1f35; /* Darker than brand blue */
        color: #ffffff;
        font-size: 12px;
        padding: 8px 0;
        position: relative;
        z-index: 1001;
        /* Enable on all devices */
        display: block; 
    }
    
    .top-bar-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .top-bar-left, .top-bar-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .top-link {
        color: #ffffff !important; /* Force White */
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
        opacity: 0.9;
    }
    
    .top-link:hover {
        opacity: 1;
    }
    
    .top-socials {
        display: flex;
        gap: 12px;
        border-left: 1px solid rgba(255,255,255,0.2);
        padding-left: 20px;
    }
    
    .top-socials a {
        color: #ffffff !important; /* Force White */
        opacity: 0.9;
        transition: opacity 0.2s;
    }
    
    .top-socials a:hover {
        opacity: 1;
    }

    .top-bar-center {
        display: flex;
        align-items: center;
        gap: 10px;
        /* Ensure it takes center stage on mobile */
        justify-content: center;
        flex-grow: 1; 
    }
    
    .flash-label {
        font-weight: 700;
        color: #ffffff !important; /* Force White */
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .countdown-timer {
        font-family: monospace;
        font-weight: 700;
        color: #ffffff !important; /* Force White */
        display: flex;
        gap: 4px;
    }

    /* Timer blocks */
    .time-block {
        background: rgba(255, 255, 255, 0.15);
        padding: 1px 4px;
        border-radius: 4px;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 991px) {
        .top-bar-right, 
        .top-socials {
            display: none !important;
        }
        
        .top-bar-left {
            /* Only show phone icon/number if space permits, or hide on very small */
            gap: 10px;
        }
        
        /* On Tablet/Mobile, center the content */
        .top-bar-content {
            justify-content: center;
            gap: 15px;
        }
    }

    @media (max-width: 576px) {
        /* Mobile Specific */
        .premium-top-bar {
            padding: 6px 0;
            font-size: 11px;
        }

        .top-bar-left {
            display: none !important; /* Hide Phone on very small screens to focus on Sale */
        }

        .top-bar-center {
            width: 100%;
            justify-content: center;
            gap: 8px;
        }

        .flash-label {
            font-size: 11px;
        }
        
        /* Compact Timer for Mobile */
        .countdown-timer {
            font-size: 11px;
        }
    }
</style>

<script>
    // Simple 24h countdown loop for demo
    function startTopCountdown() {
        // Set target to 24 hours from now just for display
        // In real app, this would be a fixed date
        const now = new Date();
        const target = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 81); // 81 days out per screenshot
        
        function update() {
            const current = new Date();
            const diff = target - current;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            const el = document.getElementById('topBarCountdown');
            if(el) {
                el.innerHTML = `
                    <span class="time-block"><span class="days">${days}</span>d</span> : 
                    <span class="time-block"><span class="hours">${hours}</span>h</span> : 
                    <span class="time-block"><span class="minutes">${minutes}</span>m</span> : 
                    <span class="time-block"><span class="seconds">${seconds}</span>s</span>
                `;
            }
        }
        
        setInterval(update, 1000);
        update();
    }
    
    document.addEventListener('DOMContentLoaded', startTopCountdown);
</script>
