<?php
session_start();
require_once __DIR__ . '/db.php'; // <-- nálad lehet config.php / connection.php

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION['user_id'];
$isAdmin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
  http_response_code(400);
  die("Hibás rendelés azonosító.");
}

$allowedStatuses = ['pending','processing','shipped','completed','cancelled'];

/* ADMIN: státusz frissítés */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
  $newStatus = $_POST['new_status'];

  if (!in_array($newStatus, $allowedStatuses, true)) {
    $error = "Érvénytelen státusz.";
  } else {
    $stmt = $pdo->prepare("UPDATE orders SET status = :st WHERE id = :id");
    $stmt->execute([':st' => $newStatus, ':id' => $orderId]);
    header("Location: order_details.php?id=" . $orderId);
    exit;
  }
}

/* Rendelés fejléc lekérés (jogosultság szerint) */
if ($isAdmin) {
  // Admin mindent láthat
  $stmt = $pdo->prepare("
    SELECT o.*, u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.id = :id
    LIMIT 1
  ");
  $stmt->execute([':id' => $orderId]);
} else {
  // User csak a sajátját
  $stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.id = :id AND o.user_id = :uid
    LIMIT 1
  ");
  $stmt->execute([':id' => $orderId, ':uid' => $userId]);
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
  http_response_code(404);
  die("Rendelés nem található (vagy nincs jogosultság).");
}

/* Tételek lekérés */
$stmt = $pdo->prepare("
  SELECT
    oi.qty,
    oi.unit_price,
    p.name AS product_name,
    p.image AS product_image
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = :oid
  ORDER BY p.name
");
$stmt->execute([':oid' => $orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Totál számítás */
$total = 0;
foreach ($items as $it) {
  $total += ((float)$it['unit_price']) * ((int)$it['qty']);
}

function statusLabel($s) {
  return match($s) {
    'pending' => 'Függőben',
    'processing' => 'Feldolgozás alatt',
    'shipped' => 'Kiszállítva',
    'completed' => 'Teljesítve',
    'cancelled' => 'Törölve',
    default => $s
  };
}
function statusClass($s) {
  return match($s) {
    'pending' => 'badge badge-warning',
    'processing' => 'badge badge-info',
    'shipped' => 'badge badge-primary',
    'completed' => 'badge badge-success',
    'cancelled' => 'badge badge-danger',
    default => 'badge badge-secondary'
  };
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Rendelés #<?= (int)$order['id'] ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    .container { max-width: 1100px; margin: 0 auto; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .card { border:1px solid #ddd; border-radius:12px; padding:16px; background:#fff; }
    .row { display:flex; gap:16px; flex-wrap:wrap; }
    .row .card { flex:1; min-width:300px; }
    .muted { color:#666; font-size:13px; }
    .badge { padding:6px 10px; border-radius:999px; font-size:12px; display:inline-block; }
    .badge-warning { background:#fff3cd; }
    .badge-info { background:#d1ecf1; }
    .badge-primary { background:#cce5ff; }
    .badge-success { background:#d4edda; }
    .badge-danger { background:#f8d7da; }
    table { width:100%; border-collapse:collapse; margin-top:12px; }
    th, td { border-bottom:1px solid #eee; padding:10px; text-align:left; vertical-align:middle; }
    th { background:#fafafa; }
    .right { text-align:right; }
    .product { display:flex; align-items:center; gap:12px; }
    .product img { width:56px; height:56px; object-fit:cover; border-radius:10px; border:1px solid #eee; }
    .btn { padding:8px 12px; border-radius:10px; border:1px solid #ddd; background:#f7f7f7; cursor:pointer; }
    .btn-primary { background:#1f6feb; color:#fff; border-color:#1f6feb; }
    select { padding:8px; border-radius:10px; border:1px solid #ddd; }
    .error { background:#f8d7da; padding:10px; border-radius:10px; margin:10px 0; }

    .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #0f2a38;
    padding: 10px 20px;
  }
  
  .navbar .logo {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .navbar .logo img {
    height: 40px;
  }

  .navbar a{
    color: #DDA853;
    text-decoration: none;
    font-size: 20px;
  }
  
  .nav-links a {
    color: #fff;
    margin: 0 10px;
    text-decoration: none;
  }
  
  .nav-actions {
    display: flex;
    gap: 10px;
  }
  
  .nav-actions button {
    background: #DDEB9D;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
  }
  
  /* ===== DROPDOWN ===== */
  .dropdown {
    display: none;
    position: absolute;
    right: 20px;
    top: 60px;
    background: #fff;
    color: #000;
    padding: 15px;
    border-radius: 10px;
    min-width: 250px;
    z-index: 100;
  }
  
  .dropdown.show {
    display: block;
  }
  
  .btn-small {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 10px;
    background: #183B4E;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
  }
  </style>
</head>
<body>

<header class="navbar">
    <div class="logo">
      <a href="index.php">Parfum p'Dm</a>
    </div>
    <nav class="nav-links desktop-menu">
      <a href="products.php">Összes parfüm</a>
      <a href="products.php?category_id=2">Csak férfi</a>
      <a href="products.php?category_id=1">Csak női</a>
      <a href="products.php?category_id=3">Csak unisex</a>
    </nav>


    <div class="nav-actions">
      <div class="cart-wrapper">
        <button onclick="toggleCart()">🛒 Kosár</button>
        <div class="dropdown cart-dropdown">
          <?php require_login();
          $userId = (int) $_SESSION['user_id'];

          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = post('action');

            if ($action === 'add') {
              $pid = (int) post('product_id');
              $qty = max(1, (int) post('qty', '1'));

              // készlet ellenőrzés
              $st = $pdo->prepare("SELECT stock FROM products WHERE id=?");
              $st->execute([$pid]);
              $row = $st->fetch();
              if (!$row) {
                die("Nincs ilyen termék.");
              }
              $stock = (int) $row['stock'];
              if ($qty > $stock) {
                die("Nincs elég készlet.");
              }

              // upsert
              $pdo->prepare(
                "INSERT INTO cart_items (user_id, product_id, quantity)
       VALUES (?, ?, ?)
       ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)"
              )->execute([$userId, $pid, $qty, $stock]);

              header('Location: cart.php');
              exit;
            }

            if ($action === 'update') {
              $id = (int) post('item_id');
              $qty = max(1, (int) post('qty', '1'));

              // készlet check a termékhez
              $st = $pdo->prepare(
                "SELECT ci.product_id, p.stock
       FROM cart_items ci JOIN products p ON p.id=ci.product_id
       WHERE ci.id=? AND ci.user_id=?"
              );
              $st->execute([$id, $userId]);
              $row = $st->fetch();
              if (!$row) {
                die("Nincs ilyen kosár tétel.");
              }
              if ($qty > (int) $row['stock']) {
                die("Nincs elég készlet.");
              }

              $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?")
                ->execute([$qty, $id, $userId]);

              header('Location: cart.php');
              exit;
            }

            if ($action === 'delete') {
              $id = (int) post('item_id');
              $pdo->prepare("DELETE FROM cart_items WHERE id=? AND user_id=?")
                ->execute([$id, $userId]);
              header('Location: cart.php');
              exit;
            }
          }

          $stmt = $pdo->prepare(
            "SELECT ci.id, ci.quantity, p.name, p.brand, p.price, p.stock
   FROM cart_items ci
   JOIN products p ON p.id = ci.product_id
   WHERE ci.user_id = ?
   ORDER BY ci.id DESC"
          );
          $stmt->execute([$userId]);
          $items = $stmt->fetchAll();

          $total = 0;
          foreach ($items as $it)
            $total += (int) $it['price'] * (int) $it['quantity'];
          ?>



          <?php if (!$items): ?>
            <p>A kosár üres.</p>
          <?php else: ?>
            <table border="1" cellpadding="6">
              <tr>
                <th>Termék</th>
                <th>Ár</th>
                <th>Mennyiség</th>
                <th>Részösszeg</th>
                <th>Művelet</th>
              </tr>
              <?php foreach ($items as $it):
                $sub = (int) $it['price'] * (int) $it['quantity'];
                ?>
                <tr>
                  <td><?= h($it['brand']) ?> – <?= h($it['name']) ?></td>
                  <td><?= (int) $it['price'] ?> Ft</td>
                  <td>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                      <input type="number" name="qty" min="1" max="<?= $it['stock'] ?>" value="<?= $it['quantity'] ?>"
                        style="width:70px;">
                      <button type="submit">Mentés</button>
                    </form>
                  </td>
                  <td><?= $sub ?> Ft</td>
                  <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Törlöd?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                      <button type="submit">Törlés</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>

            <p><strong>Összesen: <?= $total ?> Ft</strong></p>
            <p><a href="checkout.php">Tovább a rendeléshez →</a></p>
          <?php endif; ?>
          <a href="cart.php" class="btn-small">Kosár megnyitása</a>
        </div>
      </div>
      <div class="profile-wrapper">
        <button onclick="toggleProfile()">👤 Profil</button>
        <div class="dropdown profile-dropdown">
          <?php if (is_logged_in()): ?>
            <p>Üdvözlünk, <?= h($_SESSION['username'] ?? 'Felhasználó') ?>!</p>
            <?php if (is_admin()): ?>
              <a href="admin.php" class="btn-small">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-small">Kijelentkezés</a>
          <?php else: ?>
            <a href="login.php" class="btn-small">Bejelentkezés</a>
            <a href="register.php" class="btn-small">Regisztráció</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>
  
<div class="container">

  <div class="topbar">
    <div>
      <h2 style="margin:0;">Rendelés #<?= (int)$order['id'] ?></h2>
      <div class="muted">
        Dátum: <?= htmlspecialchars($order['created_at'] ?? '-') ?>
        <?php if ($isAdmin && !empty($order['email'])): ?>
          · Felhasználó: <?= htmlspecialchars($order['email']) ?>
        <?php endif; ?>
      </div>
    </div>
    <div>
      <span class="<?= statusClass($order['status'] ?? '') ?>">
        <?= htmlspecialchars(statusLabel($order['status'] ?? '')) ?>
      </span>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="card">
      <h3>Szállítás</h3>
      <div><b>Név:</b> <?= htmlspecialchars($order['shipping_name'] ?? '-') ?></div>
      <div><b>Cím:</b> <?= nl2br(htmlspecialchars($order['shipping_address'] ?? '-')) ?></div>
    </div>

    <div class="card">
      <h3>Fizetés</h3>
      <div><b>Mód:</b> <?= htmlspecialchars($order['payment_method'] ?? '-') ?></div>
      <div class="muted" style="margin-top:8px;">Összesen:</div>
      <div style="font-size:22px; font-weight:700;">
        <?= number_format($total, 0, ',', ' ') ?> Ft
      </div>
    </div>

    <?php if ($isAdmin): ?>
      <div class="card">
        <h3>Admin – státusz</h3>
        <form method="post" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <select name="new_status">
            <?php foreach ($allowedStatuses as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>" <?= ($order['status'] ?? '') === $st ? 'selected' : '' ?>>
                <?= htmlspecialchars(statusLabel($st)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary" type="submit">Mentés</button>
        </form>
        <div class="muted" style="margin-top:8px;">Csak admin tud módosítani.</div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-top:16px;">
    <h3>Tételek</h3>

    <?php if (empty($items)): ?>
      <div class="muted">Nincs tétel.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Termék</th>
            <th class="right">Egységár</th>
            <th class="right">Db</th>
            <th class="right">Részösszeg</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it):
            $sub = ((float)$it['unit_price']) * ((int)$it['qty'];
          ?>
            <tr>
              <td>
                <div class="product">
                  <img src="<?= htmlspecialchars($it['product_image'] ?: 'assets/no-image.png') ?>" alt="">
                  <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($it['product_name']) ?></div>
                    <div class="muted"><?= number_format((float)$it['unit_price'], 0, ',', ' ') ?> Ft / db</div>
                  </div>
                </div>
              </td>
              <td class="right"><?= number_format((float)$it['unit_price'], 0, ',', ' ') ?> Ft</td>
              <td class="right"><?= (int)$it['qty'] ?></td>
              <td class="right"><b><?= number_format($sub, 0, ',', ' ') ?> Ft</b></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" class="right"><b>Összesen</b></td>
            <td class="right"><b><?= number_format($total, 0, ',', ' ') ?> Ft</b></td>
          </tr>
        </tfoot>
      </table>
    <?php endif; ?>
  </div>

</div>
</body>
</html>