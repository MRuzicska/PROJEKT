<?php
declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

/* ===== SZŰRŐ PARAMÉTEREK ===== */
$search = trim($_GET['search'] ?? '');
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$brand = trim($_GET['brand'] ?? '');
$priceRange = trim($_GET['price_range'] ?? '');

/* ===== OLDALANKÉNTI DARABSZÁM ===== */
$allowedPerPage = [25, 50, 100];
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25;

if (!in_array($perPage, $allowedPerPage, true)) {
  $perPage = 25;
}

/* ===== AKTUÁLIS OLDAL ===== */
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
  $page = 1;
}

/* ===== SZŰRÉS ===== */
$where = [];
$params = [];

if ($search !== '') {
  $where[] = '(p.name LIKE ? OR p.brand LIKE ?)';
  $params[] = '%' . $search . '%';
  $params[] = '%' . $search . '%';
}

if ($categoryId > 0) {
  $where[] = 'p.category_id = ?';
  $params[] = $categoryId;
}

if ($brand !== '') {
  $where[] = 'p.brand = ?';
  $params[] = $brand;
}

if ($priceRange !== '') {
  if ($priceRange === '0-20000') {
    $where[] = 'p.price BETWEEN 0 AND 20000';
  } elseif ($priceRange === '20001-40000') {
    $where[] = 'p.price BETWEEN 20001 AND 40000';
  } elseif ($priceRange === '40001-9999999') {
    $where[] = 'p.price >= 40001';
  }
}

$whereSql = '';
if (!empty($where)) {
  $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/* ===== ÖSSZES TALÁLAT ===== */
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products p
    $whereSql
");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($page > $totalPages) {
  $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

/* ===== TERMÉKEK ===== */
$productStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $whereSql
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset
");
$productStmt->execute($params);
$products = $productStmt->fetchAll();

/* ===== SEGÉDADATOK A SZŰRŐKHÖZ ===== */
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand <> '' ORDER BY brand ASC")->fetchAll();
?>
<!doctype html>
<html lang="hu">

<head>
  <meta charset="utf-8">
  <title>Parfüm webshop</title>
  <link rel="stylesheet" href="css/products.css?v=2">
  <link rel="stylesheet" href="css/footer.css?v=2">
</head>

<body>

<header class="navbar">
    <div class="logo">
      <a href="index.php">Parfum p'Dm</a>
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
          <?php if (is_logged_in()): ?>
            <?php
            $userId = (int) $_SESSION['user_id'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $action = post('action');

              if ($action === 'add') {

                $pid = (int) post('product_id');
                $qty = max(1, (int) post('qty', '1'));

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

                // BELÉPETT USER -> adatbázis
                if (is_logged_in()) {

                  $userId = (int) $_SESSION['user_id'];

                  $pdo->prepare(
                    "INSERT INTO cart_items (user_id, product_id, quantity)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)"
                  )->execute([$userId, $pid, $qty, $stock]);

                }

                // VENDEG USER -> session kosar
                else {

                  if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                  }

                  if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid] += $qty;
                  } else {
                    $_SESSION['cart'][$pid] = $qty;
                  }

                  if ($_SESSION['cart'][$pid] > $stock) {
                    $_SESSION['cart'][$pid] = $stock;
                  }
                }

                header("Location: cart.php");
                exit;
              }

              if ($action === 'update') {
                $id = (int) post('item_id');
                $qty = max(1, (int) post('qty', '1'));

                // készlet check a termékhez
                $st = $pdo->prepare(
                  "SELECT ci.product_id, p.stock
           FROM cart_items ci
           JOIN products p ON p.id = ci.product_id
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
            foreach ($items as $it) {
              $total += (int) $it['price'] * (int) $it['quantity'];
            }
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

          <?php else: ?>
            <p>A kosár megtekintéséhez jelentkezz be.</p>
            <a href="login.php" class="btn-small">Bejelentkezés</a>
            <a href="register.php" class="btn-small">Regisztráció</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="profile-wrapper">
        <button onclick="toggleProfile()">👤 Profil</button>
        <div class="dropdown profile-dropdown">
          <?php if (is_logged_in()): ?>
            <p>Üdvözlünk,
              <?= h($_SESSION['username'] ?? 'Felhasználó') ?>!
            </p>
            <?php if (is_admin()): ?>
              <a href="admin/admin.php" class="btn-small">Admin</a>
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

  <main>
    <form method="get">
      <input type="text" name="search" placeholder="Keresés név vagy márka" value="<?= h($search) ?>">

      <select name="category_id">
        <option value="0">Összes kategória</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
            <?= h($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="brand">
        <option value="">Összes márka</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?= h($b['brand']) ?>" <?= $brand === $b['brand'] ? 'selected' : '' ?>>
            <?= h($b['brand']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="price_range">
        <option value="">Összes ár</option>
        <option value="0-20000" <?= $priceRange === '0-20000' ? 'selected' : '' ?>>0 - 20 000 Ft</option>
        <option value="20001-40000" <?= $priceRange === '20001-40000' ? 'selected' : '' ?>>20 001 - 40 000 Ft</option>
        <option value="40001-9999999" <?= $priceRange === '40001-9999999' ? 'selected' : '' ?>>40 001 Ft felett</option>
      </select>

      <select name="per_page">
        <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / oldal</option>
        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / oldal</option>
        <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 / oldal</option>
      </select>

      <input type="hidden" name="page" value="1">
      <button type="submit">Szűrés</button>
    </form>

    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <div class="product-card" onclick="window.location.href='product.php?id=<?= (int) $p['id'] ?>'">
          <div class="product-image">
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= h($p['image_url']) ?>" alt="<?= h($p['name']) ?>">
            <?php endif; ?>
          </div>

          <div class="product-name"><?= h($p['name']) ?></div>
          <div class="product-brand"><?= h($p['brand']) ?></div>
          <div class="product-category">(<?= h($p['category_name'] ?? '-') ?>)</div>
          <div class="product-price">Ár: <?= (int) $p['price'] ?> Ft</div>
          <div class="product-stock">Készlet: <?= (int) $p['stock'] ?></div>

          <?php if ((int) $p['stock'] > 0): ?>
            <form class="product-actions-row" method="post" action="cart.php" onclick="event.stopPropagation();">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
              <input type="number" name="qty" value="1" min="1" max="<?= (int) $p['stock'] ?>" class="qty-input">
              <button type="submit" class="add-to-cart-btn">Kosárba</button>
            </form>
          <?php else: ?>
            <div class="sold-out">Elfogyott</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php
    $queryBase = $_GET;
    ?>

    <div class="pagination">
      <?php $queryBase['page'] = 1; ?>
      <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
        href="<?= $page > 1 ? '?' . http_build_query($queryBase) : '#' ?>">
        « Első
      </a>

      <?php $queryBase['page'] = max(1, $page - 1); ?>
      <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
        href="<?= $page > 1 ? '?' . http_build_query($queryBase) : '#' ?>">
        ‹ Előző
      </a>

      <span class="page-info">
        Oldal <?= $page ?> / <?= $totalPages ?>
      </span>

      <?php $queryBase['page'] = min($totalPages, $page + 1); ?>
      <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
        href="<?= $page < $totalPages ? '?' . http_build_query($queryBase) : '#' ?>">
        Következő ›
      </a>

      <?php $queryBase['page'] = $totalPages; ?>
      <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
        href="<?= $page < $totalPages ? '?' . http_build_query($queryBase) : '#' ?>">
        Utolsó »
      </a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-container">

      <div class="footer-column">
        <h3>Parfum p'Dm</h3>
        <p>
          Fedezd fel prémium parfümkínálatunkat női, férfi és unisex illatokkal.
        </p>
      </div>

      <div class="footer-column">
        <h3>Kapcsolat</h3>
        <p>Email: info@parfumpdm.hu</p>
        <p>Telefon: +36 20 123 4567</p>
        <p>Cím: 1182 Budapest, Illat utca 12.</p>
      </div>

      <div class="footer-column">
        <h3>Információk</h3>
        <a href="products.php">Összes parfüm</a>
        <a href="cart.php">Kosár</a>
        <a href="orders.php">Rendeléseim</a>
        <a href="login.php">Bejelentkezés</a>
      </div>

      <div class="footer-column">
        <h3>Vásárlás</h3>
        <p>Biztonságos rendelés</p>
        <p>Gyors kiszállítás</p>
        <p>Minőségi termékek</p>
        <p>Ügyfélszolgálat minden hétköznap</p>
      </div>

    </div>

    <div class="footer-bottom">
      <p>&copy;
        <?= date('Y') ?> Parfum p'Dm – Minden jog fenntartva.
      </p>
    </div>
  </footer>

  <?php require __DIR__ . '/includes/footer.php'; ?>

  <script>
    function toggleProfile() {
      document.querySelector('.profile-dropdown').classList.toggle('show');
    }
    function toggleCart() {
        document.querySelector('.cart-dropdown').classList.toggle('show');
      }

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.profile-wrapper')) {
        const dropdown = document.querySelector('.profile-dropdown');
        if (dropdown) {
          dropdown.classList.remove('show');
        }
      }
    });
  </script>

</body>

</html>