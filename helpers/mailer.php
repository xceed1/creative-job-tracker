<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';


function sendMail(string $to, string $subject, string $html): void
{
    $mail = new PHPMailer(true);

    try {
        // 🔧 SMTP CONFIG
        $mail->isSMTP();
        $mail->Host       = 'smtp-mail.outlook.com';
        $mail->SMTPAuth   = true;
        // $mail->Username   = getenv('SMTP_USER');     // 👈 DO NOT HARDCODE
        // $mail->Password   = getenv('SMTP_PASS');     // 👈 DO NOT HARDCODE
        $mail->Username = 'm.alrwaily@pureminds.com.sa';
        $mail->Password = 'Q%Ed*y&c7Fv6@3Q*^U1PmlJ0';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // 🔍 DEBUG (TEMPORARY — DEV ONLY)
        if (getenv('APP_ENV') === 'local') {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function ($str, $level) {
                error_log("SMTP DEBUG [$level]: $str");
            };
        }

        // ✉️ MAIL CONTENT
        $mail->setFrom(
            $mail->Username,
            'Service Request Platform'
        );

        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        // 🚀 SEND
        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }

    } catch (Throwable $e) {
        error_log('[MAIL ERROR] ' . $e->getMessage());
        throw new Exception('Mail sending failed');
    }
}
