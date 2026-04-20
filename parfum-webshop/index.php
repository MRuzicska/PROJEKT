<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

// Slider képek
$sliderImages = [
  "images/slider1.jpg",
  "images/slider2.webp",
  "images/slider3.webp",
  "images/slider4.webp",
  "images/slider5.webp",
];

// Termékek
$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Parfum p'Dm</title>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/footer.css">
</head>

<body>

  <!-- ===== NAVBAR ===== -->
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
    <!-- ===== SLIDER ===== -->
    <div class="slider">
      <a class="prev" href="javascript:void(0)" onclick="prevSlide()">&#10094;</a>
      <div class="slide-image">
        <img id="slider-img" src="<?= $sliderImages[0] ?>" alt="Slider">
      </div>
      <a class="next" href="javascript:void(0)" onclick="nextSlide()">&#10095;</a>
    </div>

    <div class="info-text">
      <p>
        Fedezd fel a parfümök világát! 💧 Kínálatunkban a klasszikus aromáktól a modern, trendi illatokig mindent
        megtalálsz.
        Ne hagyd, hogy az unalmas napok eluralkodjanak – egy igazán jó illat mindig feldobja a hangulatod!
        Kattints, szagolj, élvezd – a parfüm nem csak egy illat, hanem élmény is.
      </p>
    </div>

    <!-- ===== TERMÉKKÁRTYÁK ===== -->
    <div class="product-grid" id="product-grid">
      <?php foreach ($products as $p): ?>
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

              <input type="number" name="qty" value="1" min="1" max="<?= (int) $p['stock'] ?>" class="qty-input">

              <button type="submit" class="add-to-cart-btn">Kosárba</button>
            </form>
          <?php else: ?>
            <div class="sold-out">Elfogyott</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Termék lapozás -->
    <div class="slider-nav">
      <button onclick="prevProducts()">← Előző</button>
      <button onclick="nextProducts()">Következő →</button>
      <a href="products.php" class="all-products">Összes parfüm</a>
    </div>
  </main>
  <!-- Footer -->
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
    /* ===== SLIDER JS ===== */
    const sliderImages = <?= json_encode($sliderImages) ?>;
    let slideIndex = 0;
    const sliderImgEl = document.getElementById('slider-img');
    function showSlide(i) { slideIndex = (i + sliderImages.length) % sliderImages.length; sliderImgEl.src = sliderImages[slideIndex]; }
    function nextSlide() { showSlide(slideIndex + 1); }
    function prevSlide() { showSlide(slideIndex - 1); }
    setInterval(nextSlide, 5000);

    /* ===== TERMÉKLAPOZÁS JS ===== */
    const productsPerPage = 4;
    let productPage = 0;
    const productGrid = document.getElementById('product-grid');
    const productCards = Array.from(productGrid.children);
    function showProductPage(page) {
      productPage = page;
      const start = page * productsPerPage, end = start + productsPerPage;
      productCards.forEach((card, i) => { card.style.display = (i >= start && i < end) ? 'flex' : 'none'; });
    }
    function nextProducts() { const maxPage = Math.ceil(productCards.length / productsPerPage) - 1; showProductPage(productPage < maxPage ? productPage + 1 : 0); }
    function prevProducts() { const maxPage = Math.ceil(productCards.length / productsPerPage) - 1; showProductPage(productPage > 0 ? productPage - 1 : maxPage); }
    showSlide(0); showProductPage(0);

    /* ===== NAVBAR DROPDOWN JS ===== */
    function toggleMenu() {
      document.getElementById('mobileMenu').classList.toggle('show');
    }
    function toggleCart() { document.querySelector('.cart-dropdown').classList.toggle('show'); }
    function toggleProfile() { document.querySelector('.profile-dropdown').classList.toggle('show'); }
  </script>
</body>

</html>