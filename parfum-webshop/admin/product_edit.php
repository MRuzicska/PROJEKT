<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// kategóriák
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// alapértékek
$product = [
  'name' => '',
  'brand' => '',
  'description' => '',
  'price' => 0,
  'stock' => 0,
  'category_id' => $cats ? (int)$cats[0]['id'] : 1,
  'image_url' => ''
];

if ($id > 0) {
  $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $st->execute([$id]);
  $row = $st->fetch();
  if (!$row) { die("Nincs ilyen termék."); }
  $product = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = post('name');
  $brand = post('brand');
  $description = post('description');
  $price = (int)post('price', '0');
  $stock = (int)post('stock', '0');
