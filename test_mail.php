<?php
require_once __DIR__ . '/helpers/mailer.php';

echo "Starting mail test...<br>";

try {
    sendMail(
        'm.alrwaily@pureminds.com.sa',
        'SMTP Test',
        '<p>If you see this email, SMTP works.</p>'
    );
    echo "Mail function executed successfully.";
} catch (Throwable $e) {
    echo "Mail failed: " . $e->getMessage();
}
