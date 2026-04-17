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

$relatedProducts = $pdo->prepare("
SELECT p.*, c.name AS category_name
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE p.id != ?
ORDER BY p.id DESC
LIMIT 12
");

$relatedProducts->execute([$id]);

$related = $relatedProducts->fetchAll();

if (!$product) {
  http_response_code(404);
  exit('A termék nem található.');
}

$variantsStmt = $pdo->prepare("
  SELECT id, product_id, size_ml, price, stock
  FROM product_variants
  WHERE product_id = ?
  ORDER BY size_ml ASC
");
$variantsStmt->execute([$id]);
$variants = $variantsStmt->fetchAll();

$selectedVariant = $variants[0] ?? null;

if (isset($_GET['variant_id'])) {
  $requestedVariantId = (int) $_GET['variant_id'];

  foreach ($variants as $variant) {
    if ((int) $variant['id'] === $requestedVariantId) {
      $selectedVariant = $variant;
      break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title><?= h($product['brand'] . ' – ' . $product['name']) ?></title>
  <link rel="stylesheet" href="css/product.css">
  <link rel="stylesheet" href="css/footer.css">
</head>

<body>

  <!-- ===== NAVBAR (1:1 INDEX) ===== -->
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
          <?php if (is_logged_in()): ?>
            <?php
            $userId = (int) $_SESSION['user_id'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $action = post('action');

              if ($action === 'add') {

                $pid = (int) post('product_id');
                $qty = max(1, (int) post('qty', '1'));

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

                // BELÉPETT USER -> adatbázis
                if (is_logged_in()) {

                  $userId = (int) $_SESSION['user_id'];

                  $pdo->prepare(
                    "INSERT INTO cart_items (user_id, product_id, quantity)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)"
                  )->execute([$userId, $pid, $qty, $stock]);

                }

                // VENDEG USER -> session kosar
                else {

                  if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                  }

                  if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid] += $qty;
                  } else {
                    $_SESSION['cart'][$pid] = $qty;
                  }

                  if ($_SESSION['cart'][$pid] > $stock) {
                    $_SESSION['cart'][$pid] = $stock;
                  }
                }

                header("Location: cart.php");
                exit;
              }

              if ($action === 'update') {
                $id = (int) post('item_id');
                $qty = max(1, (int) post('qty', '1'));

                // készlet check a termékhez
                $st = $pdo->prepare(
                  "SELECT ci.product_id, p.stock
           FROM cart_items ci
           JOIN products p ON p.id = ci.product_id
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
            foreach ($items as $it) {
              $total += (int) $it['price'] * (int) $it['quantity'];
            }
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

          <?php else: ?>
            <p>A kosár megtekintéséhez jelentkezz be.</p>
            <a href="login.php" class="btn-small">Bejelentkezés</a>
            <a href="register.php" class="btn-small">Regisztráció</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="profile-wrapper">
        <button onclick="toggleProfile()">👤 Profil</button>
        <div class="dropdown profile-dropdown">
          <?php if (is_logged_in()): ?>
            <p>Üdvözlünk,
              <?= h($_SESSION['username'] ?? 'Felhasználó') ?>!
            </p>
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

        <div class="variant-box">
          <?php if (!empty($variants)): ?>
            <label for="variant-select">Kiszerelés</label>

            <select id="variant-select" onchange="changeVariant(this.value)">
              <?php foreach ($variants as $variant): ?>
                <option value="<?= (int) $variant['id'] ?>" <?= $selectedVariant && (int) $selectedVariant['id'] === (int) $variant['id'] ? 'selected' : '' ?>>
                  <?= (int) $variant['size_ml'] ?> ml
                </option>
              <?php endforeach; ?>
            </select>

            <div class="variant-info">
              <p>
                <strong>Kiszerelés:</strong>
                <span id="variant-size">
                  <?= $selectedVariant ? (int) $selectedVariant['size_ml'] : '-' ?>
                </span> ml
              </p>

              <p>
                <strong>Ár:</strong>
                <span id="variant-price">
                  <?= $selectedVariant ? (int) $selectedVariant['price'] : (int) $product['price'] ?>
                </span> Ft
              </p>

              <p>
                <strong>Készlet:</strong>
                <span id="variant-stock">
                  <?= $selectedVariant ? (int) $selectedVariant['stock'] : (int) $product['stock'] ?>
                </span>
              </p>
            </div>
          <?php else: ?>
            <p>Nincs elérhető kiszerelés ehhez a termékhez.</p>
          <?php endif; ?>
        </div>

        <form method="post" action="cart.php" class="product-buy-form">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <input type="hidden" name="variant_id" id="selected-variant-id"
            value="<?= $selectedVariant ? (int) $selectedVariant['id'] : 0 ?>">

          <label for="qty">Darab:</label>
          <input type="number" id="qty" name="qty" value="1" min="1"
            max="<?= $selectedVariant ? (int) $selectedVariant['stock'] : (int) $product['stock'] ?>">

          <button type="submit">Kosárba</button>
        </form>

        <a href="index.php" class="back-link">← Vissza</a>

      </div>

    </div>

    <!-- KAPCSOLÓDÓ TERMÉKEK -->
    <h2>Kapcsolódó termékek</h2>

    <div class="product-grid" id="related-grid">
      <?php foreach ($related as $p): ?>
        <div class="product-card" onclick="window.location.href='product.php?id=<?= (int) $p['id'] ?>'">
          <div class="product-image">
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['name']) ?>">
            <?php endif; ?>
          </div>

          <div class="product-name"><?= h($p['name']) ?></div>
          <div class="product-brand"><?= h($p['brand']) ?></div>
          <div class="product-category">(<?= h($p['category_name']) ?>)</div>
          <div class="product-price">Ár: <?= (int) $p['price'] ?> Ft</div>
          <div class="product-stock">Készlet: <?= (int) $p['stock'] ?></div>

          <?php if ((int) $p['stock'] > 0): ?>
            <form class="product-actions-row" method="post" action="cart.php" onclick="event.stopPropagation();">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">

              <input type="number" name="qty" value="1" min="1" max="<?= (int) $p['stock'] ?>" class="qty-input"
                onclick="event.stopPropagation();">

              <button type="submit" class="add-to-cart-btn" onclick="event.stopPropagation();">
                Kosárba
              </button>
            </form>
          <?php else: ?>
            <div class="sold-out">Elfogyott</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="slider-nav">
      <button onclick="prevRelated()">← Előző</button>
      <button onclick="nextRelated()">Következő →</button>
      <a href="products.php" class="all-products">Összes parfüm</a>
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

      /* KAPCSOLÓDÓ TERMÉKEK LAPOZÁS */

      const relatedPerPage = 4;

      let relatedPage = 0;

      const relatedGrid =
        document.getElementById('related-grid');

      const relatedCards =
        Array.from(relatedGrid.children);

      function showRelatedPage(page) {

        relatedPage = page;

        const start =
          page * relatedPerPage;

        const end =
          start + relatedPerPage;

        relatedCards.forEach((card, i) => {

          if (i >= start && i < end) {
            card.style.display = "flex";
          } else {
            card.style.display = "none";
          }

        });

      }

      function nextRelated() {

        const maxPage =
          Math.ceil(
            relatedCards.length /
            relatedPerPage
          ) - 1;

        if (relatedPage < maxPage) {
          showRelatedPage(
            relatedPage + 1
          );
        } else {
          showRelatedPage(0);
        }

      }

      function prevRelated() {

        const maxPage =
          Math.ceil(
            relatedCards.length /
            relatedPerPage
          ) - 1;

        if (relatedPage > 0) {
          showRelatedPage(
            relatedPage - 1
          );
        } else {
          showRelatedPage(maxPage);
        }

      }

      showRelatedPage(0);

      const variantData = <?= json_encode($variants, JSON_UNESCAPED_UNICODE) ?>;

      function changeVariant(variantId) {
        const selected = variantData.find(v => Number(v.id) === Number(variantId));
        if (!selected) return;

        const variantSize = document.getElementById('variant-size');
        const variantPrice = document.getElementById('variant-price');
        const variantStock = document.getElementById('variant-stock');
        const hiddenVariantInput = document.getElementById('selected-variant-id');
        const qtyInput = document.getElementById('qty');

        if (variantSize) variantSize.textContent = selected.size_ml;
        if (variantPrice) variantPrice.textContent = selected.price;
        if (variantStock) variantStock.textContent = selected.stock;
        if (hiddenVariantInput) hiddenVariantInput.value = selected.id;

        if (qtyInput) {
          qtyInput.max = selected.stock;
          if (Number(qtyInput.value) > Number(selected.stock)) {
            qtyInput.value = selected.stock > 0 ? selected.stock : 1;
          }
        }
      }
    </script>

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