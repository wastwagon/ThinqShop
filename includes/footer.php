    <!-- Clean Premium Footer -->
    <footer class="bh-footer">
        <div class="footer-wrapper">
            <div class="container">
                <!-- Top Section: Social Media -->
                <div class="footer-top-section mb-5">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-12 text-center">
                            <h5 class="footer-newsletter-title mb-3">Follow Us</h5>
                            <div class="footer-social-icons d-inline-flex align-items-center gap-4">
                                <a href="#" class="social-icon" style="font-size: 1.5rem;"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon" style="font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-icon" style="font-size: 1.5rem;"><i class="fab fa-whatsapp"></i></a>
                                <a href="#" class="social-icon" style="font-size: 1.5rem;"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="footer-divider border-secondary my-4">

                <!-- Main Footer Content: 4 Columns -->
                <div class="row g-4">
                    <!-- Column 1: Our Services -->
                    <div class="col-lg-3 col-md-6">
                        <h6 class="footer-column-title text-white text-uppercase fw-bold mb-3 ls-1">Our Services</h6>
                        <ul class="footer-links-list list-unstyled">
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/shop.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">Online Shopping</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="text-white text-decoration-none opacity-75 hover-opacity-100">Money Transfer</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="text-white text-decoration-none opacity-75 hover-opacity-100">Logistics & Shipping</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="text-white text-decoration-none opacity-75 hover-opacity-100">Procurement Services</a></li>
                        </ul>
                    </div>

                    <!-- Column 2: Shop Categories -->
                    <div class="col-lg-3 col-md-6">
                        <h6 class="footer-column-title text-white text-uppercase fw-bold mb-3 ls-1">Shop Categories</h6>
                        <ul class="footer-links-list list-unstyled">
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/shop.php?category=electronics" class="text-white text-decoration-none opacity-75 hover-opacity-100">Electronics</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/shop.php?category=home-appliances" class="text-white text-decoration-none opacity-75 hover-opacity-100">Home Appliances</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/shop.php?category=fashion" class="text-white text-decoration-none opacity-75 hover-opacity-100">Fashion & Apparel</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/shop.php?category=photography" class="text-white text-decoration-none opacity-75 hover-opacity-100">Photography</a></li>
                        </ul>
                    </div>

                    <!-- Column 3: Support & Info -->
                    <div class="col-lg-3 col-md-6">
                        <h6 class="footer-column-title text-white text-uppercase fw-bold mb-3 ls-1">Support & Info</h6>
                        <ul class="footer-links-list list-unstyled">
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/help.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">Help Center</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/contact.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">Contact Us</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/about.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">About ThinQShopping</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/privacy.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">Privacy Policy</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/terms.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">Terms of Service</a></li>
                        </ul>
                    </div>

                    <!-- Column 4: Contact Info (Merged) -->
                    <div class="col-lg-3 col-md-6">
                        <h6 class="footer-column-title text-white text-uppercase fw-bold mb-3 ls-1">Expert Advice</h6>
                        <div class="footer-contact-info mb-4">
                            <a href="tel:+8618320709024" class="text-white text-decoration-none fw-bold d-block mb-1" style="font-size: 1.1rem;">+86 183 2070 9024</a>
                            <small class="text-white opacity-75">Available 24/7 Support</small>
                        </div>
                        
                        <h6 class="footer-column-title text-white text-uppercase fw-bold mb-3 ls-1">Quick Actions</h6>
                        <div class="d-flex flex-column gap-2">
                             <a href="<?php echo BASE_URL; ?>/contact.php" class="text-white text-decoration-none opacity-75 hover-opacity-100"><i class="fas fa-envelope me-2"></i> Email Support</a>
                             <a href="<?php echo BASE_URL; ?>/about.php" class="text-white text-decoration-none opacity-75 hover-opacity-100"><i class="fas fa-info-circle me-2"></i> About Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="footer-divider">

        <!-- Bottom Footer -->
        <div class="footer-bottom-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="footer-copyright">
                            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Professional Services for Ghana.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-legal-links">
                            <a href="<?php echo BASE_URL; ?>/terms.php">Terms of use</a>
                            <a href="<?php echo BASE_URL; ?>/privacy.php">Privacy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <!-- GLightbox JS (for image lightbox) -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo asset('assets/js/main.js'); ?>"></script>
    
    <!-- Dropdown Hover & Touch Support -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if device supports hover
        const hasHover = window.matchMedia('(hover: hover)').matches;
        
        if (hasHover) {
            // Desktop with hover: Show on hover
            const categoriesDropdown = document.querySelector('.categories-dropdown');
            const navDropdowns = document.querySelectorAll('.main-nav .nav-item.dropdown');
            
            // Prevent Bootstrap from toggling on click when hover is available
            if (categoriesDropdown) {
                const btn = categoriesDropdown.querySelector('.btn-categories');
                if (btn) {
                    btn.setAttribute('data-bs-auto-close', 'outside');
                }
                
                let hoverTimeout;
                categoriesDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    this.classList.add('show');
                    const menu = this.querySelector('.categories-dropdown-menu');
                    if (menu) {
                        menu.style.display = 'block';
                        setTimeout(() => {
                            menu.style.opacity = '1';
                            menu.style.transform = 'translateY(0)';
                        }, 10);
                    }
                });
                
                categoriesDropdown.addEventListener('mouseleave', function() {
                    const menu = this.querySelector('.categories-dropdown-menu');
                    if (menu) {
                        menu.style.opacity = '0';
                        menu.style.transform = 'translateY(-10px)';
                        hoverTimeout = setTimeout(() => {
                            this.classList.remove('show');
                            menu.style.display = 'none';
                        }, 300);
                    }
                });
            }
            
            // Navigation dropdowns hover
            navDropdowns.forEach(function(dropdown) {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.addEventListener('click', function(e) {
                        // Prevent default click behavior on hover-capable devices
                        e.preventDefault();
                        e.stopPropagation();
                    });
                }
                
                dropdown.addEventListener('mouseenter', function() {
                    this.classList.add('show');
                    const menu = this.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.style.display = 'block';
                        setTimeout(() => {
                            menu.style.opacity = '1';
                            menu.style.transform = 'translateY(0)';
                        }, 10);
                    }
                });
                
                dropdown.addEventListener('mouseleave', function() {
                    const menu = this.querySelector('.dropdown-menu');
                    if (menu) {
                        menu.style.opacity = '0';
                        menu.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            this.classList.remove('show');
                            menu.style.display = 'none';
                        }, 300);
                    }
                });
            });
        }
        // On touch devices, Bootstrap's default click behavior will handle dropdowns
    });
    </script>
    
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo asset($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="quickViewModalLabel">Quick View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="quickViewContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openQuickView(productId) {
        const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        const content = document.getElementById('quickViewContent');
        
        // Show loading
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        modal.show();
        
        // Fetch product data
        fetch('<?php echo BASE_URL; ?>/public/quick-view.php?id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const product = data.product;
                    let variantsHtml = '';
                    
                    if (product.has_variants && product.variants.length > 0) {
                        const variantsGrouped = {};
                        product.variants.forEach(v => {
                            if (!variantsGrouped[v.variant_type]) {
                                variantsGrouped[v.variant_type] = [];
                            }
                            variantsGrouped[v.variant_type].push(v);
                        });
                        
                        variantsHtml = Object.keys(variantsGrouped).map(type => `
                            <div class="mb-3">
                                <label class="form-label">${type.charAt(0).toUpperCase() + type.slice(1)}</label>
                                <select name="variant_${type}" class="form-select" required>
                                    <option value="">Select ${type}</option>
                                    ${variantsGrouped[type].map(v => 
                                        `<option value="${v.id}" ${v.stock_quantity > 0 ? '' : 'disabled'}>
                                            ${v.variant_value}${v.price_adjust != 0 ? ` (${v.price_adjust > 0 ? '+' : ''}₵${parseFloat(v.price_adjust).toFixed(2)})` : ''}
                                        </option>`
                                    ).join('')}
                                </select>
                            </div>
                        `).join('');
                    }
                    
                    // Build thumbnail gallery HTML if multiple images
                    let thumbnailGalleryHtml = '';
                    if (product.images && product.images.length > 1) {
                        thumbnailGalleryHtml = `
                            <div class="col-auto">
                                <div class="quick-view-thumbnail-gallery">
                                    ${product.images.map((img, idx) => `
                                        <div class="quick-view-thumbnail-item ${idx === 0 ? 'active' : ''}" 
                                             onclick="changeQuickViewImage(this, '${img}', ${idx})">
                                            <img src="${img}" alt="Thumbnail ${idx + 1}" class="img-fluid rounded">
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }
                    
                    content.innerHTML = `
                        <div class="row g-3">
                            ${thumbnailGalleryHtml}
                            <div class="col">
                                <div class="quick-view-image position-relative">
                                    <img id="quickViewMainImage" src="${product.main_image}" 
                                         alt="${product.name}" 
                                         class="img-fluid rounded"
                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/products/default.jpg';">
                                    ${product.has_discount ? `<span class="badge bg-danger position-absolute top-0 start-0 m-2">-${product.discount_percent}%</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h4 class="mb-2">${product.name}</h4>
                                ${product.short_description ? `<p class="text-muted mb-3">${product.short_description}</p>` : ''}
                                <div class="mb-3">
                                    <h5 class="text-primary mb-1">
                                        ₵${parseFloat(product.price).toFixed(2)}
                                        ${product.has_discount ? `<small class="text-muted text-decoration-line-through ms-2">₵${parseFloat(product.compare_price).toFixed(2)}</small>` : ''}
                                    </h5>
                                </div>
                                <div class="mb-3">
                                    ${product.stock_quantity > 0 ? 
                                        `<span class="badge bg-success"><i class="fas fa-check"></i> In Stock</span>` : 
                                        `<span class="badge bg-danger"><i class="fas fa-times"></i> Out of Stock</span>`
                                    }
                                </div>
                                <form action="<?php echo BASE_URL; ?>/modules/ecommerce/cart/add.php" method="POST" id="quickViewForm">
                                    <input type="hidden" name="product_id" value="${product.id}">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    ${variantsHtml}
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="${product.stock_quantity}" required>
                                    </div>
                                    <div class="d-grid gap-2">
                                        ${product.stock_quantity > 0 ? 
                                            `<button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>` :
                                            `<button type="button" class="btn btn-secondary btn-lg" disabled>
                                                Out of Stock
                                            </button>`
                                        }
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-danger flex-fill ${product.in_wishlist ? 'active' : ''}" onclick="toggleWishlist(${product.id})" title="${product.in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'}">
                                                <i class="${product.in_wishlist ? 'fas' : 'far'} fa-heart"></i> Wishlist
                                            </button>
                                            <a href="${product.url}" class="btn btn-outline-primary flex-fill">
                                                View Full Details
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    `;
                } else {
                    content.innerHTML = '<div class="alert alert-danger">Product not found.</div>';
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-danger">Error loading product. Please try again.</div>';
                console.error('Error:', error);
            });
    }
    
    function changeQuickViewImage(element, imageUrl, index) {
        const mainImage = document.getElementById('quickViewMainImage');
        if (mainImage) {
            mainImage.src = imageUrl;
        }
        
        // Update active thumbnail
        document.querySelectorAll('.quick-view-thumbnail-item').forEach(item => {
            item.classList.remove('active');
        });
        element.classList.add('active');
    }
    
    function toggleWishlist(productId) {
        <?php if (isLoggedIn()): ?>
        fetch('<?php echo BASE_URL; ?>/modules/ecommerce/wishlist/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update wishlist button icon
                const wishlistButtons = document.querySelectorAll(`[onclick*="toggleWishlist(${productId})"]`);
                wishlistButtons.forEach(btn => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        if (data.in_wishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            btn.classList.add('active');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            btn.classList.remove('active');
                        }
                    }
                });
                
                // Show notification
                if (data.in_wishlist) {
                    showNotification('Added to wishlist!', 'success');
                } else {
                    showNotification('Removed from wishlist', 'info');
                }
                
                // Update wishlist count in header if exists
                const wishlistBadge = document.querySelector('.action-link[href*="wishlist"] .badge-count');
                if (wishlistBadge) {
                    const currentCount = parseInt(wishlistBadge.textContent) || 0;
                    wishlistBadge.textContent = data.in_wishlist ? currentCount + 1 : Math.max(0, currentCount - 1);
                    if (wishlistBadge.textContent === '0') {
                        wishlistBadge.style.display = 'none';
                    } else {
                        wishlistBadge.style.display = 'inline-block';
                    }
                }
            } else {
                showNotification(data.message || 'Error updating wishlist', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating wishlist. Please try again.', 'danger');
        });
        <?php else: ?>
        window.location.href = '<?php echo BASE_URL; ?>/login.php';
        <?php endif; ?>
    }
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
    

    
    <!-- Mobile Footer -->
    <?php include __DIR__ . '/mobile-footer.php'; ?>
    
</body>
</html>
