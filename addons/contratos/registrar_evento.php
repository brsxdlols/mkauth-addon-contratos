<?php
header('Content-Type: application/json; charset=utf-8');

$allowed = array('page_ready', 'signature_saved', 'camera_ready', 'selfie_ready', 'pdf_started', 'pdf_ready', 'upload_started', 'upload_failed');
$event = trim((string) ($_POST['event'] ?? ''));
$uuid = trim((string) ($_POST['uuid'] ?? ''));
$detail = trim((string) ($_POST['detail'] ?? ''));

if (!in_array($event, $allowed, true) || !preg_match('/^[A-Za-z0-9._-]{1,80}$/', $uuid)) {
    http_response_code(400);
    echo json_encode(array('status' => 'error'));
    exit;
}

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$entry = array(
    'time' => date('c'),
    'event' => $event,
    'uuid' => $uuid,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 350),
    'detail' => substr($detail, 0, 350),
);
$ok = @file_put_contents($logDir . '/signing.log', json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
echo json_encode(array('status' => $ok === false ? 'error' : 'success'));
