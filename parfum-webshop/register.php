<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = post('name');
  $email = post('email');
  $pass = post('password');

  if ($name === '' || $email === '' || $pass === '') {
    $error = 'Minden mező kitöltése kötelező.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Érvénytelen email cím.';
  } elseif (strlen($pass) < 6) {
    $error = 'A jelszó minimum 6 karakter legyen.';
  } else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
      $error = 'Ezzel az email címmel már regisztráltak.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
      $stmt->execute([$name, $email, $hash]);

      header('Location: login.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Regisztráció</title>
  <link rel="stylesheet" href="css/auth.css">
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

  <div class="auth-wrap">
    <div class="auth-card">
      <h1>Regisztráció</h1>

      <?php if (!empty($error)): ?>
        <p class="auth-error"><?= h($error) ?></p>
      <?php endif; ?>

      <form method="post">
        <label for="username">Név</label>
        <input type="text" name="name" autocomplete="name" value="<?= h($_POST['username'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" name="email". autocomplete="email" value="<?= h($_POST['email'] ?? '') ?>">

        <label for="password">Jelszó</label>
        <input type="password" id="password" autocomplete="password" name="password">

        <button type="submit">Regisztráció</button>
      </form>

      <p class="auth-switch">
        Már van fiókom <a href="login.php">Belépés</a>
      </p>
    </div>
  </div>
</body>

</html>