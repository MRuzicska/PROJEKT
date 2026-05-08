<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$msg = '';

$stmt = $pdo->prepare("
    SELECT orders.*, users.email
    FROM orders
    LEFT JOIN users ON users.id = orders.user_id
    WHERE orders.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Rendelés nem található.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post('shipping_name');
    $address = post('shipping_address');
    $phone = post('shipping_phone');
    $email = post('shipping_email');

    $pdo->prepare("
        UPDATE orders 
        SET shipping_name=?, shipping_address=?, shipping_phone=?, shipping_email=? 
        WHERE id=?
    ")->execute([$name, $address, $phone, $email, $id]);

    $msg = "Rendelés adatai frissítve.";
    $stmt->execute([$id]);
    $order = $stmt->fetch();
}
?>
<!doctype html>
<html lang="hu">

<head>
    <meta charset="utf-8">
    <title>Rendelés szerkesztése</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }

        .form-group input[type="text"],
        .form-group input[type="email"] {
            display: block;
            width: 100%;
            padding: 10px;
            background: #122b3a;
            border: 1px solid #4b6473;
            color: #fff;
            border-radius: 5px;
        }
    </style>
    </style>
</head>

<body>

    <header class="navbar">
        <div class="logo"><a href="index.php">Admin Panel</a></div>
        <nav class="nav-links"><a href="orders.php">Vissza a rendelésekhez</a></nav>
    </header>

    <main class="admin-page">
        <section class="admin-card">
            <h2>#<?= $id ?> Rendelés adatainak módosítása</h2>

            <?php if ($msg): ?>
                <div class="alert-success"><?= h($msg) ?></div> <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Szállítási név:</label>
                    <input type="text" name="shipping_name" value="<?= h($order['shipping_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Cím:</label>
                    <input type="text" name="shipping_address" value="<?= h($order['shipping_address']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Telefonszám:</label>
                    <input type="text" name="shipping_phone" value="<?= h($order['shipping_phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="shipping_email">E-mail:</label>
                    <input id="shipping_email"type="text" name="shipping_email" 
                    value="<?= h($order['shipping_email'] ?? $order['email'] ?? '') ?>" placeholder="pelda@email.com" required>
                </div>
                <button type="submit" class="btn-primary">Adatok mentése</button>
            </form>
        </section>
    </main>

</body>

</html>