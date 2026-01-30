<?php
declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  exit('Hibás termékazonosító.');
}

$stmt = $pdo->prepare(
  "SELECT p.*, c.name AS category_name
   FROM products p
   LEFT JOIN categories c ON c.id = p.category_id
   WHERE p.id = ?"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
  http_response_code(404);
  exit('A termék nem található.');
}

/**
 * Kiszerelések (variantok) betöltése:
 * product_variants: (id, product_id, size_ml, price, stock)
 */
$vs = $pdo->prepare(
  "SELECT id, size_ml, price, stock
   FROM product_variants
   WHERE product_id = ?
   ORDER BY size_ml ASC"
);
$vs->execute([$id]);
$variants = $vs->fetchAll();

// alapértelmezett kiválasztás (első készleten lévő, ha van)
$selectedVariantId = 0;
foreach ($variants as $v) {
  if ((int)$v['stock'] > 0) {
    $selectedVariantId = (int)$v['id'];
    break;
  }
}
if ($selectedVariantId === 0 && $variants) {
  $selectedVariantId = (int)$variants[0]['id'];
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title><?= h($product['brand'] . ' – ' . $product['name']) ?></title>
</head>
<body>

<p><a href="index.php">← Vissza a parfümökhöz</a></p>

<h1><?= h($product['brand']) ?> – <?= h($product['name']) ?></h1>

<?php if (!empty($product['image_url'])): ?>
  <p>
    <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>" style="max-width:320px;">
  </p>
<?php endif; ?>

<ul>
  <li><strong>Kategória:</strong> <?= h($product['category_name'] ?? '-') ?></li>
</ul>

<h2>Kiszerelés</h2>

<?php if (!$variants): ?>
  <p style="color:red;">
    Ehhez a parfümhöz még nincs beállítva kiszerelés (pl. 50 / 100 ml).
    <br>Admin oldalon érdemes felvenni a variánsokat.
  </p>
<?php else: ?>
  <form method="post" action="cart_add.php">
    <fieldset style="border:1px solid #ccc; padding:10px; max-width:520px;">
      <legend>Válassz méretet</legend>

      <?php foreach ($variants as $v): ?>
        <?php
          $vid = (int)$v['id'];
          $disabled = ((int)$v['stock'] <= 0) ? 'disabled' : '';
          $checked = ($vid === $selectedVariantId) ? 'checked' : '';
        ?>
        <label style="display:block; margin:6px 0;">
          <input type="radio" name="variant_id" value="<?= $vid ?>" <?= $checked ?> <?= $disabled ?>>
          <strong><?= (int)$v['size_ml'] ?> ml</strong>
          — <?= (int)$v['price'] ?> Ft
          <?php if ((int)$v['stock'] <= 0): ?>
            <em>(nincs készleten)</em>
          <?php else: ?>
            <small>(készlet: <?= (int)$v['stock'] ?>)</small>
          <?php endif; ?>
        </label>
      <?php endforeach; ?>

      <label style="display:block; margin-top:10px;">
        Darab:
        <input type="number" name="qty" min="1" value="1" style="width:80px;">
      </label>

      <button type="submit" style="margin-top:10px;">Kosárba</button>
    </fieldset>
  </form>
<?php endif; ?>

<h2>Leírás</h2>
<p><?= nl2br(h((string)$product['description'])) ?></p>

</body>
</html>
