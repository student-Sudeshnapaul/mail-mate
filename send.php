<?php

header('Content-Type: application/json');
require_once 'config.php';
require_once 'SmtpMailer.php';

$input = json_decode(file_get_contents('php://input'), true);

$receiverEmail = trim($input['receiverEmail'] ?? '');
$receiverName = trim($input['receiverName'] ?? '');
$subject = trim($input['subject'] ?? '');
$body = trim($input['body'] ?? '');

if (!$receiverEmail || !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid receiver email is required']);
    exit;
}
if (!$subject || !$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Subject and body are required']);
    exit;
}

$mailer = new SmtpMailer(
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USER,
    SMTP_APP_PASSWORD,
    SMTP_FROM_NAME
);

$result = $mailer->send($receiverEmail, $receiverName, $subject, $body);

if (!$result['success']) {
    http_response_code(500);
}

echo json_encode($result);
