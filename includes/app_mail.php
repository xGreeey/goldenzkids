<?php
declare(strict_types=1);

/**
 * Shared SMTP mail helpers (Gmail app password via .env EMAIL / APP_PASSWORD).
 */

function app_smtp_configured(): bool
{
    $user = trim((string) ($_ENV['EMAIL'] ?? getenv('EMAIL') ?? ''));
    $pass = trim((string) ($_ENV['APP_PASSWORD'] ?? getenv('APP_PASSWORD') ?? ''));

    return $user !== '' && $pass !== '';
}

/**
 * Random password suitable for one-time file protection emails.
 */
function app_generate_random_password(int $length = 12): string
{
    $length = max(10, min(32, $length));
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghijkmnopqrstuvwxyz';
    $digits = '23456789';
    $symbols = '!@#$%&*';
    $all = $upper . $lower . $digits . $symbols;

    $password = $upper[random_int(0, strlen($upper) - 1)]
        . $lower[random_int(0, strlen($lower) - 1)]
        . $digits[random_int(0, strlen($digits) - 1)]
        . $symbols[random_int(0, strlen($symbols) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}

/**
 * @param list<string> $to
 */
function app_send_html_email(array $to, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $smtpUser = trim((string) ($_ENV['EMAIL'] ?? getenv('EMAIL') ?? ''));
    $smtpPass = trim((string) ($_ENV['APP_PASSWORD'] ?? getenv('APP_PASSWORD') ?? ''));
    if ($smtpUser === '' || $smtpPass === '') {
        error_log('app_send_html_email: EMAIL/APP_PASSWORD missing in environment');
        return false;
    }

    $recipients = [];
    foreach ($to as $addr) {
        $addr = trim($addr);
        if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $addr;
        }
    }
    if ($recipients === []) {
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($smtpUser, app_agency_name());
        foreach ($recipients as $addr) {
            $mail->addAddress($addr);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $exception) {
        error_log('app_send_html_email failed: ' . $exception->getMessage());

        return false;
    }
}
