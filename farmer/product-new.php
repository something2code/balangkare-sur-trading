<?php
require __DIR__ . '/../includes/bootstrap.php';
$farmer = require_login('farmer');
$errors = [];
$postedString = static fn(string $key, string $default = ''): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : $default;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $field = static fn(string $name, string $default = ''): string => is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : $default;
    $name = $field('name');
    $category = $field('category', 'Other');
    $description = $field('description');
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $unit = $field('unit');
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    $location = $field('location', 'Balangkare, Nueva Ecija');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 140) $errors[] = 'Product name must be 2 to 140 characters.';
    if (!in_array($category, ['Rice', 'Grains', 'Seeds', 'Tools', 'Fertilizer', 'Vegetables', 'Other'], true)) $errors[] = 'Choose a product category.';
    if ($price === false || $price <= 0 || $price > 99999999) $errors[] = 'Enter a valid price above zero.';
    if ($quantity === false || $quantity <= 0 || $quantity > 99999999) $errors[] = 'Enter an amount available above zero.';
    if (mb_strlen($unit) < 1 || mb_strlen($unit) > 30) $errors[] = 'Enter a short unit such as kg, sack, or bag.';
    if (mb_strlen($location) < 2 || mb_strlen($location) > 120) $errors[] = 'Enter a location.';
    if (mb_strlen($description) > 3000) $errors[] = 'Description must be 3,000 characters or less.';

    $imagePath = null;
    $upload = $_FILES['image'] ?? null;
    if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $extension = image_upload_extension($upload, 3 * 1024 * 1024);
            if (!$errors) {
                $directory = __DIR__ . '/../uploads';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    $errors[] = 'Could not prepare the product photo folder.';
                } else {
                    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                    if (move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
                        $imagePath = '/uploads/' . $filename;
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
        $q = $pdo->prepare('INSERT INTO products(farmer_id,name,category,description,image_path,price,unit,quantity,location) VALUES(?,?,?,?,?,?,?,?,?)');
        $q->execute([$farmer['id'], $name, $category, $description !== '' ? $description : null, $imagePath, $price, $unit, $quantity, $location]);
        flash('Your product is now listed in the market.');
        header('Location: /farmer/dashboard.php');
        exit;
    }
}

$pageTitle = 'Add a product';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-layout"><section class="auth-card"><span class="eyebrow" style="color:var(--green)">Farmer workspace</span><h1>List a product</h1><p>Clear units and accurate stock help buyers know what to expect.</p>
<?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="field"><label for="name">Product name</label><input id="name" name="name" maxlength="140" placeholder="Example: Freshly milled rice" value="<?= e($postedString('name')) ?>" required></div>
  <div class="field"><label for="category">Category</label><select name="category" id="category"><?php foreach (['Rice', 'Grains', 'Seeds', 'Tools', 'Fertilizer', 'Vegetables', 'Other'] as $c): ?><option value="<?= e($c) ?>" <?= ($_POST['category'] ?? 'Other') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
  <div class="form-grid"><div class="field"><label for="price">Price per unit (₱)</label><input id="price" name="price" type="number" min="0.01" step="0.01" value="<?= e($postedString('price')) ?>" required></div><div class="field"><label for="unit">Unit</label><input id="unit" name="unit" maxlength="30" placeholder="kg, sack, bag" value="<?= e($postedString('unit', 'kg')) ?>" required></div></div>
  <div class="field"><label for="quantity">Available quantity (in that unit)</label><input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="<?= e($postedString('quantity')) ?>" required></div>
  <div class="field"><label for="location">Farm / pickup location</label><input id="location" name="location" maxlength="120" value="<?= e($postedString('location', 'Balangkare, Nueva Ecija')) ?>" required></div>
  <div class="field"><label for="image">Product photo (optional)</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><div class="helper">JPG, PNG, or WebP · up to 3 MB</div></div>
  <div class="field"><label for="description">Description (optional)</label><textarea id="description" name="description" maxlength="3000" placeholder="Variety, harvest date, or other useful details"><?= e($postedString('description')) ?></textarea></div>
  <button class="button">Publish listing</button><a class="button button-quiet" style="width:100%;margin-top:9px" href="/farmer/dashboard.php">Cancel</a>
</form><p class="helper">Upload only a photo of the listed product. The server accepts JPG, PNG, or WebP images up to 20 megapixels.</p></section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
