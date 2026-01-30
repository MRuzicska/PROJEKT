<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$statuses = ['NEW','PROCESSING','COMPLETED','CANCELLED'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'status') {
  $id = (int)post('id', '0');
  $status = post('status');

  if (!in_array($status, $statuses, true)) {
    $msg = "Érvénytelen státusz.";
  } else {
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
    $msg = "Státusz frissítve.";
  }
}

$stmt = $pdo->query(
  "SELECT o.*, u.name AS user_name, u.email AS user_email
   FROM orders o
   JOIN users u ON u.id = o.user_id
   ORDER BY o.id DESC"
);
$orders = $stmt->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Admin - Rendelések</title></head>
<body>
<h1>Admin – Rendelések</h1>
<p><a href="index.php">← Admin menü</a></p>

<?php if (!empty($msg)) echo '<p style="color:green;">'.h($msg).'</p>'; ?>

<table border="1" cellpadding="6">
  <tr>
    <th>#</th><th>Felhasználó</th><th>Összeg</th><th>Státusz</th><th>Szállítás</th><th>Dátum</th><th>Művelet</th>
  </tr>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><?=$o['id']?></td>
      <td><?=h($o['user_name'])?> (<?=h($o['user_email'])?>)</td>
      <td><?=$o['total_price']?> Ft</td>
      <td><?=h($o['status'])?></td>
      <td><?=h($o['shipping_name'])?>, <?=h($o['shipping_address'])?>, <?=h($o['shipping_phone'])?></td>
      <td><?=h($o['created_at'])?></td>
      <td>
        <form method="post">
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="id" value="<?=$o['id']?>">
          <select name="status">
            <?php foreach ($statuses as $s): ?>
              <option value="<?=$s?>" <?=$o['status']===$s?'selected':''?>><?=$s?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Mentés</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

</body>
</html>
