<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

// termékek listája kategória névvel
$stmt = $pdo->query(
  "SELECT p.id, p.brand, p.name, p.price, p.stock, c.name AS category_name
   FROM products p
   LEFT JOIN categories c ON c.id = p.category_id
   ORDER BY p.id DESC"
);
$products = $stmt->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Admin - Termékek</title></head>
<body>
<h1>Admin – Termékek</h1>
<p><a href="index.php">← Admin menü</a></p>

<p>
  <a href="product_edit.php" style="display:inline-block;padding:6px 10px;border:1px solid #333;text-decoration:none;">
    + Új termék
  </a>
</p>

<table border="1" cellpadding="6">
  <tr>
    <th>ID</th>
    <th>Márka</th>
    <th>Név</th>
    <th>Kategória</th>
    <th>Ár</th>
    <th>Készlet</th>
    <th>Műveletek</th>
  </tr>

  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= (int)$p['id'] ?></td>
      <td><?= h((string)$p['brand']) ?></td>
      <td><?= h((string)$p['name']) ?></td>
      <td><?= h((string)($p['category_name'] ?? '-')) ?></td>
      <td><?= (int)$p['price'] ?> Ft</td>
      <td><?= (int)$p['stock'] ?></td>
      <td>
        <a href="product_edit.php?id=<?= (int)$p['id'] ?>">Szerkesztés</a>

        <form action="product_delete.php" method="post" style="display:inline"
              onsubmit="return confirm('Biztosan törlöd ezt a terméket?');">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit">Törlés</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

</body>
</html>
