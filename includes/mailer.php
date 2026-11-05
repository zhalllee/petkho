<?php

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends one HTML email through Mailtrap.
 *
 * @param string $toEmail      Receiver's email address
 * @param string $toName       Receiver's name
 * @param string $subject      Email subject
 * @param string $htmlBody     Email body (HTML)
 * @param array  $attachments  Optional. Each item: ['content' => file data, 'name' => 'receipt.pdf']
 * @return true|string         true if sent, otherwise the error message
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody, array $attachments = [])
{
    $mail = new PHPMailer(true);   

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        // --- attachments (the PDF receipt) ---
        foreach ($attachments as $a) {
            $mail->addStringAttachment($a['content'], $a['name']);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}