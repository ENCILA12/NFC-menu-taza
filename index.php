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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time() ?>">
    <style>
        :root {
            <?php if (!empty($config['body_bg_color'])): ?>
                --bg-color: <?= htmlspecialchars($config['body_bg_color']) ?>;
            <?php endif; ?>
            <?php if (!empty($config['hero_bg_color'])): ?>
                --primary-color: <?= htmlspecialchars($config['hero_bg_color']) ?>;
            <?php endif; ?>
        }

        body {
            background-color: var(--bg-color);
            <?php if (!empty($config['body_bg_image']) && file_exists(__DIR__ . '/' . $config['body_bg_image'])): 
                $fit = $config['body_bg_fit'] ?? 'cover';
            ?>
                background-image: url('<?= htmlspecialchars($config['body_bg_image']) ?>?v=<?= filemtime(__DIR__ . '/' . $config['body_bg_image']) ?>');
                <?php if ($fit === 'contain'): ?>
                    background-size: contain;
                    background-position: center top;
                    background-repeat: no-repeat;
                    background-attachment: fixed;
                <?php elseif ($fit === 'repeat'): ?>
                    background-size: auto;
                    background-position: top left;
                    background-repeat: repeat;
                    background-attachment: fixed;
                <?php else: /* cover / auto-fit */ ?>
                    background-size: 100% auto; /* Auto fit width seamlessly */
                    background-position: center top;
                    background-repeat: repeat-y;
                    background-attachment: scroll;
                <?php endif; ?>
            <?php endif; ?>
            min-height: 100vh;
        }

        .hero {
            <?php if (!empty($config['hero_bg_image']) && file_exists(__DIR__ . '/' . $config['hero_bg_image'])): ?>
                background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65)), url('<?= htmlspecialchars($config['hero_bg_image']) ?>?v=<?= filemtime(__DIR__ . '/' . $config['hero_bg_image']) ?>');
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
            <?php elseif (!empty($config['hero_bg_color'])): ?>
                background: linear-gradient(135deg, <?= htmlspecialchars($config['hero_bg_color']) ?>, #3e261b);
            <?php endif; ?>
        }

        .app-footer {
            <?php if (!empty($config['footer_bg_color'])): ?>
                background-color: <?= htmlspecialchars($config['footer_bg_color']) ?>;
            <?php endif; ?>
            <?php if (!empty($config['footer_bg_image']) && file_exists(__DIR__ . '/' . $config['footer_bg_image'])): ?>
                background: linear-gradient(rgba(243, 237, 232, 0.85), rgba(243, 237, 232, 0.95)), url('<?= htmlspecialchars($config['footer_bg_image']) ?>?v=<?= filemtime(__DIR__ . '/' . $config['footer_bg_image']) ?>');
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
            <?php endif; ?>
        }
    </style>
</head>
<body>
    <header class="hero">
        <div class="hero-content">
            <h1 class="brand-name">🎃 TAZA BREW CAFE 🦇</h1>
            <p class="tagline">Savor the spookiness & warmth in every cup ☕👻</p>
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
            
            <!-- Search Bar -->
            <div class="search-bar-container">
                <i class="ph ph-magnifying-glass search-icon"></i>
                <input type="text" id="menuSearchInput" placeholder="Search drinks, dishes, specials..." autocomplete="off">
                <button type="button" id="clearSearchBtn" class="clear-search-btn" style="display:none;"><i class="ph ph-x-circle"></i></button>
            </div>

            <div class="category-filters">
                <?php foreach($categories as $index => $category): ?>
                    <button class="filter-btn <?= $index === 0 ? 'active' : '' ?>" data-filter="<?= strtolower($category) ?>">
                        <?= $category ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="menu-grid" id="menuGridContainer">
                <?php foreach($menu_items as $item): ?>
                    <div class="menu-card <?= (isset($item['is_available']) && $item['is_available'] === false) ? 'not-available' : '' ?>" 
                         data-category="<?= strtolower($item['category']) ?>" 
                         data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>"
                         data-description="<?= strtolower(htmlspecialchars($item['description'] ?? '')) ?>"
                         onclick="openModal(<?= $item['id'] ?>)">
                        <div class="pumpkin-card-decoration" title="Happy Halloween!">🎃</div>
                        <div class="card-image">
                            <?php if(empty($item['image']) || strpos($item['image'], 'default.png') !== false || !file_exists(__DIR__ . '/' . $item['image'])): ?>
                                <div class="no-pic-placeholder">NO PIC</div>
                            <?php else: ?>
                                <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                            <?php endif; ?>
                            <?php if(isset($item['is_available']) && $item['is_available'] === false): ?>
                                <div class="unavailable-badge">Not Available</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="item-price"><?= htmlspecialchars($item['price']) ?></p>
                            <span class="view-item-btn"><i class="ph ph-plus-circle"></i> Add to Order</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="noResultsMessage" class="no-results-message" style="display:none;">
                <i class="ph ph-magnifying-glass" style="font-size:2.5rem; opacity:0.5; display:block; margin-bottom:8px;"></i>
                No items match your search.
            </div>

        <?php endif; ?>
    </main>

    <!-- App Footer with Location & Developer Links -->
    <footer class="app-footer">
        <div class="footer-links">
            <a href="https://brewfinders.tazabrew.com/cafe.php?id=1" target="_blank" rel="noopener noreferrer" class="footer-btn location-btn">
                <i class="ph ph-map-pin"></i> View Location
            </a>
            <a href="mailto:iganpulencila01@gmail.com?subject=Inquiry%20-%20NFC%20QR%20Digital%20Menu%20Project&body=Hi%20Dev,%20interesado%20po%20akong%20magpagawa%20ng%20system%20tulad%20nito." class="footer-btn dev-btn">
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
                <div class="modal-header-info">
                    <h2 id="modalName"></h2>
                    <p id="modalPrice" class="item-price"></p>
                </div>
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

    <!-- Floating Cart Bar for Mobile & Desktop (Without Total) -->
    <div id="floatingCart" class="floating-cart" onclick="openCartModal()">
        <div class="floating-cart-info">
            <span class="cart-badge" id="cartItemCount">0</span>
            <span class="cart-label">View My Order</span>
        </div>
        <div class="floating-cart-action">
            <span class="cart-view-text">Review List <i class="ph ph-arrow-right"></i></span>
        </div>
    </div>

    <!-- Cart Drawer / Modal (Without Prices / Totals) -->
    <div id="cartModal" class="modal">
        <div class="modal-content cart-modal-content">
            <div class="cart-header">
                <h2><i class="ph ph-receipt"></i> Your Order List</h2>
                <button class="close-modal-btn" onclick="closeCartModal()"><i class="ph ph-x"></i></button>
            </div>
            <div class="cart-items-container" id="cartItemsList">
                <!-- Dynamically populated without prices -->
            </div>
            <div class="cart-footer">
                <div class="cart-action-buttons">
                    <button class="clear-cart-btn" onclick="clearCart()"><i class="ph ph-trash"></i> Clear Order</button>
                    <div class="pay-counter-badge"><i class="ph ph-storefront"></i> Pay at the Counter</div>
                </div>
            </div>
        </div>
    </div>

    <script>const menuData = <?= json_encode($menu_items) ?>;</script>
    <script src="assets/js/script.js?v=<?= file_exists(__DIR__ . '/assets/js/script.js') ? filemtime(__DIR__ . '/assets/js/script.js') : time() ?>"></script>
    <?php endif; ?>
</body>
</html>
