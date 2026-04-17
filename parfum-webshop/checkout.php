<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

function load_cart(PDO $pdo, int $userId): array {
  $st = $pdo->prepare(
    "SELECT ci.product_id, ci.quantity, p.price, p.stock
     FROM cart_items ci JOIN products p ON p.id=ci.product_id
     WHERE ci.user_id=?"
  );
  $st->execute([$userId]);
  return $st->fetchAll();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $shipping_name = post('shipping_name');
  $shipping_address = post('shipping_address');
  $shipping_phone = post('shipping_phone');

$email = post('shipping_email');

// ÜRES + HIBÁS EMAIL ELLENŐRZÉS
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $error = "Adj meg egy érvényes email címet.";
}

  if ($shipping_name === '' || $shipping_address === '' || $shipping_phone === '') {
    $error = "Minden szállítási adat kötelező.";
  } else {
    $cart = load_cart($pdo, $userId);
    if (!$cart) {
      $error = "A kosár üres.";
    } else {
      try {
        $pdo->beginTransaction();

        // készlet ellenőrzés + total
        $total = 0;
        foreach ($cart as $it) {
          if ((int)$it['quantity'] > (int)$it['stock']) {
            throw new Exception("Nincs elég készlet az egyik termékből.");
          }
          $total += (int)$it['price'] * (int)$it['quantity'];
        }
        // orders insert
        $pdo->prepare(
          "INSERT INTO orders (user_id, total_price, status, shipping_name, shipping_address, shipping_phone)
           VALUES (?, ?, 'NEW', ?, ?, ?)"
        )->execute([$userId, $total, $shipping_name, $shipping_address, $shipping_phone]);
        $orderId = (int)$pdo->lastInsertId();

        // order_items + stock csökkentés
        $insItem = $pdo->prepare(
          "INSERT INTO order_items (order_id, product_id, unit_price, quantity, line_total)
           VALUES (?, ?, ?, ?, ?)"
        );
        $decStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?");

        foreach ($cart as $it) {
          $line = (int)$it['price'] * (int)$it['quantity'];
          $insItem->execute([$orderId, (int)$it['product_id'], (int)$it['price'], (int)$it['quantity'], $line]);
          $decStock->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }

        // kosár ürítés
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$userId]);

        $pdo->commit();
        header("Location: orders.php");
        exit;
      } catch (Throwable $e) {
        $pdo->rollBack();
        $error = "Hiba a rendelésnél: " . $e->getMessage();
      }
    }
  }
}
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Rendelés</title>
<link rel="stylesheet" href="css/checkout.css"></head>
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
  
<h1>Rendelés leadása</h1>
<a href="cart.php"><button class="btn"> ← Vissza a kosárhoz </button> </a>

<?php if ($error) echo '<p style="color:red;">'.h($error).'</p>'; ?>

<form method="post">
  <label>Név: <input name="shipping_name" value="<?=h(post('shipping_name'))?>"></label><br>
  <label>Telefon: <input name="shipping_phone" value="<?=h(post('shipping_phone'))?>"></label><br>
  <label>Email: <input type="email" name="shipping_email" value="<?=h(post('shipping_email'))?>" required></label><br>
  <label>Cím: <input name="shipping_address" value="<?=h(post('shipping_address'))?>"></label><br>
  <button type="submit">Megrendelés</button>
</form>
</body>
</html>
