<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/settings.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo json_encode([
    'nowMs' => (int) floor(microtime(true) * 1000),
    'targetMs' => countdown_target()->getTimestamp() * 1000,
    'timezone' => 'Asia/Bangkok',
], JSON_THROW_ON_ERROR);
