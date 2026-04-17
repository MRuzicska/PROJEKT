<?php
require __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="navbar">
  <div class="logo">
    <a href="../index.php">Parfum p'Dm Admin</a>
  </div>

  <nav class="nav-links">
    <a href="products.php">Termékek</a>
    <a href="orders.php">Rendelések</a>
  </nav>

  <div class="nav-actions">
    <a href="../index.php">Webshop</a>
    <a href="../logout.php">Kilépés</a>
  </div>
</header>

<main class="admin-page">
  <h1 class="admin-title">Admin felület</h1>
  <p class="admin-subtitle">Itt tudod kezelni a termékeket és a rendeléseket.</p>

  <section class="admin-stats">
    <div class="stat-card">
      <div class="stat-label">Termékkezelés</div>
      <div class="stat-value">📦</div>
      <div class="button-row">
        <a href="products.php" class="btn-primary">Termékek megnyitása</a>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-label">Rendeléskezelés</div>
      <div class="stat-value">🧾</div>
      <div class="button-row">
        <a href="orders.php" class="btn-primary">Rendelések megnyitása</a>
      </div>
    </div>
  </section>

  <section class="admin-card">
    <h2>Gyors műveletek</h2>
    <div class="button-row">
      <a href="product_edit.php" class="btn-primary">+ Új termék</a>
      <a href="products.php" class="btn-secondary">Terméklista</a>
      <a href="orders.php" class="btn-secondary">Rendelések</a>
    </div>
  </section>
</main>

</body>
</html>