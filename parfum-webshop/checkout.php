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
<h1>Rendelés leadása</h1>
<p><a href="cart.php">← Vissza a kosárhoz</a></p>

<?php if ($error) echo '<p style="color:red;">'.h($error).'</p>'; ?>

<form method="post">
  <label>Név: <input name="shipping_name" value="<?=h(post('shipping_name'))?>"></label><br>
  <label>Cím: <input name="shipping_address" value="<?=h(post('shipping_address'))?>"></label><br>
  <label>Telefon: <input name="shipping_phone" value="<?=h(post('shipping_phone'))?>"></label><br>
  <button type="submit">Megrendelés</button>
</form>
</body>
</html>
