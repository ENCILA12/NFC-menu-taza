<?php
$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true) ?: ['mode' => 'static'];

if ($config['mode'] === 'interactive') {
    include 'data.php';
} else {
    $gallery_items = json_decode(file_get_contents(__DIR__ . '/gallery.json'), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taza Brew Cafe | Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="hero">
        <div class="hero-content">
            <h1 class="brand-name">TAZA BREW CAFE</h1>
            <p class="tagline">Savor the warmth in every cup.</p>
        </div>
    </header>

    <main class="container">
        <?php if ($config['mode'] === 'static'): ?>
            
            <!-- MODE A: Static Scrollable Image Gallery -->
            <div class="gallery-container">
                <?php if(count($gallery_items) === 0): ?>
                    <p style="text-align:center; padding: 2rem;">Menu is currently empty. Please upload menu pictures in the admin panel.</p>
                <?php endif; ?>
                
                <?php foreach($gallery_items as $img): ?>
                    <div class="gallery-item">
                        <img src="<?= $img ?>" alt="Menu Page" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            
            <!-- MODE B: Interactive Grid -->
            <div class="category-filters">
                <?php foreach($categories as $index => $category): ?>
                    <button class="filter-btn <?= $index === 0 ? 'active' : '' ?>" data-filter="<?= strtolower($category) ?>">
                        <?= $category ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="menu-grid">
                <?php foreach($menu_items as $item): ?>
                    <div class="menu-card <?= (isset($item['is_available']) && $item['is_available'] === false) ? 'not-available' : '' ?>" data-category="<?= strtolower($item['category']) ?>" onclick="openModal(<?= $item['id'] ?>)">
                        <div class="card-image">
                            <?php if(empty($item['image']) || strpos($item['image'], 'default.png') !== false): ?>
                                <div class="no-pic-placeholder">NO PIC</div>
                            <?php else: ?>
                                <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" loading="lazy">
                            <?php endif; ?>
                            <?php if(isset($item['is_available']) && $item['is_available'] === false): ?>
                                <div class="unavailable-badge">Not Available</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="item-price"><?= htmlspecialchars($item['price']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </main>

    <!-- App Footer with Location & Developer Links -->
    <footer class="app-footer">
        <div class="footer-links">
            <a href="https://brewfinders.tazabrew.com/cafe.php?id=1" target="_blank" rel="noopener noreferrer" class="footer-btn location-btn">
                <i class="ph ph-map-pin"></i> View Location
            </a>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=iganpulencila01@gmail.com&su=Inquiry+-+NFC+QR+Digital+Menu+Project&body=Hi+Dev,+interesado+po+akong+magpagawa+ng+system+tulad+nito." target="_blank" rel="noopener noreferrer" class="footer-btn dev-btn">
                <i class="ph ph-envelope-simple"></i> Contact Developer
            </a>
        </div>
        <p class="footer-copy">© <?= date('Y') ?> Taza Brew Cafe • Powered by Custom QR/NFC Menu</p>
    </footer>


    <?php if ($config['mode'] === 'interactive'): ?>
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal()"><i class="ph ph-x"></i></button>
            <div class="modal-image-container">
                <img id="modalImage" src="" alt="Item Image">
                <div id="modalNoPic" class="no-pic-placeholder" style="display: none;">NO PIC</div>
            </div>
            <div class="modal-details">
                <div class="modal-header-info"><h2 id="modalName"></h2><p id="modalPrice"></p></div>
                <div id="modalTags" class="tags-container"></div>
                <p id="modalDescription" class="modal-desc"></p>
                
                <div class="modal-order-action">
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" onclick="adjustModalQty(-1)"><i class="ph ph-minus"></i></button>
                        <span id="modalQtyDisplay">1</span>
                        <button type="button" class="qty-btn" onclick="adjustModalQty(1)"><i class="ph ph-plus"></i></button>
                    </div>
                    <button id="modalAddToCartBtn" class="add-to-cart-btn" onclick="addCurrentItemToCart()">
                        <i class="ph ph-shopping-cart-simple"></i> Add to Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Cart Bar for Mobile & Desktop -->
    <div id="floatingCart" class="floating-cart" onclick="openCartModal()">
        <div class="floating-cart-info">
            <span class="cart-badge" id="cartItemCount">0</span>
            <span class="cart-label">View Order</span>
        </div>
        <div class="floating-cart-total" id="cartBarTotal">₱0.00</div>
    </div>

    <!-- Cart Drawer / Modal -->
    <div id="cartModal" class="modal">
        <div class="modal-content cart-modal-content">
            <div class="cart-header">
                <h2><i class="ph ph-receipt"></i> Your Order Summary</h2>
                <button class="close-modal-btn" onclick="closeCartModal()"><i class="ph ph-x"></i></button>
            </div>
            <div class="cart-items-container" id="cartItemsList">
                <!-- Dynamically populated -->
            </div>
            <div class="cart-footer">
                <div class="cart-summary-row">
                    <span>Total Amount:</span>
                    <strong class="cart-grand-total" id="cartGrandTotal">₱0.00</strong>
                </div>
                <div class="cart-action-buttons">
                    <button class="clear-cart-btn" onclick="clearCart()"><i class="ph ph-trash"></i> Clear</button>
                    <button class="checkout-prompt-btn" onclick="promptCustomerBill()"><i class="ph ph-check-circle"></i> Show to Counter</button>
                </div>
            </div>
        </div>
    </div>

    <script>const menuData = <?= json_encode($menu_items) ?>;</script>
    <script src="assets/js/script.js"></script>
    <?php endif; ?>
</body>
</html>
