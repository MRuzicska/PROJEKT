<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

require_login();
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = post('action');

  if ($action === 'update') {
    $id = (int) post('item_id');
    $qty = max(1, (int) post('qty', '1'));

    $st = $pdo->prepare("
      SELECT ci.product_id, p.stock
      FROM cart_items ci
      JOIN products p ON p.id = ci.product_id
      WHERE ci.id=? AND ci.user_id=?
    ");
    $st->execute([$id, $userId]);
    $row = $st->fetch();

    if (!$row) die("Hibás kosár tétel.");
    if ($qty > (int)$row['stock']) die("Nincs elég készlet.");

    $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?")
      ->execute([$qty, $id, $userId]);

    header("Location: cart.php");
    exit;
  }

  if ($action === 'delete') {
    $id = (int) post('item_id');
    $pdo->prepare("DELETE FROM cart_items WHERE id=? AND user_id=?")
      ->execute([$id, $userId]);

    header("Location: cart.php");
    exit;
  }
}

$stmt = $pdo->prepare("
  SELECT ci.id, ci.quantity, p.name, p.brand, p.price, p.stock
  FROM cart_items ci
  JOIN products p ON p.id = ci.product_id
  WHERE ci.user_id = ?
  ORDER BY ci.id DESC
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $it) {
  $total += (int)$it['price'] * (int)$it['quantity'];
}
?>

<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Kosár</title>
  <link rel="stylesheet" href="css/cart.css">
</head>

<body>

<header class="navbar">
  <div class="logo">
    <img src="images/logo-placeholder.png" alt="">
    <a href="index.php">Parfum p'Dm</a>
  </div>

  <nav class="nav-links">
    <a href="products.php">Összes parfüm</a>
    <a href="products.php?category_id=2">Férfi</a>
    <a href="products.php?category_id=1">Női</a>
    <a href="products.php?category_id=3">Unisex</a>
  </nav>

  <div class="nav-actions">
    <div class="profile-wrapper">
      <button onclick="toggleProfile()">👤 Profil</button>
      <div class="dropdown profile-dropdown">
        <p><?= h($_SESSION['username'] ?? 'Felhasználó') ?></p>
        <a href="logout.php" class="btn-small">Kijelentkezés</a>
      </div>
    </div>
  </div>
</header>

<h1>Kosár</h1>

<?php if (!$items): ?>
  <p>A kosár üres.</p>
<?php else: ?>

<table>
  <tr>
    <th>Termék</th>
    <th>Ár</th>
    <th>Mennyiség</th>
    <th>Részösszeg</th>
    <th>Művelet</th>
  </tr>

  <?php foreach ($items as $it): ?>
    <tr>
      <td><?= h($it['brand']) ?> – <?= h($it['name']) ?></td>
      <td><?= (int)$it['price'] ?> Ft</td>

      <td>
        <form method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
          <input type="number" name="qty" min="1" max="<?= $it['stock'] ?>" value="<?= $it['quantity'] ?>">
          <button>Mentés</button>
        </form>
      </td>

      <td><?= (int)$it['price'] * (int)$it['quantity'] ?> Ft</td>

      <td>
        <form method="post" onsubmit="return confirm('Törlöd?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
          <button>Törlés</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<p><strong>Összesen: <?= $total ?> Ft</strong></p>

<p>
  <a href="index.php" class="btnn">← Vissza</a>
  <a href="checkout.php" class="btn">Tovább →</a>
</p>

<?php endif; ?>

<script>
function toggleProfile() {
  document.querySelector('.profile-dropdown').classList.toggle('show');
}
</script>

</body>
</html>