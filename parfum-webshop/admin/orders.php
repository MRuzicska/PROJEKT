<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$statuses = ['NEW','PROCESSING','COMPLETED','CANCELLED'];
$msg = '';

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

function adminStatusClass(string $status): string {
  return match ($status) {
    'NEW' => 'status-badge status-new',
    'PROCESSING' => 'status-badge status-processing',
    'COMPLETED' => 'status-badge status-completed',
    'CANCELLED' => 'status-badge status-cancelled',
    default => 'status-badge',
  };
}
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Admin - Rendelések</title>
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="navbar">
  <div class="logo">
    <a href="../index.php">Parfum p'Dm Admin</a>
  </div>

  <nav class="nav-links">
    <a href="index.php">Admin menü</a>
    <a href="products.php">Termékek</a>
  </nav>

  <div class="nav-actions">
    <a href="../index.php">Webshop</a>
    <a href="../logout.php">Kilépés</a>
  </div>
</header>

<main class="admin-page">
  <h1 class="admin-title">Admin – Rendelések</h1>

  <?php if (!empty($msg)): ?>
    <div class="alert-success"><?= h($msg) ?></div>
  <?php endif; ?>

  <section class="admin-card">
    <h2>Összes rendelés</h2>

    <div class="table-wrap">
      <table class="admin-table">
        <tr>
          <th>#</th>
          <th>Felhasználó</th>
          <th>Összeg</th>
          <th>Státusz</th>
          <th>Szállítás</th>
          <th>Dátum</th>
          <th>Művelet</th>
        </tr>

        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td><?= h($o['user_name']) ?> (<?= h($o['user_email']) ?>)</td>
            <td><?= $o['total_price'] ?> Ft</td>
            <td>
              <span class="<?= adminStatusClass((string)$o['status']) ?>">
                <?= h($o['status']) ?>
              </span>
            </td>
            <td><?= h($o['shipping_name']) ?>, <?= h($o['shipping_address']) ?>, <?= h($o['shipping_phone']) ?></td>
            <td><?= h($o['created_at']) ?></td>
            <td>
              <form method="post" class="button-row" style="margin-top:0;">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">

                <select name="status" style="padding:10px 12px;border-radius:10px;border:1px solid #4b6473;background:#122b3a;color:#fff;">
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-primary">Mentés</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </section>
</main>

</body>
</html>