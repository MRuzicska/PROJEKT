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
  $category_id = (int)post('category_id', '0');
  $image_url = post('image_url');

  if ($name === '' || $brand === '') {
    $error = "A név és a márka kötelező.";
  } elseif ($price <= 0) {
    $error = "Az ár legyen pozitív szám.";
  } elseif ($stock < 0) {
    $error = "A készlet nem lehet negatív.";
  } else {
    // ellenőrzés: category létezik-e
    $chk = $pdo->prepare("SELECT id FROM categories WHERE id=?");
    $chk->execute([$category_id]);
    if (!$chk->fetch()) {
      $error = "Érvénytelen kategória.";
    } else {
      if ($id > 0) {
        $pdo->prepare(
          "UPDATE products
           SET name=?, brand=?, description=?, price=?, stock=?, category_id=?, image_url=?
           WHERE id=?"
        )->execute([$name, $brand, $description, $price, $stock, $category_id, $image_url, $id]);
      } else {
        $pdo->prepare(
          "INSERT INTO products (name, brand, description, price, stock, category_id, image_url)
           VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$name, $brand, $description, $price, $stock, $category_id, $image_url]);
      }
      header("Location: products.php");
      exit;
    }
  }

  // ha hiba, töltsük vissza a formba
  $product = [
    'name' => $name,
    'brand' => $brand,
    'description' => $description,
    'price' => $price,
    'stock' => $stock,
    'category_id' => $category_id,
    'image_url' => $image_url
  ];
}
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Admin - <?= $id>0 ? 'Szerkesztés' : 'Új termék' ?></title></head>
<body>
<h1>Admin – <?= $id>0 ? 'Termék szerkesztése' : 'Új termék' ?></h1>
<p><a href="index.php">← Vissza a listához</a></p>

<?php if ($error) echo '<p style="color:red;">'.h($error).'</p>'; ?>

<form method="post">
  <label>Márka:<br>
    <input name="brand" value="<?=h((string)$product['brand'])?>" style="width:320px;">
  </label><br><br>

  <label>Név:<br>
    <input name="name" value="<?=h((string)$product['name'])?>" style="width:320px;">
  </label><br><br>

  <label>Kategória:<br>
    <select name="category_id">
      <?php foreach ($cats as $c): ?>
        <option value="<?=$c['id']?>" <?= (int)$product['category_id']===(int)$c['id'] ? 'selected' : '' ?>>
          <?=h($c['name'])?>
        </option>
      <?php endforeach; ?>
    </select>
  </label><br><br>

  <label>Ár (Ft):<br>
    <input type="number" name="price" min="1" value="<?= (int)$product['price'] ?>">
  </label><br><br>

  <label>Készlet:<br>
    <input type="number" name="stock" min="0" value="<?= (int)$product['stock'] ?>">
  </label><br><br>

  <label>Kép URL (opcionális):<br>
    <input name="image_url" value="<?=h((string)$product['image_url'])?>" style="width:420px;">
  </label><br><br>

  <label>Leírás:<br>
    <textarea name="description" rows="5" cols="60"><?=h((string)$product['description'])?></textarea>
  </label><br><br>

  <button type="submit">Mentés</button>
</form>

</body>
</html>
