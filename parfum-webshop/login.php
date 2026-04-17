<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = post('email');
  $pass = post('password');

  $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($pass, $user['password_hash'])) {
    $error = 'Hibás email vagy jelszó.';
  } else {
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    header('Location: index.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Bejelentkezés</title>
  <link rel="stylesheet" href="css/auth.css">
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
  </header>

  <main>
    <div class="auth-wrap">
      <div class="auth-card">
        <h1>Bejelentkezés</h1>

        <?php if (!empty($error)): ?>
          <p class="auth-error"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="post">
          <label>Email: <input name="email" value="<?= h(post('email')) ?>"></label><br>
          <label>Jelszó: <input type="password" name="password"></label><br>
          <button type="submit">Belépés</button>
        </form>
        <p class="auth-switch">
          Nincs fiókod? <a href="register.php">Regisztráció</a>
        </p>
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
      <p>&copy;
        <?= date('Y') ?> Parfum p'Dm – Minden jog fenntartva.
      </p>
    </div>
  </footer>
</body>

</html>