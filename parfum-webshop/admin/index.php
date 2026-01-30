<?php
require __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!doctype html>
<html lang="hu">
<head><meta charset="utf-8"><title>Admin</title></head>
<body>
<h1>Admin</h1>
<ul>
  <li><a href="products.php">Termékek</a></li>
  <li><a href="orders.php">Rendelések</a></li>
</ul>
<p><a href="../index.php">← Webshop</a></p>
</body>
</html>
