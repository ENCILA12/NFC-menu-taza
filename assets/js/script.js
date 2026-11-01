// Search & Filter Logic
const filterBtns = document.querySelectorAll('.filter-btn');
const menuCards = document.querySelectorAll('.menu-card');
const searchInput = document.getElementById('menuSearchInput');
const clearSearchBtn = document.getElementById('clearSearchBtn');
const noResultsMessage = document.getElementById('noResultsMessage');

let currentFilter = 'all';
let currentSearch = '';

function applyFilterAndSearch() {
    let visibleCount = 0;

    menuCards.forEach(card => {
        const category = card.getAttribute('data-category') || '';
        const name = card.getAttribute('data-name') || '';
        const desc = card.getAttribute('data-description') || '';

        const matchesCategory = (currentFilter === 'all' || category === currentFilter);
        const matchesSearch = !currentSearch || name.includes(currentSearch) || desc.includes(currentSearch);

        if (matchesCategory && matchesSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (noResultsMessage) {
        noResultsMessage.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.getAttribute('data-filter') || 'all';
        applyFilterAndSearch();
    });
});

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        currentSearch = e.target.value.trim().toLowerCase();
        if (clearSearchBtn) {
            clearSearchBtn.style.display = currentSearch.length > 0 ? 'flex' : 'none';
        }
        applyFilterAndSearch();
    });
}

if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        currentSearch = '';
        clearSearchBtn.style.display = 'none';
        applyFilterAndSearch();
        if (searchInput) searchInput.focus();
    });
}

// Modal Logic
const modal = document.getElementById('itemModal');
const modalImage = document.getElementById('modalImage');
const modalName = document.getElementById('modalName');
const modalDescription = document.getElementById('modalDescription');
const modalTags = document.getElementById('modalTags');

// Touch swipe gestures on Modal & History state handling for mobile back gestures
let touchStartX = 0;
let touchStartY = 0;
let touchEndX = 0;
let touchEndY = 0;

let currentModalItem = null;
let currentModalQty = 1;

// Cart State (Order list of selected items)
let cart = JSON.parse(localStorage.getItem('taza_cart') || '[]');

function saveCart() {
    localStorage.setItem('taza_cart', JSON.stringify(cart));
    updateCartUI();
}

function updateCartUI() {
    const floatingCart = document.getElementById('floatingCart');
    const cartBadge = document.getElementById('cartItemCount');
    const cartItemsList = document.getElementById('cartItemsList');

    if (!floatingCart) return;

    let totalQty = 0;
    cart.forEach(item => {
        totalQty += item.qty;
    });

    if (cartBadge) cartBadge.textContent = totalQty;

    if (totalQty > 0) {
        floatingCart.classList.add('visible');
    } else {
        floatingCart.classList.remove('visible');
    }

    // Populate order list with product image
    if (cartItemsList) {
        if (cart.length === 0) {
            cartItemsList.innerHTML = `
                <div class="cart-empty-message">
                    <i class="ph ph-shopping-bag" style="font-size: 2.5rem; display:block; margin-bottom: 8px; opacity: 0.5;"></i>
                    Your order list is empty.<br>Choose items from the menu to add!
                </div>
            `;
        } else {
            cartItemsList.innerHTML = cart.map(item => {
                // Find matching item in menuData to ensure updated image
                const matched = (typeof menuData !== 'undefined') ? menuData.find(m => m.id === item.id) : null;
                const imgPath = (matched && matched.image) ? matched.image : (item.image || '');
                const hasValidImg = imgPath && !imgPath.includes('default.png');
                
                const imgHtml = hasValidImg
                    ? `<img src="${imgPath}" alt="${item.name}" class="cart-item-thumb">`
                    : `<div class="cart-item-thumb-placeholder">☕</div>`;

                return `
                    <div class="cart-item-row">
                        <div class="cart-item-media">
                            ${imgHtml}
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.name}</div>
                        </div>
                        <div class="cart-item-controls">
                            <div class="cart-qty-ctrl">
                                <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, -1)">-</button>
                                <span class="cart-item-qty">${item.qty}</span>
                                <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, 1)">+</button>
                            </div>
                            <button class="cart-del-btn" style="background:none;border:none;color:#d9534f;cursor:pointer;font-size:1.1rem;padding:4px;" onclick="removeCartItem(${item.id})" title="Remove item">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }
}

function adjustModalQty(delta) {
    currentModalQty = Math.max(1, currentModalQty + delta);
    const display = document.getElementById('modalQtyDisplay');
    if (display) display.textContent = currentModalQty;
}

function addCurrentItemToCart() {
    if (!currentModalItem) return;
    
    const existingIndex = cart.findIndex(i => i.id === currentModalItem.id);

    if (existingIndex > -1) {
        cart[existingIndex].qty += currentModalQty;
        // ensure image is stored
        if (currentModalItem.image) {
            cart[existingIndex].image = currentModalItem.image;
        }
    } else {
        cart.push({
            id: currentModalItem.id,
            name: currentModalItem.name,
            qty: currentModalQty,
            image: currentModalItem.image || ''
        });
    }

    saveCart();
    closeModal();
}

function updateCartItemQty(id, delta) {
    const itemIndex = cart.findIndex(i => i.id === id);
    if (itemIndex > -1) {
        cart[itemIndex].qty += delta;
        if (cart[itemIndex].qty <= 0) {
            cart.splice(itemIndex, 1);
        }
        saveCart();
    }
}

function removeCartItem(id) {
    const itemIndex = cart.findIndex(i => i.id === id);
    if (itemIndex > -1) {
        cart.splice(itemIndex, 1);
        saveCart();
    }
}

function clearCart() {
    if (confirm('Are you sure you want to clear your order?')) {
        cart = [];
        saveCart();
    }
}

function promptCustomerBill() {
    // No popup alert needed, Pay at the Counter is displayed directly
}

// Cart Drawer / Modal open & close
const cartModal = document.getElementById('cartModal');

function openCartModal() {
    if (!cartModal) return;
    cartModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    history.pushState({ cartOpen: true }, '');
}

function closeCartModal(fromHistory = false) {
    if (!cartModal || !cartModal.classList.contains('active')) return;
    cartModal.classList.remove('active');
    document.body.style.overflow = '';

    if (!fromHistory && history.state && history.state.cartOpen) {
        history.back();
    }
}

if (cartModal) {
    cartModal.addEventListener('click', (e) => {
        if (e.target === cartModal) {
            closeCartModal();
        }
    });
}

function openModal(id) {
    // Find item in data array
    const item = menuData.find(i => i.id === id);
    if (!item) return;

    currentModalItem = item;
    currentModalQty = 1;
    const qtyDisplay = document.getElementById('modalQtyDisplay');
    if (qtyDisplay) qtyDisplay.textContent = '1';

    const addBtn = document.getElementById('modalAddToCartBtn');
    if (item.is_available === false) {
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="ph ph-prohibit"></i> Not Available';
        }
    } else {
        if (addBtn) {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="ph ph-shopping-cart-simple"></i> Add to Order';
        }
    }

    // Populate modal image
    const modalNoPic = document.getElementById('modalNoPic');
    if (!item.image || item.image.includes('default.png')) {
        modalImage.style.display = 'none';
        modalNoPic.style.display = 'flex';
    } else {
        modalImage.style.display = 'block';
        modalNoPic.style.display = 'none';
        modalImage.src = item.image;
    }
    modalName.textContent = item.name;
    const modalPriceElem = document.getElementById('modalPrice');
    if (modalPriceElem) modalPriceElem.textContent = item.price;
    modalDescription.textContent = item.description;
    
    // Populate tags
    modalTags.innerHTML = '';
    if (item.tags && item.tags.length > 0) {
        item.tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'tag';
            span.textContent = tag;
            modalTags.appendChild(span);
        });
    }

    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Add state to browser history
    history.pushState({ modalOpen: true }, '');
}

function closeModal(fromHistory = false) {
    if (!modal || !modal.classList.contains('active')) return;
    
    modal.classList.remove('active');
    document.body.style.overflow = ''; 
    currentModalItem = null;

    if (!fromHistory && history.state && history.state.modalOpen) {
        history.back();
    }
}

// Intercept mobile back button / back swipe
window.addEventListener('popstate', (e) => {
    if (cartModal && cartModal.classList.contains('active')) {
        closeCartModal(true);
    } else if (modal && modal.classList.contains('active')) {
        closeModal(true);
    }
});

// Close modal when clicking outside content
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});

// Touch swipe gesture listener on modal to close cleanly on swipe left/right or down
const modalContent = document.querySelector('.modal-content');
if (modalContent) {
    modalContent.addEventListener('touchstart', (e) => {
        if (!e.touches || e.touches.length === 0) return;
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    modalContent.addEventListener('touchmove', (e) => {
        // If swiping horizontally across the modal, prevent Chrome/browser default back-forward swipe
        if (!e.touches || e.touches.length === 0) return;
        const currentX = e.touches[0].clientX;
        const currentY = e.touches[0].clientY;
        const deltaX = Math.abs(currentX - touchStartX);
        const deltaY = Math.abs(currentY - touchStartY);

        if (deltaX > deltaY && deltaX > 15) {
            if (e.cancelable) {
                e.preventDefault();
            }
        }
    }, { passive: false });

    modalContent.addEventListener('touchend', (e) => {
        if (!e.changedTouches || e.changedTouches.length === 0) return;
        touchEndX = e.changedTouches[0].clientX;
        touchEndY = e.changedTouches[0].clientY;
        handleSwipeGesture();
    }, { passive: true });
}

function handleSwipeGesture() {
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;

    // Horizontal swipe left or right (more than 50px)
    if (Math.abs(diffX) > 50 && Math.abs(diffX) > Math.abs(diffY)) {
        closeModal();
    } else if (diffY > 70 && modalContent && modalContent.scrollTop <= 0) {
        // Pull down gesture
        closeModal();
    }
}

// Initialize Cart UI on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
});
updateCartUI();


