<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

$id = (int)post('id', '0');
if ($id <= 0) {
  header('Location: products.php');
  exit;
}

// (vizsgán plusz pont) ha rendelésben szerepel, ne engedjük törölni
$chk = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id=?");
$chk->execute([$id]);
if ((int)$chk->fetchColumn() > 0) {
  // egyszerűen visszadob (ha akarsz, csinálhatsz session üzenetet)
  header('Location: products.php');
  exit;
}

$pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);

header('Location: products.php');
exit;
