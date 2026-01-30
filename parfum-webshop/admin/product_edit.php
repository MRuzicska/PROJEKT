<?php
declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// kategóriák
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// engedélyezett kiszerelések
$allowedSizes = [30, 50, 75, 100, 200];

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

// meglévő variantok betöltése (product_variants)
$currentVariants = []; // [size_ml => ['price'=>..., 'stock'=>...]]
if ($id > 0) {
  $vst = $pdo->prepare("SELECT size_ml, price, stock FROM product_variants WHERE product_id=?");
  $vst->execute([$id]);
  foreach ($vst->fetchAll() as $v) {
    $currentVariants[(int)$v['size_ml']] = [
      'price' => (int)$v['price'],
      'stock' => (int)$v['stock']
    ];
  }
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

  // kiszerelések a formból
  $sizesSelected = $_POST['sizes'] ?? [];           // pl ['30'=>'1','100'=>'1']
  $variantPrice  = $_POST['variant_price'] ?? [];   // pl ['30'=>'9990', ...]
  $variantStock  = $_POST['variant_stock'] ?? [];   // pl ['30'=>'5', ...]

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
      // tranzakció: termék + variantok együtt
      $pdo->beginTransaction();
      try {
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

          $id = (int)$pdo->lastInsertId();
        }

        // Variantok mentése: bepipált → upsert, nem pipált → delete
        foreach ($allowedSizes as $sz) {
          $szKey = (string)$sz;
          $isChecked = isset($sizesSelected[$szKey]);

          if ($isChecked) {
            $vp = isset($variantPrice[$szKey]) ? (int)$variantPrice[$szKey] : 0;
            $vs = isset($variantStock[$szKey]) ? (int)$variantStock[$szKey] : 0;

            if ($vp <= 0) {
              throw new Exception("A(z) {$sz} ml kiszerelés ára legyen pozitív.");
            }
            if ($vs < 0) {
              throw new Exception("A(z) {$sz} ml kiszerelés készlete nem lehet negatív.");
            }

            // upsert (MySQL): ha már van ugyanarra a méretre, frissít
            $pdo->prepare(
              "INSERT INTO product_variants (product_id, size_ml, price, stock)
               VALUES (?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE price=VALUES(price), stock=VALUES(stock)"
            )->execute([$id, $sz, $vp, $vs]);

          } else {
            // ha nincs bepipálva → töröljük (ha volt)
            $pdo->prepare(
              "DELETE FROM product_variants WHERE product_id=? AND size_ml=?"
            )->execute([$id, $sz]);
          }
        }

        $pdo->commit();
        header("Location: products.php");
        exit;

      } catch (Throwable $e) {
        $pdo->rollBack();
        $error = "Mentési hiba: " . $e->getMessage();
      }
    }
  }

  // ha hiba, töltsük vissza a formba (termék mezők)
  $product = [
    'name' => $name,
    'brand' => $brand,
    'description' => $description,
    'price' => $price,
    'stock' => $stock,
    'category_id' => $category_id,
    'image_url' => $image_url
  ];

  // variant visszatöltés
  $currentVariants = [];
  foreach ($allowedSizes as $sz) {
    $szKey = (string)$sz;
    if (isset($sizesSelected[$szKey])) {
      $currentVariants[$sz] = [
        'price' => (int)($variantPrice[$szKey] ?? 0),
        'stock' => (int)($variantStock[$szKey] ?? 0)
      ];
    }
  }
}
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Admin - <?= $id>0 ? 'Szerkesztés' : 'Új termék' ?></title></head>
<body>
<h1>Admin – <?= $id>0 ? 'Termék szerkesztése' : 'Új termék' ?></h1>
<p><a href="products.php">← Vissza a termékekhez</a></p>

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

  <label>Ár (Ft) – alap (opcionális, ha a variánsok eltérnek):<br>
    <input type="number" name="price" min="1" value="<?= (int)$product['price'] ?>">
  </label><br><br>

  <label>Készlet – alap (opcionális):<br>
    <input type="number" name="stock" min="0" value="<?= (int)$product['stock'] ?>">
  </label><br><br>

  <label>Kép URL (opcionális):<br>
    <input name="image_url" value="<?=h((string)$product['image_url'])?>" style="width:420px;">
  </label><br><br>

  <label>Leírás:<br>
    <textarea name="description" rows="5" cols="60"><?=h((string)$product['description'])?></textarea>
  </label><br><br>

  <hr>

  <h2>Kiszerelések (pipáld be, amit szeretnél)</h2>
  <p style="max-width:720px;">
    Itt tudod beállítani, hogy milyen kiszerelésben legyen elérhető a parfüm (30/50/75/100/200 ml),
    és mindegyikhez külön árat és készletet adhatsz meg.
  </p>

  <table border="1" cellpadding="6">
    <tr>
      <th>Elérhető?</th>
      <th>Méret</th>
      <th>Ár (Ft)</th>
      <th>Készlet</th>
    </tr>
    <?php foreach ($allowedSizes as $sz): ?>
      <?php
        $checked = isset($currentVariants[$sz]);
        $pval = $checked ? (int)$currentVariants[$sz]['price'] : 0;
        $sval = $checked ? (int)$currentVariants[$sz]['stock'] : 0;
      ?>
      <tr>
        <td style="text-align:center;">
          <input type="checkbox" name="sizes[<?= $sz ?>]" value="1" <?= $checked ? 'checked' : '' ?>>
        </td>
        <td><strong><?= $sz ?> ml</strong></td>
        <td>
          <input type="number" name="variant_price[<?= $sz ?>]" min="0" value="<?= $pval ?>" style="width:140px;">
        </td>
        <td>
          <input type="number" name="variant_stock[<?= $sz ?>]" min="0" value="<?= $sval ?>" style="width:100px;">
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <br>
  <button type="submit">Mentés</button>
</form>

</body>
</html>
