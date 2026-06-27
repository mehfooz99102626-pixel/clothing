/**
 * main.js - Core interactive scripts for VÈLO Fashion Store.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode State Manager
    const themeToggler = document.getElementById('theme-toggler');
    if (themeToggler) {
        // Load initial state
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        themeToggler.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
        });
    }

    // 2. Mobile Responsive Nav Bar Toggler
    const burgerMenu = document.getElementById('burger-menu');
    const navLinks = document.getElementById('nav-links');
    if (burgerMenu && navLinks) {
        burgerMenu.addEventListener('click', () => {
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
            // Toggle hamburger icon lines
            const spans = burgerMenu.querySelectorAll('span');
            spans[0].style.transform = navLinks.style.display === 'flex' ? 'rotate(45deg) translate(5px, 5px)' : 'none';
            spans[1].style.opacity = navLinks.style.display === 'flex' ? '0' : '1';
            spans[2].style.transform = navLinks.style.display === 'flex' ? 'rotate(-45deg) translate(5px, -5px)' : 'none';
        });
    }

    // 3. Tab System Controller (Product Details & Reviews Tabs)
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-content-pane');
    if (tabBtns.length > 0 && tabPanes.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.getAttribute('data-tab');
                
                // Clear active states
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));

                // Set new active state
                btn.classList.add('active');
                const matchingPane = document.getElementById(`tab-${targetTab}`);
                if (matchingPane) {
                    matchingPane.classList.add('active');
                }
            });
        });
    }

    // 4. Star Rating Hover/Select Selector for Reviews Form
    const starSelectors = document.querySelectorAll('.stars-select i');
    const ratingInput = document.getElementById('review-rating-input');
    if (starSelectors.length > 0 && ratingInput) {
        starSelectors.forEach(star => {
            star.addEventListener('mouseover', () => {
                const value = parseInt(star.getAttribute('data-value'));
                highlightStars(value);
            });

            star.addEventListener('mouseleave', () => {
                const currentValue = parseInt(ratingInput.value || '0');
                highlightStars(currentValue);
            });

            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data-value'));
                ratingInput.value = value;
                highlightStars(value);
            });
        });

        function highlightStars(val) {
            starSelectors.forEach(star => {
                const starVal = parseInt(star.getAttribute('data-value'));
                if (starVal <= val) {
                    star.className = 'fas fa-star'; // Filled star
                } else {
                    star.className = 'far fa-star'; // Empty star
                }
            });
        }
    }

    // 5. Image Thumbnails Switcher on Product Page
    const thumbItems = document.querySelectorAll('.gallery-thumb-item');
    const mainGalleryImg = document.getElementById('main-gallery-image');
    if (thumbItems.length > 0 && mainGalleryImg) {
        thumbItems.forEach(thumb => {
            thumb.addEventListener('click', () => {
                // Clear active
                thumbItems.forEach(t => t.classList.remove('active'));
                // Set active
                thumb.classList.add('active');
                // Change main image source
                const newSrc = thumb.querySelector('img').getAttribute('src');
                mainGalleryImg.setAttribute('src', newSrc);
            });
        });
    }

    // 6. Generic AJAX Helper to add items to Cart
    const addToCartForms = document.querySelectorAll('.ajax-add-to-cart-form');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            
            fetch(form.getAttribute('action') || 'api/cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showGlobalToast(data.message || 'Product added to cart!', 'success');
                    updateCartBadge(data.cart_count);
                } else {
                    showGlobalToast(data.message || 'Failed to add product.', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                showGlobalToast('An error occurred. Please try again.', 'error');
            });
        });
    });

    // 7. Generic AJAX Helper to toggle wishlist
    const wishlistToggles = document.querySelectorAll('.wishlist-toggle-btn');
    wishlistToggles.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('csrf_token', csrfToken);
            
            fetch('api/wishlist_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showGlobalToast(data.message, 'success');
                    // Toggle styling
                    if (data.status === 'added') {
                        btn.classList.add('active');
                        btn.querySelector('i').className = 'fas fa-heart';
                    } else {
                        btn.classList.remove('active');
                        btn.querySelector('i').className = 'far fa-heart';
                    }
                    updateWishlistBadge(data.wishlist_count);
                } else {
                    showGlobalToast(data.message || 'You must be logged in to manage your wishlist.', 'error');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1200);
                    }
                }
            })
            .catch(error => {
                console.error('Error managing wishlist:', error);
                showGlobalToast('An error occurred. Please try again.', 'error');
            });
        });
    });

    // Modal Quick View Opener
    const quickViewBtns = document.querySelectorAll('.quick-view-btn');
    const quickViewModal = document.getElementById('quickview-modal');
    if (quickViewBtns.length > 0 && quickViewModal) {
        const closeBtn = quickViewModal.querySelector('.modal-close-btn');
        closeBtn.addEventListener('click', () => {
            quickViewModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === quickViewModal) {
                quickViewModal.style.display = 'none';
            }
        });

        quickViewBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const pId = btn.getAttribute('data-product-id');
                const pName = btn.getAttribute('data-name');
                const pPrice = btn.getAttribute('data-price');
                const pDesc = btn.getAttribute('data-description');
                const pImg = btn.getAttribute('data-image');
                const pSizes = btn.getAttribute('data-sizes').split(',');
                const pColors = btn.getAttribute('data-colors').split(',');

                // Update modal elements
                quickViewModal.querySelector('.qv-name').textContent = pName;
                quickViewModal.querySelector('.qv-price').textContent = CURRENCY_SYMBOL_GLOBAL + parseFloat(pPrice).toFixed(2);
                quickViewModal.querySelector('.qv-desc').textContent = pDesc;
                quickViewModal.querySelector('.qv-img').setAttribute('src', pImg);
                quickViewModal.querySelector('.qv-prod-id').value = pId;

                // Build Sizes
                const sizesContainer = quickViewModal.querySelector('.qv-sizes-container');
                sizesContainer.innerHTML = '';
                pSizes.forEach((sz, idx) => {
                    const label = document.createElement('label');
                    label.innerHTML = `
                        <input type="radio" name="size" value="${sz.trim()}" ${idx === 0 ? 'checked' : ''} class="size-chip-input">
                        <span class="size-chip">${sz.trim()}</span>
                    `;
                    sizesContainer.appendChild(label);
                });

                // Build Colors
                const colorsContainer = quickViewModal.querySelector('.qv-colors-container');
                colorsContainer.innerHTML = '';
                pColors.forEach((col, idx) => {
                    const label = document.createElement('label');
                    label.style.display = 'inline-block';
                    label.style.marginRight = '8px';
                    label.innerHTML = `
                        <input type="radio" name="color" value="${col.trim()}" ${idx === 0 ? 'checked' : ''} style="accent-color: var(--accent);">
                        <span style="font-size:0.85rem; padding-left: 4px;">${col.trim()}</span>
                    `;
                    colorsContainer.appendChild(label);
                });

                // Open modal
                quickViewModal.style.display = 'flex';
            });
        });
    }

    // Helper functions
    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    function updateWishlistBadge(count) {
        const badge = document.querySelector('.wishlist-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // Global Toast Notification
    function showGlobalToast(message, type = 'success') {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            toastContainer.setAttribute('style', 'position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;');
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let bgColor = 'var(--primary)';
        let textColor = 'var(--background)';
        if (type === 'error') {
            bgColor = 'var(--error)';
            textColor = '#ffffff';
        } else if (type === 'success') {
            bgColor = 'var(--success)';
            textColor = '#ffffff';
        }

        toast.setAttribute('style', `
            background-color: ${bgColor};
            color: ${textColor};
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        `);
        toast.textContent = message;

        toastContainer.appendChild(toast);
        
        // Trigger transition
        setTimeout(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        }, 50);

        // Delete toast
        setTimeout(() => {
            toast.style.transform = 'translateY(-20px)';
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3500);
    }
});

// Setup global site constants variables for JS scripts
const CURRENCY_SYMBOL_GLOBAL = '$';
