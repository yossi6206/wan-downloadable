<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$RESEND_API_KEY = 're_bZvNJ1SX_L67Wn9RrjvNGaat2YSmkN2jf';
$TO_EMAIL       = 'info@allintech.co.il';
$FROM_EMAIL     = 'noreply@allintech.co.il';

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$phone || !$email) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'שדות חובה חסרים']);
    exit;
}

$serviceLabels = [
    'web'       => 'בניית אתר',
    'app'       => 'פיתוח אפליקציה',
    'design'    => 'עיצוב UX/UI',
    'branding'  => 'מיתוג',
    'marketing' => 'שיווק דיגיטלי',
    'other'     => 'אחר',
];
$serviceLabel = $serviceLabels[$service] ?? ($service ?: '—');

$messageRow = $message
    ? '<tr><td style="padding:10px 0;color:#555;font-weight:bold;vertical-align:top;">פרטי פרויקט</td>
       <td style="padding:10px 0;color:#222;white-space:pre-wrap;">' . htmlspecialchars($message) . '</td></tr>'
    : '';

$htmlBody = '
<div dir="rtl" style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a73e8,#0d47a1);padding:24px 32px;">
    <h2 style="color:#fff;margin:0;font-size:20px;">פנייה חדשה מהאתר</h2>
  </div>
  <div style="padding:28px 32px;background:#fff;">
    <table style="width:100%;border-collapse:collapse;">
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555;width:130px;font-weight:bold;">שם מלא</td>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#222;">' . htmlspecialchars($name) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555;font-weight:bold;">טלפון</td>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#222;">' . htmlspecialchars($phone) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555;font-weight:bold;">אימייל</td>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#222;">' . htmlspecialchars($email) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555;font-weight:bold;">סוג שירות</td>
        <td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#222;">' . htmlspecialchars($serviceLabel) . '</td>
      </tr>
      ' . $messageRow . '
    </table>
  </div>
  <div style="background:#f8f9fa;padding:14px 32px;text-align:center;font-size:12px;color:#888;">נשלח אוטומטית מהאתר</div>
</div>';

$payload = json_encode([
    'from'     => 'אתר WAN <' . $FROM_EMAIL . '>',
    'to'       => [$TO_EMAIL],
    'reply_to' => $email,
    'subject'  => 'פנייה חדשה מהאתר – ' . $name,
    'html'     => $htmlBody,
]);

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'שגיאה: cURL לא זמין בשרת']);
    exit;
}

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $RESEND_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'שגיאת חיבור: ' . $curlError]);
    exit;
}

if ($curlError || !($httpCode >= 200 && $httpCode < 300)) {
    $body = json_decode($response, true);
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => $curlError ?: ($body['message'] ?? $body['name'] ?? 'שגיאה ' . $httpCode),
    ]);
    exit;
}

// מייל אישור ללקוח
$clientHtml = '
<div dir="rtl" style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a73e8,#0d47a1);padding:28px 32px;text-align:center;">
    <h2 style="color:#fff;margin:0;font-size:22px;">הבקשה שלך התקבלה!</h2>
  </div>
  <div style="padding:32px;background:#fff;text-align:right;">
    <p style="font-size:16px;color:#333;">שלום <strong>' . htmlspecialchars($name) . '</strong>,</p>
    <p style="font-size:15px;color:#555;line-height:1.7;">
      תודה שפנית אלינו! קיבלנו את הבקשה שלך ונחזור אליך תוך 24 שעות עם הצעת מחיר מותאמת.
    </p>
    <div style="background:#f0f6ff;border-right:4px solid #1a73e8;padding:16px 20px;margin:24px 0;border-radius:4px;">
      <p style="margin:0;color:#1a73e8;font-weight:bold;">פרטי הפנייה שלך:</p>
      <p style="margin:8px 0 0;color:#333;">שירות: <strong>' . htmlspecialchars($serviceLabel) . '</strong></p>
      ' . ($message ? '<p style="margin:8px 0 0;color:#333;">פרטים: ' . htmlspecialchars($message) . '</p>' : '') . '
    </div>
    <p style="font-size:14px;color:#888;">לכל שאלה ניתן לפנות ישירות ל: <a href="mailto:' . $TO_EMAIL . '" style="color:#1a73e8;">' . $TO_EMAIL . '</a></p>
  </div>
  <div style="background:#f8f9fa;padding:14px 32px;text-align:center;font-size:12px;color:#888;">© AllinTech</div>
</div>';

$clientPayload = json_encode([
    'from'    => 'AllinTech <noreply@allintech.co.il>',
    'to'      => [$email],
    'subject' => 'קיבלנו את הבקשה שלך – נחזור אליך בקרוב',
    'html'    => $clientHtml,
]);

$ch2 = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $clientPayload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $RESEND_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
curl_exec($ch2);
curl_close($ch2);

echo json_encode(['ok' => true]);
