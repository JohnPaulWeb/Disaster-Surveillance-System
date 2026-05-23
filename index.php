<?php
$method = $_SERVER["REQUEST_METHOD"];
$url = $_SERVER["REQUEST_URI"];

$uri = parse_url($url, PHP_URL_PATH);

// safer: remove leading slash + folder name if present
$uri = str_replace("/ITP", "", $uri);
$uri = str_replace("/Spotter", "", $uri);

$urlParts = explode("/", trim($uri, "/"));

$route = $urlParts[0] ?? "";

if ($route == "" || $route == "index") {
    require "login.php";
} else {
    echo json_encode([
        "status" => "failed",
        "message" => "Invalid URL"
    ]);
}
?>