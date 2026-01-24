/**
 * Global Modal Fix JavaScript - Prevents Flickering and Stability Issues
 * ThinQShopping Platform
 * This file must be loaded AFTER Bootstrap JS
 */

(function() {
    'use strict';
    
    // Prevent multiple initializations
    if (window.modalFixInitialized) {
        return;
    }
    window.modalFixInitialized = true;
    
    // Wait for DOM and Bootstrap to be ready
    var retryCount = 0;
    var maxRetries = 50; // Maximum 5 seconds (50 * 100ms)
    
    function initModalFix() {
        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(initModalFix, 100);
            } else {
                console.warn('Bootstrap Modal not found after maximum retries. Skipping modal fix.');
            }
            return;
        }
        
        // Reset retry count on success
        retryCount = 0;
        
        var Modal = bootstrap.Modal;
        
        // Fix all existing modals
        var modals = document.querySelectorAll('.modal');
        
        modals.forEach(function(modalElement) {
            fixModal(modalElement, Modal);
        });
        
        // Watch for dynamically added modals
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && node.classList.contains('modal')) {
                            fixModal(node, Modal);
                        }
                        // Check children
                        var childModals = node.querySelectorAll && node.querySelectorAll('.modal');
                        if (childModals) {
                            childModals.forEach(function(modal) {
                                fixModal(modal, Modal);
                            });
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    function fixModal(modalElement, Modal) {
        // Skip if already fixed
        if (modalElement.dataset.modalFixed === 'true') {
            return;
        }
        modalElement.dataset.modalFixed = 'true';
        
        var modalInstance = null;
        
        try {
            // Get or create modal instance
            modalInstance = Modal.getInstance(modalElement) || new Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
        } catch(e) {
            console.warn('Modal initialization error:', e);
        }
        
        // Force stable positioning
        function stabilizeModal() {
            modalElement.style.position = 'fixed';
            modalElement.style.top = '0';
            modalElement.style.left = '0';
            modalElement.style.right = '0';
            modalElement.style.bottom = '0';
            modalElement.style.zIndex = '1055';
            modalElement.style.margin = '0';
            modalElement.style.padding = '0';
            modalElement.style.transform = 'translateZ(0)';
            modalElement.style.webkitTransform = 'translateZ(0)';
        }
        
        // Prevent flickering on show
        modalElement.addEventListener('show.bs.modal', function(e) {
            e.stopImmediatePropagation();
            stabilizeModal();
            
            // Force reflow
            void modalElement.offsetHeight;
            
            // Ensure backdrop is created properly
            setTimeout(function() {
                var backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.position = 'fixed';
                    backdrop.style.top = '0';
                    backdrop.style.left = '0';
                    backdrop.style.right = '0';
                    backdrop.style.bottom = '0';
                    backdrop.style.zIndex = '1050';
                    backdrop.style.width = '100vw';
                    backdrop.style.height = '100vh';
                    backdrop.style.margin = '0';
                    backdrop.style.padding = '0';
                    backdrop.style.transform = 'translateZ(0)';
                    backdrop.style.webkitTransform = 'translateZ(0)';
                }
            }, 10);
        }, true);
        
        // Stabilize after shown
        modalElement.addEventListener('shown.bs.modal', function(e) {
            e.stopImmediatePropagation();
            
            stabilizeModal();
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            
            // Ensure backdrop is visible
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.classList.add('show');
                backdrop.style.opacity = '0.5';
            }
            
            // Lock body scroll
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '0px';
            
            // Prevent any further changes
            void modalElement.offsetHeight;
        }, true);
        
        // Handle hide
        modalElement.addEventListener('hide.bs.modal', function(e) {
            e.stopImmediatePropagation();
            void modalElement.offsetHeight;
        }, true);
        
        // Clean up after hidden
        modalElement.addEventListener('hidden.bs.modal', function(e) {
            e.stopImmediatePropagation();
            
            // Remove modal classes
            modalElement.style.display = '';
            modalElement.classList.remove('show');
            
            // Remove backdrop
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop && !document.querySelector('.modal.show')) {
                backdrop.remove();
            }
            
            // Unlock body scroll
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }, true);
        
        // Ensure close button works
        var closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Fallback: manually hide
                    modalElement.classList.remove('show');
                    modalElement.style.display = 'none';
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            }, true);
        });
    }
    
    // Global backdrop click handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            var openModal = document.querySelector('.modal.show');
            if (openModal) {
                var modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Fallback
                    openModal.classList.remove('show');
                    openModal.style.display = 'none';
                    e.target.remove();
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            }
        }
    }, true);
    
    // Prevent ESC key issues
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            var openModal = document.querySelector('.modal.show');
            if (openModal) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                var modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Fallback
                    openModal.classList.remove('show');
                    openModal.style.display = 'none';
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                }
            }
        }
    }, true);
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalFix);
    } else {
        initModalFix();
    }
})();



