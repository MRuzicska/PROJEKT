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
<head><meta charset="utf-8"><title>Regisztráció</title></head>
<body>
<h1>Regisztráció</h1>
<?php if ($error) echo '<p style="color:red;">'.h($error).'</p>'; ?>
<form method="post">
  <label>Név: <input name="name" value="<?=h(post('name'))?>"></label><br>
  <label>Email: <input name="email" value="<?=h(post('email'))?>"></label><br>
  <label>Jelszó: <input type="password" name="password"></label><br>
  <button type="submit">Regisztráció</button>
</form>
<p><a href="login.php">Már van fiókom</a></p>
</body>
</html>


