<?php

declare(strict_types=1);

require_once __DIR__ . "/_auth.php";

$user = auth_user($pdo);
$userId = (int) $user["id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response([
        "success" => false,
        "message" => "Csak POST keres engedelyezett"
    ], 405);
}

$data = get_json_input();

$shippingName = trim($data["shipping_name"] ?? "");
$shippingAddress = trim($data["shipping_address"] ?? "");
$shippingPhone = trim($data["shipping_phone"] ?? "");

if ($shippingName === "" || $shippingAddress === "" || $shippingPhone === "") {
    json_response([
        "success" => false,
        "message" => "Szallitasi adatok kitoltese kotelezo"
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT 
        ci.id AS cart_item_id,
        ci.product_id,
        ci.variant_id,
        ci.quantity,
        p.price AS product_price,
        p.stock AS product_stock,
        pv.price AS variant_price,
        pv.stock AS variant_stock
    FROM cart_items ci
    INNER JOIN products p ON p.id = ci.product_id
    LEFT JOIN product_variants pv ON pv.id = ci.variant_id
    WHERE ci.user_id = ?
");

$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    json_response([
        "success" => false,
        "message" => "A kosar ures"
    ], 400);
}

$total = 0;

foreach ($items as $item) {
    $price = $item["variant_price"] !== null ? (int) $item["variant_price"] : (int) $item["product_price"];
    $quantity = (int) $item["quantity"];
    $stock = $item["variant_id"] !== null ? (int) $item["variant_stock"] : (int) $item["product_stock"];

    if ($stock < $quantity) {
        json_response([
            "success" => false,
            "message" => "Nincs eleg keszleten az egyik termekbol"
        ], 400);
    }

    $total += $price * $quantity;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO orders 
            (user_id, total_price, status, shipping_name, shipping_address, shipping_phone)
        VALUES 
            (?, ?, 'NEW', ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $total,
        $shippingName,
        $shippingAddress,
        $shippingPhone
    ]);

    $orderId = (int) $pdo->lastInsertId();

    foreach ($items as $item) {
        $productId = (int) $item["product_id"];
        $variantId = $item["variant_id"] !== null ? (int) $item["variant_id"] : null;
        $quantity = (int) $item["quantity"];
        $price = $item["variant_price"] !== null ? (int) $item["variant_price"] : (int) $item["product_price"];
        $lineTotal = $price * $quantity;

        $stmt = $pdo->prepare("
            INSERT INTO order_items
                (order_id, product_id, unit_price, quantity, line_total)
            VALUES
                (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $orderId,
            $productId,
            $price,
            $quantity,
            $lineTotal
        ]);

        if ($variantId) {
            $stmt = $pdo->prepare("
                UPDATE product_variants
                SET stock = stock - ?
                WHERE id = ?
            ");

            $stmt->execute([$quantity, $variantId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
            ");

            $stmt->execute([$quantity, $productId]);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();

    json_response([
        "success" => true,
        "message" => "Sikeres rendeles",
        "order_id" => $orderId,
        "total" => $total
    ], 201);

} catch (Throwable $e) {
    $pdo->rollBack();

    json_response([
        "success" => false,
        "message" => "Rendelesi hiba",
        "error" => $e->getMessage()
    ], 500);
}