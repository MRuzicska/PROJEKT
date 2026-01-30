<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$cat = get_int('category_id', 0);

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
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
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
  <input name="search" placeholder="Keresés..." value="<?=h($search)?>">
  <select name="category_id">
    <option value="0">Összes kategória</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?=$c['id']?>" <?=$cat===(int)$c['id']?'selected':''?>><?=h($c['name'])?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Szűrés</button>
</form>

<hr>

<?php foreach ($products as $p): ?>
  <div style="margin-bottom:14px;">
    <strong><?=h($p['brand'])?> – <?=h($p['name'])?></strong>
    (<?=h($p['category_name'])?>) <br>
    Ár: <?= (int)$p['price'] ?> Ft | Készlet: <?= (int)$p['stock'] ?><br>
    <a href="product.php?id=<?= (int)$p['id'] ?>">Részletek</a>
    <?php if (is_logged_in()): ?>
      <?php if ((int)$p['stock'] > 0): ?>
        <form method="post" action="cart.php" style="display:inline;">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?=$p['id']?>">
          <input type="number" name="qty" value="1" min="1" max="<?=$p['stock']?>" style="width:60px;">
          <button type="submit">Kosárba</button>
        </form>
      <?php else: ?>
        <em>Elfogyott</em>
      <?php endif; ?>
    <?php else: ?>
      <em>(Kosárhoz jelentkezz be)</em>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

</body>
</html>
