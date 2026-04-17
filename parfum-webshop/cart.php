<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = post('action');

  if ($action === 'add') {
    $pid = (int) post('product_id');
    $variantId = (int) post('variant_id');
    $qty = max(1, (int) post('qty', '1'));

    // Ha nincs kiválasztott variant, automatikusan az első elérhetőt választjuk
    if ($variantId <= 0) {
        $defaultVariantStmt = $pdo->prepare("
            SELECT id, product_id, price, stock
            FROM product_variants
            WHERE product_id = ?
            ORDER BY size_ml ASC
            LIMIT 1
        ");
        $defaultVariantStmt->execute([$pid]);
        $defaultVariant = $defaultVariantStmt->fetch();

        if (!$defaultVariant) {
            die("Ehhez a termékhez nincs elérhető kiszerelés.");
        }

        $variantId = (int) $defaultVariant['id'];
        $stock = (int) $defaultVariant['stock'];
    } else {
        $variantStmt = $pdo->prepare("
            SELECT id, product_id, price, stock
            FROM product_variants
            WHERE id = ? AND product_id = ?
        ");
        $variantStmt->execute([$variantId, $pid]);
        $variant = $variantStmt->fetch();

        if (!$variant) {
            die("Nincs ilyen kiszerelés.");
        }

        $stock = (int) $variant['stock'];
    }

    if ($qty > $stock) {
        die("Nincs elég készlet.");
    }

    if (is_logged_in()) {
        $userId = (int) $_SESSION['user_id'];

        $checkStmt = $pdo->prepare("
            SELECT id, quantity
            FROM cart_items
            WHERE user_id = ? AND product_id = ? AND variant_id = ?
        ");
        $checkStmt->execute([$userId, $pid, $variantId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $newQty = min((int) $existing['quantity'] + $qty, $stock);

            $pdo->prepare("
                UPDATE cart_items
                SET quantity = ?
                WHERE id = ?
            ")->execute([$newQty, $existing['id']]);
        } else {
            $pdo->prepare("
                INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
                VALUES (?, ?, ?, ?)
            ")->execute([$userId, $pid, $variantId, $qty]);
        }
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $sessionKey = $pid . '_' . $variantId;

        if (isset($_SESSION['cart'][$sessionKey])) {
            $_SESSION['cart'][$sessionKey]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$sessionKey] = [
                'product_id' => $pid,
                'variant_id' => $variantId,
                'quantity' => $qty
            ];
        }

        if ($_SESSION['cart'][$sessionKey]['quantity'] > $stock) {
            $_SESSION['cart'][$sessionKey]['quantity'] = $stock;
        }
    }

    header('Location: cart.php');
    exit;
}

  if ($action === 'update') {
    $id = (int) post('item_id');
    $qty = max(1, (int) post('qty', '1'));

    if (is_logged_in()) {
      $userId = (int) $_SESSION['user_id'];

      $st = $pdo->prepare(
        "SELECT ci.product_id, p.stock
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.id = ? AND ci.user_id = ?"
      );
      $st->execute([$id, $userId]);
      $row = $st->fetch();

      if (!$row) {
        die("Nincs ilyen kosár tétel.");
      }

      if ($qty > (int) $row['stock']) {
        $qty = (int) $row['stock'];
      }

      $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?")
          ->execute([$qty, $id, $userId]);
    } else {
      if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
      }

      $pid = $id;

      $st = $pdo->prepare("SELECT stock FROM products WHERE id=?");
      $st->execute([$pid]);
      $row = $st->fetch();

      if (!$row) {
        unset($_SESSION['cart'][$pid]);
      } else {
        $stock = (int) $row['stock'];

        if ($qty > $stock) {
          $qty = $stock;
        }

        $_SESSION['cart'][$pid] = $qty;
      }
    }

    header('Location: cart.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int) post('item_id');

    if (is_logged_in()) {
      $userId = (int) $_SESSION['user_id'];

      $pdo->prepare("DELETE FROM cart_items WHERE id=? AND user_id=?")
          ->execute([$id, $userId]);
    } else {
      if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
      }

      unset($_SESSION['cart'][$id]);
    }

    header('Location: cart.php');
    exit;
  }
}

$items = [];

if (is_logged_in()) {
  $userId = (int) $_SESSION['user_id'];

  $stmt = $pdo->prepare(
    "SELECT ci.id, ci.product_id, ci.quantity, p.name, p.brand, p.price, p.stock
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.user_id = ?
     ORDER BY ci.id DESC"
  );
  $stmt->execute([$userId]);
  $items = $stmt->fetchAll();
} else {
  if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }

  if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);

    if (!empty($productIds)) {
      $placeholders = implode(',', array_fill(0, count($productIds), '?'));

      $stmt = $pdo->prepare(
        "SELECT id, name, brand, price, stock
         FROM products
         WHERE id IN ($placeholders)"
      );
      $stmt->execute($productIds);
      $products = $stmt->fetchAll();

      foreach ($products as $product) {
        $pid = (int) $product['id'];
        $qty = (int) ($_SESSION['cart'][$pid] ?? 0);

        if ($qty > 0) {
          if ($qty > (int) $product['stock']) {
            $qty = (int) $product['stock'];
            $_SESSION['cart'][$pid] = $qty;
          }

          $items[] = [
            'id' => $pid,
            'product_id' => $pid,
            'quantity' => $qty,
            'name' => $product['name'],
            'brand' => $product['brand'],
            'price' => $product['price'],
            'stock' => $product['stock'],
          ];
        }
      }
    }
  }
}

$total = 0;
foreach ($items as $it) {
  $total += (int) $it['price'] * (int) $it['quantity'];
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>Kosár</title>
  <link rel="stylesheet" href="css/cart.css">
  <link rel="stylesheet" href="css/footer.css">
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
      <div class="profile-wrapper">
        <button onclick="toggleProfile()">👤 Profil</button>
        <div class="dropdown profile-dropdown">
          <?php if (is_logged_in()): ?>
            <p>Üdvözlünk, <?= h($_SESSION['username'] ?? 'Felhasználó') ?>!</p>
            <?php if (is_admin()): ?>
              <a href="admin/admin.php" class="btn-small">Admin</a>
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
  <main>
<h1>Kosár</h1>


<?php if (!$items): ?>
  <p>A kosár üres.</p>
<?php else: ?>
  <table border="1" cellpadding="6">
    <tr><th>Termék</th><th>Ár</th><th>Mennyiség</th><th>Részösszeg</th><th>Művelet</th></tr>
    <?php foreach ($items as $it): 
      $sub = (int)$it['price'] * (int)$it['quantity'];
    ?>
      <tr>
  
        <td><?=h($it['brand'])?> – <?=h($it['name'])?></td>
        <td><?= (int)$it['price'] ?> Ft</td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="item_id" value="<?=$it['id']?>">
            <input type="number" name="qty" min="1" max="<?=$it['stock']?>" value="<?=$it['quantity']?>" style="width:70px;">
            <button type="submit">Mentés</button>
          </form>
        </td>
        <td><?=$sub?> Ft</td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Törlöd?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="item_id" value="<?=$it['id']?>">
            <button type="submit">Törlés</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <div class="button-row">
    <a href="index.php" class="btnn">← Vissza a termékekhez</a>
    <a href="checkout.php" class="btn">Tovább a rendeléshez →</a>
  </div>
<?php endif; ?>
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

<script>
  function toggleProfile() {
    document.querySelector('.profile-dropdown').classList.toggle('show');
  }

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.profile-wrapper')) {
      const dropdown = document.querySelector('.profile-dropdown');
      if (dropdown) {
        dropdown.classList.remove('show');
      }
    }
  });
</script>

</body>
</html>
