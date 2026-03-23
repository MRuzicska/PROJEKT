<?php declare(strict_types=1);

if (!isset($_GET['id']) || (int) $_GET['id'] <= 0) {
  header("Location: products.php");
  exit;
}

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.id = p.category_id
  WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
  http_response_code(404);
  exit('A termék nem található.');
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title><?= h($product['brand'] . ' – ' . $product['name']) ?></title>
  <link rel="stylesheet" href="css/product.css">
</head>

<body>

  <!-- ===== NAVBAR (1:1 INDEX) ===== -->
  <header class="navbar">
    <div class="logo">
      <img src="images/logo-placeholder.png" alt="">
      <span>Parfum p'Dm</span>
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
  <main class="page-container">
  <!-- ===== PRODUCT LAYOUT ===== -->
  <div class="product-container">

    <!-- BAL OLDAL: KÉP -->
    <div class="product-image">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
      <?php endif; ?>
    </div>

    <!-- JOBB OLDAL: INFÓ -->
    <div class="product-info">

      <h1><?= h($product['brand']) ?> – <?= h($product['name']) ?></h1>

      <p class="category">
        Kategória: <?= h($product['category_name'] ?? '-') ?>
      </p>

      <!-- ===== IDE JÖN A PARFÜM LEÍRÁS (ÜRES HELY) ===== -->
      <div class="description-box">
        <!-- IDE ÍRD MAJD A LEÍRÁST -->
      </div>

      <p class="price">
        Ár: <?= (int) $product['price'] ?> Ft
      </p>

      <p class="stock">
        Készlet: <?= (int) $product['stock'] ?>
      </p>

      <!-- ===== KISZERELÉS (HA AKARSZ MÉG FEJLESZTENI) ===== -->
      <form method="post" action="cart_add.php">
        <label>Darab:</label>
        <input type="number" name="qty" min="1" value="1">

        <button type="submit">Kosárba</button>
      </form>

      <a href="index.php" class="back-link">← Vissza</a>

    </div>

  </div>

  <script>
    function toggleMenu() {
      document.getElementById('mobileMenu').classList.toggle('show');
    }
    function toggleCart() {
      document.querySelector('.cart-dropdown').classList.toggle('show');
    }

    function toggleProfile() {
      document.querySelector('.profile-dropdown').classList.toggle('show');
    }
  </script>

</main>

</body>

</html>