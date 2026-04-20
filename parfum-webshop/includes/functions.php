<?php
declare(strict_types=1);

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function post(string $key, string $default = ''): string {
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function get_int(string $key, int $default = 0): int {
  return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

function ensure_session_cart(): void {
  if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }
}

function cart_session_key(int $productId, int $variantId): string {
  return $productId . '_' . $variantId;
}

function cart_merge_session_into_user(PDO $pdo, int $userId): void {
  ensure_session_cart();

  if (empty($_SESSION['cart'])) {
    return;
  }

  $selectExisting = $pdo->prepare("
    SELECT id, quantity
    FROM cart_items
    WHERE user_id = ? AND product_id = ? AND variant_id = ?
  ");

  $updateExisting = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE id = ?
  ");

  $insertNew = $pdo->prepare("
    INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
    VALUES (?, ?, ?, ?)
  ");

  $variantStmt = $pdo->prepare("
    SELECT stock
    FROM product_variants
    WHERE id = ? AND product_id = ?
    LIMIT 1
  ");

  foreach ($_SESSION['cart'] as $item) {
    $productId = (int)($item['product_id'] ?? 0);
    $variantId = (int)($item['variant_id'] ?? 0);
    $quantity = max(1, (int)($item['quantity'] ?? 1));

    if ($productId <= 0 || $variantId <= 0) {
      continue;
    }

    $variantStmt->execute([$variantId, $productId]);
    $variant = $variantStmt->fetch();

    if (!$variant) {
      continue;
    }

    $stock = (int)$variant['stock'];

    if ($stock <= 0) {
      continue;
    }

    $selectExisting->execute([$userId, $productId, $variantId]);
    $existing = $selectExisting->fetch();

    if ($existing) {
      $newQty = min((int)$existing['quantity'] + $quantity, $stock);
      $updateExisting->execute([$newQty, (int)$existing['id']]);
    } else {
      $insertNew->execute([$userId, $productId, $variantId, min($quantity, $stock)]);
    }
  }

  $_SESSION['cart'] = [];
}
