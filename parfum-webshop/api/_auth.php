<?php

declare(strict_types=1);

require_once __DIR__ . "/_bootstrap.php";

function get_bearer_token(): ?string
{
    $headers = getallheaders();

    if (!isset($headers["Authorization"])) {
        return null;
    }

    $auth = $headers["Authorization"];

    if (!str_starts_with($auth, "Bearer ")) {
        return null;
    }

    return trim(substr($auth, 7));
}

function auth_user(PDO $pdo): array
{
    $token = get_bearer_token();

    if (!$token) {
        json_response([
            "success" => false,
            "message" => "Hianyzik a token"
        ], 401);
    }

    $stmt = $pdo->prepare("
        SELECT users.id, users.name, users.email, users.role
        FROM api_tokens
        INNER JOIN users ON users.id = api_tokens.user_id
        WHERE api_tokens.token = ?
        LIMIT 1
    ");

    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response([
            "success" => false,
            "message" => "Ervenytelen token"
        ], 401);
    }

    return $user;
}

function require_admin_api(array $user): void
{
    if ($user["role"] !== "admin") {
        json_response([
            "success" => false,
            "message" => "Nincs jogosultsag"
        ], 403);
    }
}