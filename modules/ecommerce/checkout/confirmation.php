<?php
require_once __DIR__ . '/../../../includes/functions.php';
$order = $_GET['order'] ?? '';
$url = '/confirmation.php' . ($order ? '?order=' . urlencode($order) : '');
header("Location: $url");
exit;
