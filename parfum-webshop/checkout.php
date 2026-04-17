<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

require_login();
$userId = (int) $_SESSION['user_id'];

function load_cart(PDO $pdo, int $userId): array
{
  $st = $pdo->prepare(
    "SELECT ci.product_id, ci.quantity, p.name, p.brand, p.price, p.stock
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.user_id = ?"
  );
  $st->execute([$userId]);
  return $st->fetchAll();
}

$items = load_cart($pdo, $userId);

$total = 0;
foreach ($items as $it) {
  $total += (int) $it['price'] * (int) $it['quantity'];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $shipping_name = trim(post('shipping_name'));
  $shipping_address = trim(post('shipping_address'));
  $shipping_phone = trim(post('shipping_phone'));
  $shipping_email = trim(post('shipping_email'));

  if (
    $shipping_name === '' ||
    $shipping_address === '' ||
    $shipping_phone === '' ||
    $shipping_email === ''
  ) {
    $error = 'Minden szállítási adat megadása kötelező.';
  } elseif (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Adj meg egy érvényes email címet.';
  } else {
    $cart = load_cart($pdo, $userId);

    if (!$cart) {
      $error = 'A kosár üres.';
    } else {
      try {
        $pdo->beginTransaction();

        $total = 0;
        foreach ($cart as $it) {
          if ((int) $it['quantity'] > (int) $it['stock']) {
            throw new Exception('Nincs elég készlet az egyik termékből.');
          }
          $total += (int) $it['price'] * (int) $it['quantity'];
        }

        $pdo->prepare(
          "INSERT INTO orders (user_id, total_price, status, shipping_name, shipping_address, shipping_phone)
           VALUES (?, ?, 'NEW', ?, ?, ?)"
        )->execute([
              $userId,
              $total,
              $shipping_name,
              $shipping_address,
              $shipping_phone
            ]);

        $orderId = (int) $pdo->lastInsertId();

        $insItem = $pdo->prepare(
          "INSERT INTO order_items (order_id, product_id, unit_price, quantity, line_total)
           VALUES (?, ?, ?, ?, ?)"
        );

        $decStock = $pdo->prepare(
          "UPDATE products SET stock = stock - ? WHERE id = ?"
        );

        foreach ($cart as $it) {
          $line = (int) $it['price'] * (int) $it['quantity'];

          $insItem->execute([
            $orderId,
            (int) $it['product_id'],
            (int) $it['price'],
            (int) $it['quantity'],
            $line
          ]);

          $decStock->execute([
            (int) $it['quantity'],
            (int) $it['product_id']
          ]);
        }

        $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);

        $pdo->commit();
        header('Location: orders.php');
        exit;
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $error = 'Hiba a rendelésnél: ' . $e->getMessage();
      }
    }
  }
}
?>
<!doctype html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Rendelés leadása</title>
  <link rel="stylesheet" href="css/orders.css">
  <link rel="stylesheet" href="css/footer.css">
</head>

<body>

  <header class="navbar">
    <div class="logo">
      <a href="index.php">Parfum p'Dm</a>
    </div>

    <nav class="nav-links">
      <a href="products.php">Összes parfüm</a>
      <a href="products.php?category_id=2">Csak férfi</a>
      <a href="products.php?category_id=1">Csak női</a>
      <a href="products.php?category_id=3">Csak unisex</a>
    </nav>

    <div class="nav-actions">
      <a href="orders.php">Rendeléseim</a>
    </div>
  </header>

  <main>

    <div class="page-wrap">
      <h1 class="page-title">Rendelés leadása</h1>

      <?php if ($error): ?>
        <div class="alert-error"><?= h($error) ?></div>
      <?php endif; ?>

      <div class="card">
        <h2>Szállítási adatok</h2>

        <form method="post" class="checkout-form">
          <div class="form-group">
            <label for="shipping_name">Név</label>
            <input type="text" id="shipping_name" name="shipping_name" value="<?= h(post('shipping_name')) ?>">
          </div>

          <div class="form-group">
            <label for="shipping_email">Email</label>
            <input type="email" id="shipping_email" name="shipping_email" value="<?= h(post('shipping_email')) ?>">
          </div>

          <div class="form-group">
            <label for="shipping_phone">Telefon</label>
            <input type="text" id="shipping_phone" name="shipping_phone" value="<?= h(post('shipping_phone')) ?>">
          </div>

          <div class="form-group full">
            <label for="shipping_address">Cím</label>
            <input type="text" id="shipping_address" name="shipping_address" value="<?= h(post('shipping_address')) ?>">
          </div>

          <div class="form-group full">
            <button type="submit" class="btn-primary">Megrendelés</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h2>Rendelési összegzés</h2>

        <?php if (!$items): ?>
          <div class="alert-info">A kosarad jelenleg üres.</div>
        <?php else: ?>
          <table class="order-table">
            <tr>
              <th>Termék</th>
              <th>Ár</th>
              <th>Mennyiség</th>
              <th>Részösszeg</th>
            </tr>

            <?php foreach ($items as $it): ?>
              <?php $sub = (int) $it['price'] * (int) $it['quantity']; ?>
              <tr>
                <td><?= h($it['brand']) ?> – <?= h($it['name']) ?></td>
                <td><?= (int) $it['price'] ?> Ft</td>
                <td><?= (int) $it['quantity'] ?></td>
                <td><?= $sub ?> Ft</td>
              </tr>
            <?php endforeach; ?>
          </table>

          <div class="summary-box" style="margin-top:20px;">
            <div class="summary-row">
              <span>Szállítás</span>
              <span>0 Ft</span>
            </div>
            <div class="summary-row total">
              <span>Végösszeg</span>
              <span><?= (int) $total ?> Ft</span>
            </div>
          </div>
        <?php endif; ?>

        <div class="button-row">
          <a href="cart.php" class="btn-secondary">← Vissza a kosárhoz</a>
        </div>
      </div>
    </div>

  </main>

  <footer class="site-footer">
    <div class="footer-container">

      <div class="footer-column">
        <h3>Parfum p'Dm</h3>
        <p>
          Fedezd fel prémium parfümkínálatunkat női, férfi és unisex illatokkal.
        </p>
      </div>

      <div class="footer-column">
        <h3>Kapcsolat</h3>
        <p>Email: info@parfumpdm.hu</p>
        <p>Telefon: +36 20 123 4567</p>
        <p>Cím: 1182 Budapest, Illat utca 12.</p>
      </div>

      <div class="footer-column">
        <h3>Információk</h3>
        <a href="products.php">Összes parfüm</a>
        <a href="cart.php">Kosár</a>
        <a href="orders.php">Rendeléseim</a>
        <a href="login.php">Bejelentkezés</a>
      </div>

      <div class="footer-column">
        <h3>Vásárlás</h3>
        <p>Biztonságos rendelés</p>
        <p>Gyors kiszállítás</p>
        <p>Minőségi termékek</p>
        <p>Ügyfélszolgálat minden hétköznap</p>
      </div>

    </div>


    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Parfum p'Dm – Minden jog fenntartva.</p>
    </div>
  </footer>

</body>

</html>