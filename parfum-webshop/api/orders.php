<?php

declare(strict_types=1);

require_once __DIR__ . "/_auth.php";

$user = auth_user($pdo);
$userId = (int) $user["id"];

$stmt = $pdo->prepare("
    SELECT 
        id,
        total_price,
        status,
        shipping_name,
        shipping_address,
        shipping_phone,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    "success" => true,
    "orders" => $orders
]);