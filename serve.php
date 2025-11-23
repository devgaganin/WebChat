<?php
require_once 'config.php';
require_once 'files.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Invalid request');
}

serveFile($token);
