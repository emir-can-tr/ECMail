<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ECMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new ECMail();
    $result = $mail->handleFormSubmit();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek yöntemi.'], JSON_UNESCAPED_UNICODE);
}
?>
