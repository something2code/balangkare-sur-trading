<?php
require __DIR__ . '/../includes/bootstrap.php';
$farmer = require_login('farmer');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$q = $pdo->prepare('SELECT * FROM products WHERE id=? AND farmer_id=?');
$q->execute([$id ?: 0, $farmer['id']]);
$product = $q->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="section"><div class="empty-state">That listing was not found. <a href="/farmer/dashboard.php">Return to your dashboard</a>.</div></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$errors = [];
$categories = ['Rice', 'Grains', 'Seeds', 'Tools', 'Fertilizer', 'Vegetables', 'Other'];
$newImagePath = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $field = static fn(string $name, string $default = ''): string => is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : $default;
    $name = $field('name');
    $category = $field('category', 'Other');
    $description = $field('description');
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $unit = $field('unit');
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $location = $field('location');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 140) $errors[] = 'Product name must be 2 to 140 characters.';
    if (!in_array($category, $categories, true)) $errors[] = 'Choose a product category.';
    if ($price === false || $price <= 0 || $price > 99999999) $errors[] = 'Enter a valid price above zero.';
    if ($quantity === false || $quantity < 0 || $quantity > 99999999) $errors[] = 'Enter a valid available amount (zero marks the listing sold out).';
    if (mb_strlen($unit) < 1 || mb_strlen($unit) > 30) $errors[] = 'Enter a short unit such as kg, sack, or bag.';
    if (mb_strlen($location) < 2 || mb_strlen($location) > 120) $errors[] = 'Enter a location.';
    if (mb_strlen($description) > 3000) $errors[] = 'Description must be 3,000 characters or less.';

    $upload = $_FILES['image'] ?? null;
    if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $extension = image_upload_extension($upload, 3 * 1024 * 1024);
            if (!$errors) {
                $dir = __DIR__ . '/../uploads';
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    $errors[] = 'Could not create the uploads folder.';
                } else {
                    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                    if (move_uploaded_file($upload['tmp_name'], $dir . '/' . $filename)) {
                        $newImagePath = '/uploads/' . $filename;
                    } else {
                        $errors[] = 'Could not save the photo. Check the uploads folder permissions.';
                    }
                }
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $q = $pdo->prepare("UPDATE products SET name=?,category=?,description=?,image_path=?,price=?,unit=?,quantity=?,location=?,status=CASE WHEN ?<=0 THEN 'sold_out' WHEN status='sold_out' THEN 'active' ELSE status END WHERE id=? AND farmer_id=?");
            $q->execute([
                $name, $category, $description !== '' ? $description : null,
                $newImagePath ?? $product['image_path'], $price, $unit, $quantity,
                $location, $quantity, $product['id'], $farmer['id'],
            ]);
            flash('Product listing updated.');
            header('Location: /farmer/dashboard.php');
            exit;
        } catch (Throwable $e) {
            if ($newImagePath !== null) {
                $savedImage = __DIR__ . '/..' . $newImagePath;
                if (is_file($savedImage)) unlink($savedImage);
            }
            $errors[] = 'Could not update the product. Please try again.';
        }
    }
}

$value = static fn(string $key): string => e(is_string($_POST[$key] ?? null) ? $_POST[$key] : (string)$product[$key]);
$pageTitle = 'Edit product';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-layout"><section class="auth-card">
  <span class="eyebrow" style="color:var(--green)">Farmer workspace</span>
  <h1>Edit product</h1>
  <p>Update the listing details buyers see. Use the dashboard to add or remove stock.</p>
  <?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>
  <?php if (!empty($product['image_path'])): ?><img class="edit-product-photo" src="<?= e($product['image_path']) ?>" alt="Current photo for <?= e($product['name']) ?>"><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="field"><label for="name">Product name</label><input id="name" name="name" maxlength="140" value="<?= $value('name') ?>" required></div>
    <div class="field"><label for="category">Category</label><select name="category" id="category"><?php foreach ($categories as $categoryOption): ?><option value="<?= e($categoryOption) ?>" <?= (($_POST['category'] ?? $product['category']) === $categoryOption) ? 'selected' : '' ?>><?= e($categoryOption) ?></option><?php endforeach; ?></select></div>
    <div class="form-grid"><div class="field"><label for="price">Price per unit (₱)</label><input id="price" name="price" type="number" min="0.01" max="99999999" step="0.01" value="<?= $value('price') ?>" required></div><div class="field"><label for="unit">Unit</label><input id="unit" name="unit" maxlength="30" value="<?= $value('unit') ?>" required></div></div>
    <div class="field"><label for="quantity">Available quantity (in that unit)</label><input id="quantity" name="quantity" type="number" min="0" max="99999999" step="0.01" value="<?= $value('quantity') ?>" required><div class="helper">Setting this to zero marks the listing sold out.</div></div>
    <div class="field"><label for="location">Farm / pickup location</label><input id="location" name="location" maxlength="120" value="<?= $value('location') ?>" required></div>
    <div class="field"><label for="image">Replace product photo (optional)</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><div class="helper">JPG, PNG, or WebP · up to 3 MB</div></div>
    <div class="field"><label for="description">Description (optional)</label><textarea id="description" name="description" maxlength="3000"><?= $value('description') ?></textarea></div>
    <button class="button" type="submit">Save changes</button><a class="button button-quiet" style="width:100%;margin-top:9px" href="/farmer/dashboard.php">Cancel</a>
  </form>
</section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
