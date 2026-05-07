<?php

declare(strict_types=1);

require_once __DIR__ . "/_auth.php";

$user = auth_user($pdo);

json_response([
    "success" => true,
    "user" => $user
]);