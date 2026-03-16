<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$cat = get_int('category_id', 0);
$brand = isset($_GET['brand']) ? trim((string)$_GET['brand']) : '';
$price_range = isset($_GET['price_range']) ? trim((string)$_GET['price_range']) : '';

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
  $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}
if ($cat > 0) {
  $sql .= " AND p.category_id = ?";
  $params[] = $cat;
}
if ($brand !== '') {
  $sql .= " AND p.brand = ?";
  $params[] = $brand;
}
if ($price_range !== '') {
  if ($price_range === '0-10000') {
    $sql .= " AND p.price BETWEEN 0 AND 10000";
  } elseif ($price_range === '10000-20000') {
    $sql .= " AND p.price BETWEEN 10000 AND 20000";
  } elseif ($price_range === '20000-30000') {
    $sql .= " AND p.price BETWEEN 20000 AND 30000";
  } elseif ($price_range === '30000+') {
    $sql .= " AND p.price >= 30000";
  }
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand")->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Parfüm webshop</title>
<link rel="stylesheet" href="css/style.css"></head>
<body>
<h1>Parfüm webshop</h1>

<p>
<?php if (is_logged_in()): ?>
  Szia, <?=h((string)$_SESSION['user_name'])?>! |
  <a href="cart.php">Kosár</a> |
  <a href="orders.php">Rendeléseim</a> |
  <?php if (is_admin()): ?><a href="admin/index.php">Admin</a> |<?php endif; ?>
  <a href="logout.php">Kilépés</a>
<?php else: ?>
  <a href="login.php">Belépés</a> | <a href="register.php">Regisztráció</a>
<?php endif; ?>
</p>

<form method="get">
  <input name="search" placeholder="Keresés név vagy márka szerint..." value="<?=h($search)?>">
  <select name="category_id">
    <option value="0">Összes kategória</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?=$c['id']?>" <?=$cat===(int)$c['id']?'selected':''?>><?=h($c['name'])?></option>
    <?php endforeach; ?>
  </select>
  <select name="brand">
    <option value="">Összes márka</option>
    <?php foreach ($brands as $b): ?>
      <option value="<?=h($b['brand'])?>" <?=$brand===$b['brand']?'selected':''?>><?=h($b['brand'])?></option>
    <?php endforeach; ?>
  </select>
  <select name="price_range">
    <option value="">Összes ár</option>
    <option value="0-10000" <?=$price_range==='0-10000'?'selected':''?>>0 - 10 000 Ft</option>
    <option value="10000-20000" <?=$price_range==='10000-20000'?'selected':''?>>10 000 - 20 000 Ft</option>
    <option value="20000-30000" <?=$price_range==='20000-30000'?'selected':''?>>20 000 - 30 000 Ft</option>
    <option value="30000+" <?=$price_range==='30000+'?'selected':''?>>30 000 Ft felett</option>
  </select>
  <button type="submit">Szűrés</button>
</form>

<hr>

<div class="product-grid">
<?php foreach ($products as $p): ?>
  <div class="product-card">
    <div class="product-image">
      <?php if (!empty($p['image_url'])): ?>
        <img src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>">
      <?php endif; ?>
    </div>
    <div class="product-name"><?=h($p['name'])?></div>
    <div class="product-brand"><?=h($p['brand'])?></div>
    <div class="product-category">(<?=h($p['category_name'])?>)</div>
    <div class="product-price">Ár: <?= (int)$p['price'] ?> Ft</div>
    <div class="product-stock">Készlet: <?= (int)$p['stock'] ?></div>
    <div class="product-links">
      <a href="product.php?id=<?= (int)$p['id'] ?>">Részletek</a>
    </div>
    <?php if (is_logged_in()): ?>
      <?php if ((int)$p['stock'] > 0): ?>
        <form class="add-to-cart-form" method="post" action="cart.php">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?=$p['id']?>">
          <input type="number" name="qty" value="1" min="1" max="<?=$p['stock']?>">
          <button type="submit">Kosárba</button>
        </form>
      <?php else: ?>
        <div class="sold-out">Elfogyott</div>
      <?php endif; ?>
    <?php else: ?>
      <div class="sold-out">(Kosárhoz jelentkezz be)</div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

</body>
</html>
