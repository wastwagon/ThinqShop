<?php
/**
 * Premium UX Quick Start Examples
 * Copy these examples to see the premium components in action
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium UX Components - ThinQShopping</title>
    
    <!-- Your existing CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <!-- NEW: Premium UX System -->
    <link rel="stylesheet" href="assets/css/premium-ux.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Demo Page Styles -->
    <link rel="stylesheet" href="assets/css/pages/premium-ux-demo.css">
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <!-- Header -->
        <header style="text-align: center; margin-bottom: 60px;">
            <h1 class="heading-premium-1">Premium UX Components</h1>
            <p class="text-premium-lead">World-class design system for ThinQShopping</p>
        </header>

        <!-- Buttons Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Buttons</h2>
            
            <div class="demo-grid">
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Primary</h4>
                    <button class="btn-premium btn-premium-primary">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Secondary</h4>
                    <button class="btn-premium btn-premium-secondary">
                        <i class="fas fa-heart"></i>
                        Add to Wishlist
                    </button>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Ghost</h4>
                    <button class="btn-premium btn-premium-ghost">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Loading</h4>
                    <button class="btn-premium btn-premium-primary btn-premium-loading">
                        Processing...
                    </button>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Small</h4>
                    <button class="btn-premium btn-premium-primary btn-premium-sm">
                        Small Button
                    </button>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Large</h4>
                    <button class="btn-premium btn-premium-primary btn-premium-lg">
                        Large Button
                    </button>
                </div>
            </div>
        </section>

        <!-- Inputs Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Inputs</h2>
            
            <div class="demo-grid">
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Basic Input</h4>
                    <input type="text" class="input-premium" placeholder="Enter your email">
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">With Icon</h4>
                    <div class="input-group-premium">
                        <i class="fas fa-search input-icon"></i>
                        <input type="text" class="input-premium" placeholder="Search products...">
                    </div>
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Error State</h4>
                    <input type="email" class="input-premium input-premium-error" placeholder="Invalid email">
                </div>
                
                <div class="demo-item">
                    <h4 class="heading-premium-4" style="margin-bottom: 16px;">Success State</h4>
                    <input type="email" class="input-premium input-premium-success" placeholder="Valid email">
                </div>
            </div>
        </section>

        <!-- Cards Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Cards</h2>
            
            <div class="demo-grid">
                <div class="card-premium">
                    <div class="card-premium-header">
                        <h3 class="card-premium-title">Product Card</h3>
                        <p class="card-premium-subtitle">Electronics</p>
                    </div>
                    <div class="card-premium-body">
                        <p class="text-premium-body">High-quality wireless headphones with noise cancellation.</p>
                    </div>
                    <div class="card-premium-footer">
                        <span class="badge-premium badge-premium-success">In Stock</span>
                        <button class="btn-premium btn-premium-primary btn-premium-sm">Buy Now</button>
                    </div>
                </div>
                
                <div class="card-premium card-premium-interactive hover-lift">
                    <div class="card-premium-header">
                        <h3 class="card-premium-title">Interactive Card</h3>
                        <p class="card-premium-subtitle">Click me!</p>
                    </div>
                    <div class="card-premium-body">
                        <p class="text-premium-body">This card has hover effects and is clickable.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Badges Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Badges</h2>
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <span class="badge-premium badge-premium-primary">New</span>
                <span class="badge-premium badge-premium-success">In Stock</span>
                <span class="badge-premium badge-premium-error">Out of Stock</span>
                <span class="badge-premium badge-premium-warning">Low Stock</span>
                <span class="badge-premium badge-premium-info">Featured</span>
            </div>
        </section>

        <!-- Alerts Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Alerts</h2>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="alert-premium alert-premium-success">
                    <i class="fas fa-check-circle alert-premium-icon"></i>
                    <div class="alert-premium-content">
                        <div class="alert-premium-title">Success!</div>
                        <div class="alert-premium-message">Your order has been placed successfully.</div>
                    </div>
                </div>
                
                <div class="alert-premium alert-premium-error">
                    <i class="fas fa-exclamation-circle alert-premium-icon"></i>
                    <div class="alert-premium-content">
                        <div class="alert-premium-title">Error</div>
                        <div class="alert-premium-message">Please check your payment information.</div>
                    </div>
                </div>
                
                <div class="alert-premium alert-premium-warning">
                    <i class="fas fa-exclamation-triangle alert-premium-icon"></i>
                    <div class="alert-premium-content">
                        <div class="alert-premium-title">Warning</div>
                        <div class="alert-premium-message">Your session will expire in 5 minutes.</div>
                    </div>
                </div>
                
                <div class="alert-premium alert-premium-info">
                    <i class="fas fa-info-circle alert-premium-icon"></i>
                    <div class="alert-premium-content">
                        <div class="alert-premium-title">Info</div>
                        <div class="alert-premium-message">Free shipping on orders over $50.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Skeleton Loading Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Skeleton Loading</h2>
            
            <div class="card-premium">
                <div class="skeleton-premium skeleton-premium-title"></div>
                <div class="skeleton-premium skeleton-premium-text"></div>
                <div class="skeleton-premium skeleton-premium-text"></div>
                <div class="skeleton-premium skeleton-premium-text" style="width: 80%;"></div>
                <div class="skeleton-premium skeleton-premium-image" style="margin-top: 16px;"></div>
            </div>
        </section>

        <!-- Empty State Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Empty State</h2>
            
            <div class="empty-state-premium">
                <div class="empty-state-premium-icon">
                    <i class="fas fa-shopping-bag" style="font-size: 64px;"></i>
                </div>
                <h3 class="empty-state-premium-title">Your cart is empty</h3>
                <p class="empty-state-premium-message">
                    Start shopping to add items to your cart and enjoy our amazing products!
                </p>
                <button class="btn-premium btn-premium-primary">
                    <i class="fas fa-shopping-cart"></i>
                    Browse Products
                </button>
            </div>
        </section>

        <!-- Typography Section -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Premium Typography</h2>
            
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div>
                    <h1 class="heading-premium-1">Heading 1 - Main Title</h1>
                </div>
                <div>
                    <h2 class="heading-premium-2">Heading 2 - Section Title</h2>
                </div>
                <div>
                    <h3 class="heading-premium-3">Heading 3 - Subsection</h3>
                </div>
                <div>
                    <h4 class="heading-premium-4">Heading 4 - Card Title</h4>
                </div>
                <div>
                    <p class="text-premium-lead">Lead paragraph - Larger, more prominent text for introductions.</p>
                </div>
                <div>
                    <p class="text-premium-body">Body text - Regular paragraph text for content.</p>
                </div>
                <div>
                    <p class="text-premium-small">Small text - For secondary information.</p>
                </div>
                <div>
                    <p class="text-premium-xs">Extra small text - For captions and footnotes.</p>
                </div>
            </div>
        </section>

        <!-- Product Card Example -->
        <section class="demo-section">
            <h2 class="heading-premium-2 demo-title">Real Product Card Example</h2>
            
            <div class="demo-grid">
                <div class="card-premium card-premium-interactive hover-lift">
                    <img src="https://via.placeholder.com/300x200" alt="Product" style="width: 100%; border-radius: 8px; margin-bottom: 16px;">
                    <div class="card-premium-header">
                        <h3 class="card-premium-title">Premium Wireless Headphones</h3>
                        <p class="card-premium-subtitle">Electronics • Audio</p>
                    </div>
                    <div class="card-premium-body">
                        <p class="text-premium-body">High-quality sound with active noise cancellation.</p>
                        <div style="margin-top: 12px; display: flex; gap: 8px;">
                            <span class="badge-premium badge-premium-success">In Stock</span>
                            <span class="badge-premium badge-premium-primary">New</span>
                        </div>
                    </div>
                    <div class="card-premium-footer">
                        <div style="flex: 1;">
                            <div class="heading-premium-3" style="color: var(--primary);">$299.99</div>
                            <div class="text-premium-xs" style="text-decoration: line-through;">$399.99</div>
                        </div>
                        <button class="btn-premium btn-premium-primary btn-premium-sm">
                            <i class="fas fa-cart-plus"></i>
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <script>
        // Demo: Toggle loading state
        document.querySelectorAll('.btn-premium').forEach(btn => {
            if (!btn.classList.contains('btn-premium-loading')) {
                btn.addEventListener('click', function() {
                    console.log('Button clicked:', this.textContent.trim());
                });
            }
        });
        
        // Demo: Interactive cards
        document.querySelectorAll('.card-premium-interactive').forEach(card => {
            card.addEventListener('click', function() {
                console.log('Card clicked!');
            });
        });
    </script>
</body>
</html>
