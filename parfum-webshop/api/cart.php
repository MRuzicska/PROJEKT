<?php

declare(strict_types=1);

require_once __DIR__ . "/_auth.php";

$user = auth_user($pdo);
$userId = (int) $user["id"];

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $pdo->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.product_id,
            ci.variant_id,
            ci.quantity,
            p.name,
            p.brand,
            p.image_url,
            COALESCE(pv.price, p.price) AS price,
            COALESCE(pv.stock, p.stock) AS stock,
            pv.size_ml,
            (COALESCE(pv.price, p.price) * ci.quantity) AS line_total
        FROM cart_items ci
        INNER JOIN products p ON p.id = ci.product_id
        LEFT JOIN product_variants pv ON pv.id = ci.variant_id
        WHERE ci.user_id = ?
        ORDER BY ci.id DESC
    ");

    $stmt->execute([$userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;

    foreach ($items as $item) {
        $total += (int) $item["line_total"];
    }

    json_response([
        "success" => true,
        "items" => $items,
        "total" => $total
    ]);
}

if ($method === "POST") {
    $data = get_json_input();

    $productId = (int) ($data["product_id"] ?? 0);
    $variantId = isset($data["variant_id"]) ? (int) $data["variant_id"] : null;
    $quantity = (int) ($data["quantity"] ?? 1);

    if ($productId <= 0 || $quantity <= 0) {
        json_response([
            "success" => false,
            "message" => "Ervenytelen adat"
        ], 400);
    }

    if ($variantId) {
        $stmt = $pdo->prepare("
            SELECT id, product_id, stock
            FROM product_variants
            WHERE id = ? AND product_id = ?
            LIMIT 1
        ");

        $stmt->execute([$variantId, $productId]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            json_response([
                "success" => false,
                "message" => "Varians nem talalhato"
            ], 404);
        }

        if ((int) $variant["stock"] < $quantity) {
            json_response([
                "success" => false,
                "message" => "Nincs eleg keszleten"
            ], 400);
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT id, stock
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            json_response([
                "success" => false,
                "message" => "Termek nem talalhato"
            ], 404);
        }

        if ((int) $product["stock"] < $quantity) {
            json_response([
                "success" => false,
                "message" => "Nincs eleg keszleten"
            ], 400);
        }
    }

    if ($variantId) {
        $stmt = $pdo->prepare("
            SELECT id, quantity
            FROM cart_items
            WHERE user_id = ? AND product_id = ? AND variant_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId, $productId, $variantId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, quantity
            FROM cart_items
            WHERE user_id = ? AND product_id = ? AND variant_id IS NULL
            LIMIT 1
        ");

        $stmt->execute([$userId, $productId]);
    }

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQuantity = (int) $existing["quantity"] + $quantity;

        $stmt = $pdo->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE id = ?
        ");

        $stmt->execute([$newQuantity, $existing["id"]]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$userId, $productId, $variantId, $quantity]);
    }

    json_response([
        "success" => true,
        "message" => "Termek kosarba rakva"
    ], 201);
}

if ($method === "PUT") {
    $data = get_json_input();

    $cartItemId = (int) ($data["cart_item_id"] ?? 0);
    $quantity = (int) ($data["quantity"] ?? 0);

    if ($cartItemId <= 0 || $quantity <= 0) {
        json_response([
            "success" => false,
            "message" => "Ervenytelen adat"
        ], 400);
    }

    $stmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$quantity, $cartItemId, $userId]);

    json_response([
        "success" => true,
        "message" => "Kosar frissitve"
    ]);
}

if ($method === "DELETE") {
    $data = get_json_input();

    $cartItemId = (int) ($data["cart_item_id"] ?? 0);

    if ($cartItemId <= 0) {
        json_response([
            "success" => false,
            "message" => "Ervenytelen kosar elem ID"
        ], 400);
    }

    $stmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$cartItemId, $userId]);

    json_response([
        "success" => true,
        "message" => "Termek torolve a kosarbol"
    ]);
}

json_response([
    "success" => false,
    "message" => "Nem tamogatott keres"
], 405);