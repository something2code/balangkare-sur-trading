<?php
$artStyle = $artStyle ?? '';
$fallback = match ($p['category']) {
    'Rice' => '🌾', 'Grains' => '🌽', 'Seeds' => '🌱', 'Fertilizer' => '🌿', default => '🧺'
};
?>
<div class="product-art"<?= $artStyle !== '' ? ' style="' . e($artStyle) . '"' : '' ?>><?php if (!empty($p['image_path'])): ?><img class="product-image" src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>"><?php else: ?><?= $fallback ?><?php endif; ?></div>
