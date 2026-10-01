<?php
$requested = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_file($requested)) return false;
require dirname(__DIR__) . '/app.php';
