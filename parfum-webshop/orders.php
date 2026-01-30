<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
$orders->execute([$userId]);
$rows = $orders->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Rendeléseim</title>
<link rel="stylesheet" href="css/orders.css"></head>
<body>
<h1>Rendeléseim</h1>
<p><a href="index.php">← Termékek</a></p>

<?php if (!$rows): ?>
  <p>Még nincs rendelésed.</p>
<?php else: ?>
  <table border="1" cellpadding="6">
    <tr><th>#</th><th>Összeg</th><th>Státusz</th><th>Dátum</th></tr>
    <?php foreach ($rows as $o): ?>
      <tr>
        <td><?=$o['id']?></td>
        <td><?=$o['total_price']?> Ft</td>
        <td><?=h($o['status'])?></td>
        <td><?=h($o['created_at'])?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</body>
</html>
