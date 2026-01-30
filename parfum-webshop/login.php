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
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    header('Location: index.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Bejelentkezés</title>
<link rel="stylesheet" href="css/login.css"></head>
<body>
<h1>Bejelentkezés</h1>
<?php if ($error) echo '<p style="color:red;">'.h($error).'</p>'; ?>
<form method="post">
  <label>Email: <input name="email" value="<?=h(post('email'))?>"></label><br>
  <label>Jelszó: <input type="password" name="password"></label><br>
  <button type="submit">Belépés</button>
</form>
<p><a href="register.php">Regisztráció</a></p>
</body>
</html>
