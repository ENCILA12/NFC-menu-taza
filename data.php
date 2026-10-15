<?php
// Menu data loader for Taza Brew Cafe

$json_data = file_get_contents(__DIR__ . '/menu.json');
$menu_items = json_decode($json_data, true);

// Extract unique categories (always keeping 'All' as the first one)
$categories = ['All'];
foreach($menu_items as $item) {
    if(!in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}
?>
