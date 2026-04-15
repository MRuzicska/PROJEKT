<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$cat = get_int('category_id', 0);
$brand = isset($_GET['brand']) ? trim((string)$_GET['brand']) : '';
$price_range = isset($_GET['price_range']) ? trim((string)$_GET['price_range']) : '';

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
  $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
  $params[] = "%{$search}%";
  $params[] = "%{$search}%";
}
if ($cat > 0) {
  $sql .= " AND p.category_id = ?";
  $params[] = $cat;
}
if ($brand !== '') {
  $sql .= " AND p.brand = ?";
  $params[] = $brand;
}
if ($price_range !== '') {
  if ($price_range === '0-10000') {
    $sql .= " AND p.price BETWEEN 0 AND 10000";
  } elseif ($price_range === '10000-20000') {
    $sql .= " AND p.price BETWEEN 10000 AND 20000";
  } elseif ($price_range === '20000-30000') {
    $sql .= " AND p.price BETWEEN 20000 AND 30000";
  } elseif ($price_range === '30000+') {
    $sql .= " AND p.price >= 30000";
  }
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand")->fetchAll();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Parfüm webshop</title>
<link rel="stylesheet" href="css/products.css"></head>
<body>

<header class="navbar">
    <div class="logo">
      <img src="images/logo-placeholder.png" alt="">
      <a href="../index.php">Parfum p'Dm</a>
    </div>
    <nav class="nav-links desktop-menu">
      <a href="products.php">Összes parfüm</a>
      <a href="products.php?category_id=2">Csak férfi</a>
      <a href="products.php?category_id=1">Csak női</a>
      <a href="products.php?category_id=3">Csak unisex</a>
    </nav>


    <div class="nav-actions">
      <div class="cart-wrapper">
        <button onclick="toggleCart()">🛒 Kosár</button>
        <div class="dropdown cart-dropdown">
          <?php require_login();
          $userId = (int) $_SESSION['user_id'];

          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = post('action');

            if ($action === 'add') {
              $pid = (int) post('product_id');
              $qty = max(1, (int) post('qty', '1'));

              // készlet ellenőrzés
              $st = $pdo->prepare("SELECT stock FROM products WHERE id=?");
              $st->execute([$pid]);
              $row = $st->fetch();
              if (!$row) {
                die("Nincs ilyen termék.");
              }
              $stock = (int) $row['stock'];
              if ($qty > $stock) {
                die("Nincs elég készlet.");
              }

              // upsert
              $pdo->prepare(
                "INSERT INTO cart_items (user_id, product_id, quantity)
       VALUES (?, ?, ?)
       ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)"
              )->execute([$userId, $pid, $qty, $stock]);

              header('Location: cart.php');
              exit;
            }

            if ($action === 'update') {
              $id = (int) post('item_id');
              $qty = max(1, (int) post('qty', '1'));

              // készlet check a termékhez
              $st = $pdo->prepare(
                "SELECT ci.product_id, p.stock
       FROM cart_items ci JOIN products p ON p.id=ci.product_id
       WHERE ci.id=? AND ci.user_id=?"
              );
              $st->execute([$id, $userId]);
              $row = $st->fetch();
              if (!$row) {
                die("Nincs ilyen kosár tétel.");
              }
              if ($qty > (int) $row['stock']) {
                die("Nincs elég készlet.");
              }

              $pdo->prepare("UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?")
                ->execute([$qty, $id, $userId]);

              header('Location: cart.php');
              exit;
            }

            if ($action === 'delete') {
              $id = (int) post('item_id');
              $pdo->prepare("DELETE FROM cart_items WHERE id=? AND user_id=?")
                ->execute([$id, $userId]);
              header('Location: cart.php');
              exit;
            }
          }

          $stmt = $pdo->prepare(
            "SELECT ci.id, ci.quantity, p.name, p.brand, p.price, p.stock
   FROM cart_items ci
   JOIN products p ON p.id = ci.product_id
   WHERE ci.user_id = ?
   ORDER BY ci.id DESC"
          );
          $stmt->execute([$userId]);
          $items = $stmt->fetchAll();

          $total = 0;
          foreach ($items as $it)
            $total += (int) $it['price'] * (int) $it['quantity'];
          ?>



          <?php if (!$items): ?>
            <p>A kosár üres.</p>
          <?php else: ?>
            <table border="1" cellpadding="6">
              <tr>
                <th>Termék</th>
                <th>Ár</th>
                <th>Mennyiség</th>
                <th>Részösszeg</th>
                <th>Művelet</th>
              </tr>
              <?php foreach ($items as $it):
                $sub = (int) $it['price'] * (int) $it['quantity'];
                ?>
                <tr>
                  <td><?= h($it['brand']) ?> – <?= h($it['name']) ?></td>
                  <td><?= (int) $it['price'] ?> Ft</td>
                  <td>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                      <input type="number" name="qty" min="1" max="<?= $it['stock'] ?>" value="<?= $it['quantity'] ?>"
                        style="width:70px;">
                      <button type="submit">Mentés</button>
                    </form>
                  </td>
                  <td><?= $sub ?> Ft</td>
                  <td>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Törlöd?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                      <button type="submit">Törlés</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>

            <p><strong>Összesen: <?= $total ?> Ft</strong></p>
            <p><a href="checkout.php">Tovább a rendeléshez →</a></p>
          <?php endif; ?>
          <a href="cart.php" class="btn-small">Kosár megnyitása</a>
        </div>
      </div>
      <div class="profile-wrapper">
        <button onclick="toggleProfile()">👤 Profil</button>
        <div class="dropdown profile-dropdown">
          <?php if (is_logged_in()): ?>
            <p>Üdvözlünk, <?= h($_SESSION['username'] ?? 'Felhasználó') ?>!</p>
            <?php if (is_admin()): ?>
              <a href="admin.php" class="btn-small">Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-small">Kijelentkezés</a>
          <?php else: ?>
            <a href="login.php" class="btn-small">Bejelentkezés</a>
            <a href="register.php" class="btn-small">Regisztráció</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>





<form method="get">
  <input name="search" placeholder="Keresés név vagy márka szerint..." value="<?=h($search)?>">
  <select name="category_id">
    <option value="0">Összes kategória</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?=$c['id']?>" <?=$cat===(int)$c['id']?'selected':''?>><?=h($c['name'])?></option>
    <?php endforeach; ?>
  </select>
  <select name="brand">
    <option value="">Összes márka</option>
    <?php foreach ($brands as $b): ?>
      <option value="<?=h($b['brand'])?>" <?=$brand===$b['brand']?'selected':''?>><?=h($b['brand'])?></option>
    <?php endforeach; ?>
  </select>
  <select name="price_range">
    <option value="">Összes ár</option>
    <option value="0-10000" <?=$price_range==='0-10000'?'selected':''?>>0 - 10 000 Ft</option>
    <option value="10000-20000" <?=$price_range==='10000-20000'?'selected':''?>>10 000 - 20 000 Ft</option>
    <option value="20000-30000" <?=$price_range==='20000-30000'?'selected':''?>>20 000 - 30 000 Ft</option>
    <option value="30000+" <?=$price_range==='30000+'?'selected':''?>>30 000 Ft felett</option>
  </select>
  <button type="submit">Szűrés</button>
</form>

<hr>

<div class="product-grid">
<?php foreach ($products as $p): ?>
  <div class="product-card">
  <a href="product.php?id=<?= (int)$p['id'] ?>" class="card-link">
    <div class="product-image">
      <?php if (!empty($p['image_url'])): ?>
        <img src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>">
      <?php endif; ?>
    </div>
    <div class="product-name"><?=h($p['name'])?></div>
    <div class="product-brand"><?=h($p['brand'])?></div>
    <div class="product-category">(<?=h($p['category_name'])?>)</div>
    <div class="product-price">Ár: <?= (int)$p['price'] ?> Ft</div>
    <div class="product-stock">Készlet: <?= (int)$p['stock'] ?></div>
    <div class="product-links">
      <a href="product.php?id=<?= (int)$p['id'] ?>">Részletek</a>
    </div>
    <?php if (is_logged_in()): ?>
      <?php if ((int)$p['stock'] > 0): ?>
        <form class="add-to-cart-form" method="post" action="cart.php">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?=$p['id']?>">
          <input type="number" name="qty" value="1" min="1" max="<?=$p['stock']?>">
          <button type="submit">Kosárba</button>
        </form>
      <?php else: ?>
        <div class="sold-out">Elfogyott</div>
      <?php endif; ?>
    <?php else: ?>
      <div class="sold-out">(Kosárhoz jelentkezz be)</div>
    <?php endif; ?>
    </a>
  </div>
<?php endforeach; ?>
</div>

<script>
    function toggleMenu() {
      document.getElementById('mobileMenu').classList.toggle('show');
    }
    function toggleCart() {
      document.querySelector('.cart-dropdown').classList.toggle('show');
    }

    function toggleProfile() {
      document.querySelector('.profile-dropdown').classList.toggle('show');
    }
  </script>

</body>
</html>
