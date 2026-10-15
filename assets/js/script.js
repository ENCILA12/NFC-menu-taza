// Filter Logic
const filterBtns = document.querySelectorAll('.filter-btn');
const menuCards = document.querySelectorAll('.menu-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all
        filterBtns.forEach(b => b.classList.remove('active'));
        // Add to clicked
        btn.classList.add('active');

        const filterValue = btn.getAttribute('data-filter');

        menuCards.forEach(card => {
            if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                card.style.display = 'flex';
                // Slight animation
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.4s ease';
                    card.style.opacity = '1';
                }, 50);
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Modal Logic
const modal = document.getElementById('itemModal');
const modalImage = document.getElementById('modalImage');
const modalName = document.getElementById('modalName');
const modalPrice = document.getElementById('modalPrice');
const modalDescription = document.getElementById('modalDescription');
const modalTags = document.getElementById('modalTags');

// Touch swipe gestures on Modal & History state handling for mobile back gestures
let touchStartX = 0;
let touchStartY = 0;
let touchEndX = 0;
let touchEndY = 0;

let currentModalItem = null;
let currentModalQty = 1;

// Cart State (stored in localStorage for seamless experience)
let cart = JSON.parse(localStorage.getItem('taza_cart') || '[]');

function parsePrice(priceStr) {
    if (typeof priceStr === 'number') return priceStr;
    if (!priceStr) return 0;
    // Strip peso symbol and commas
    const cleaned = priceStr.replace(/[^0-9.]/g, '');
    return parseFloat(cleaned) || 0;
}

function formatPrice(amount) {
    return '₱' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function saveCart() {
    localStorage.setItem('taza_cart', JSON.stringify(cart));
    updateCartUI();
}

function updateCartUI() {
    const floatingCart = document.getElementById('floatingCart');
    const cartBadge = document.getElementById('cartItemCount');
    const cartBarTotal = document.getElementById('cartBarTotal');
    const cartGrandTotal = document.getElementById('cartGrandTotal');
    const cartItemsList = document.getElementById('cartItemsList');

    if (!floatingCart) return;

    let totalQty = 0;
    let grandTotal = 0;

    cart.forEach(item => {
        totalQty += item.qty;
        grandTotal += item.price * item.qty;
    });

    cartBadge.textContent = totalQty;
    cartBarTotal.textContent = formatPrice(grandTotal);
    if (cartGrandTotal) cartGrandTotal.textContent = formatPrice(grandTotal);

    if (totalQty > 0) {
        floatingCart.classList.add('visible');
    } else {
        floatingCart.classList.remove('visible');
    }

    // Populate drawer list
    if (cartItemsList) {
        if (cart.length === 0) {
            cartItemsList.innerHTML = `
                <div class="cart-empty-message">
                    <i class="ph ph-shopping-bag" style="font-size: 2.5rem; display:block; margin-bottom: 8px; opacity: 0.5;"></i>
                    Your order is empty.<br>Choose items from the menu to get started!
                </div>
            `;
        } else {
            cartItemsList.innerHTML = cart.map(item => `
                <div class="cart-item-row">
                    <div class="cart-item-info">
                        <div class="cart-item-title">${item.name}</div>
                        <div class="cart-item-unit-price">${formatPrice(item.price)} each</div>
                    </div>
                    <div class="cart-item-controls">
                        <div class="cart-qty-ctrl">
                            <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, -1)">-</button>
                            <span class="cart-item-qty">${item.qty}</span>
                            <button class="cart-qty-btn" onclick="updateCartItemQty(${item.id}, 1)">+</button>
                        </div>
                        <div class="cart-item-subtotal">${formatPrice(item.price * item.qty)}</div>
                    </div>
                </div>
            `).join('');
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
    const priceNum = parsePrice(currentModalItem.price);

    if (existingIndex > -1) {
        cart[existingIndex].qty += currentModalQty;
    } else {
        cart.push({
            id: currentModalItem.id,
            name: currentModalItem.name,
            price: priceNum,
            qty: currentModalQty
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

function clearCart() {
    if (confirm('Are you sure you want to clear your order?')) {
        cart = [];
        saveCart();
    }
}

function promptCustomerBill() {
    if (cart.length === 0) {
        alert('Your order list is empty.');
        return;
    }
    let summary = "📋 TAZA BREW CAFE ORDER:\\n\\n";
    let grandTotal = 0;
    cart.forEach(i => {
        const sub = i.price * i.qty;
        grandTotal += sub;
        summary += `• ${i.qty}x ${i.name} - ${formatPrice(sub)}\\n`;
    });
    summary += `\\nTOTAL: ${formatPrice(grandTotal)}\\n\\nPlease present this to the cashier/counter to place your order. Thank you!`;
    alert(summary);
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
    modalPrice.textContent = item.price;
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
    if (!modal.classList.contains('active')) return;
    
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
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    modalContent.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipeGesture();
    }, { passive: true });
}

function handleSwipeGesture() {
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;

    if (Math.abs(diffX) > 60 && Math.abs(diffX) > Math.abs(diffY)) {
        closeModal();
    } else if (diffY > 80 && modalContent.scrollTop <= 0) {
        closeModal();
    }
}

// Initialize Cart UI on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
});
updateCartUI();


