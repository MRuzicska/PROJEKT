<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$stmt = $pdo->query(
  "SELECT p.id, p.brand, p.name, p.price, p.stock, p.image_url, c.name AS category_name
   FROM products p
   LEFT JOIN categories c ON c.id = p.category_id
   ORDER BY p.id DESC"
);
$products = $stmt->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Admin - Termékek</title>
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="navbar">
  <div class="logo">
    <a href="../index.php">Parfum p'Dm Admin</a>
  </div>

  <nav class="nav-links">
    <a href="index.php">Admin menü</a>
    <a href="orders.php">Rendelések</a>
  </nav>

  <div class="nav-actions">
    <a href="product_edit.php">+ Új termék</a>
    <a href="../index.php">Webshop</a>
  </div>
</header>

<main class="admin-page">
  <h1 class="admin-title">Admin – Termékek</h1>

  <section class="admin-card">
    <h2>Terméklista</h2>

    <div class="table-wrap">
      <table class="admin-table">
        <tr>
          <th>ID</th>
          <th>Kép</th>
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
            <td>
              <?php if (!empty($p['image_url'])): ?>
                <img src="../<?= h((string)$p['image_url']) ?>" alt="<?= h((string)$p['name']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:10px;">
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= h((string)$p['brand']) ?></td>
            <td><?= h((string)$p['name']) ?></td>
            <td><?= h((string)($p['category_name'] ?? '-')) ?></td>
            <td><?= (int)$p['price'] ?> Ft</td>
            <td><?= (int)$p['stock'] ?></td>
            <td>
              <div class="button-row">
                <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn-edit">Szerkesztés</a>

                <form action="product_delete.php" method="post" style="display:inline"
                      onsubmit="return confirm('Biztosan törlöd ezt a terméket?');">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn-danger">Törlés</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </section>
</main>

</body>
</html>