<?php
/**
 * ============================================================
 *  generate.php — builds a prompt from form fields, calls
 *  Gemini, and returns { subject, body } as JSON.
 * ============================================================
 */

header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

// --- Collect + sanitize inputs ---
$receiverName = trim($input['receiverName'] ?? '');
$receiverEmail = trim($input['receiverEmail'] ?? '');
$content = trim($input['content'] ?? '');
$greetingWord = trim($input['greetingWord'] ?? 'Dear');
$honorific = trim($input['honorific'] ?? 'Sir/Ma\'am');
$timeOfDay = trim($input['timeOfDay'] ?? '');
$type = trim($input['type'] ?? 'formal');
$senderName = trim($input['senderName'] ?? '');
$date = trim($input['date'] ?? date('Y-m-d'));

if (!$receiverEmail || !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid receiver email is required']);
    exit;
}
if (!$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Email content/context is required']);
    exit;
}

// --- Build the prompt for Gemini ---
// Line 1: "Dear Sir," / "Respected Ma'am, Sudeshna,"
$line1 = trim("$greetingWord $honorific" . ($receiverName ? ", $receiverName" : "")) . ",";
// Line 2: "Good morning," (omitted entirely if no time of day chosen)
$line2 = $timeOfDay ? "Good {$timeOfDay}," : "";

$openingLines = $line2 ? "{$line1}\n{$line2}" : $line1;

// Closing: always "Yours sincerely," followed by the sender's full name, each its own line
$closingLines = "Yours sincerely,\n{$senderName}";

$prompt = <<<PROMPT
You are a professional email-writing assistant. Write a {$type} email.

Details:
- The email MUST open with these exact lines, each as its own separate paragraph, word-for-word, before anything else:
{$openingLines}
- Core content / purpose of the email: "{$content}"
- The email MUST end with these exact lines, each as its own separate paragraph, word-for-word, after the message body and nothing else following them:
{$closingLines}
- Date to reference if relevant: {$date}

Rules:
- Keep the tone consistent with a {$type} email throughout.
- Do not invent facts not implied by the content given.
- Keep it concise (roughly 80-180 words) unless the content requires more detail.
- Output body as clean HTML using only <p>, <br>, <b>, <i> tags (no styles, no full HTML document).
- Each of the required opening lines above must be its own separate <p>...</p>, in the same order, exactly as given, before the rest of the message.
- Each of the required closing lines above must be its own separate <p>...</p>, in the same order, exactly as given, as the very last content in the body — never omit these, never substitute a different closing phrase, and never add anything after the sender's name.

Return ONLY a raw JSON object, no markdown fences, no commentary, in exactly this shape:
{"subject": "...", "body": "..."}
PROMPT;

// --- Call Gemini API ---
$url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

$payload = [
    'contents' => [
        ['parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 800,
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => "Could not reach Gemini API: $curlError"]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Gemini API did not return expected content',
        'raw' => $data
    ]);
    exit;
}

$text = trim($data['candidates'][0]['content']['parts'][0]['text']);

// Strip markdown code fences if Gemini added them anyway
$text = preg_replace('/^```json\s*|```$/m', '', $text);
$text = trim($text);

$parsed = json_decode($text, true);

if (!$parsed || !isset($parsed['subject'], $parsed['body'])) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Could not parse Gemini output as JSON',
        'raw_text' => $text
    ]);
    exit;
}

$body = $parsed['body'];

// Safety net: if the model dropped the sign-off, append it ourselves
// rather than trusting the prompt alone.
if ($senderName && stripos($body, $senderName) === false) {
    $body = rtrim($body) . "<p>Yours sincerely,</p><p>" . htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8') . "</p>";
}

echo json_encode([
    'subject' => $parsed['subject'],
    'body' => $body,
    'receiverEmail' => $receiverEmail,
    'receiverName' => $receiverName,
    'senderName' => $senderName,
]);