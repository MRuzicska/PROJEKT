<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

require_login();
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Rendeléseim</title>
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
      <a href="logout.php">Kijelentkezés</a>
    </div>
  </header>

  <main>

    <div class="page-wrap">
      <h1 class="page-title">Rendeléseim</h1>

      <?php if (!$rows): ?>
        <div class="card empty-box">
          <h2>Még nincs rendelésed</h2>
          <p>Nézz szét a kínálatban, és add le az első rendelésed.</p>

          <div class="button-row" style="justify-content:center;">
            <a href="products.php" class="btn-primary">Parfümök megtekintése</a>
          </div>
        </div>
      <?php else: ?>
        <div class="order-list">
          <?php foreach ($rows as $o): ?>
            <div class="order-item">
              <div class="order-top">
                <div>
                  <div class="order-id">Rendelés #<?= (int) $o['id'] ?></div>
                  <div class="order-meta">Dátum: <?= h($o['created_at']) ?></div>
                </div>

                <div class="order-status">
                  <?= h($o['status']) ?>
                </div>
              </div>

              <div class="summary-box">
                <div class="summary-row">
                  <span>Végösszeg</span>
                  <span><?= (int) $o['total_price'] ?> Ft</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="button-row">
          <a href="products.php" class="btn-secondary">← Vissza a termékekhez</a>
        </div>
      <?php endif; ?>
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