<?php
require_once __DIR__ . '/ECMail.php';

/**
 * YÖNTEM 1: Otomatik .env Kullanımı
 * Parametre verilmezse ortamdaki veya proje kökündeki .env dosyasından ayarları çeker.
 */
$mail = new ECMail();

// E-Posta Gönderme
$gonderildi = $mail->send([
    'to'      => 'alici@siteniz.com',
    'subject' => 'ECMail Test Mesajı',
    'body'    => '<h1>Tebrikler!</h1><p>ECMail başarıyla çalışıyor.</p>',
    'is_html' => true
]);

if ($gonderildi) {
    echo "✅ E-posta başarıyla gönderildi!\n";
} else {
    echo "❌ Hata: " . $mail->getLastError() . "\n";
}

/**
 * YÖNTEM 2: Kod İçinden Manuel Konfigürasyon
 */
/*
$customMail = new ECMail([
    'smtp_host'  => 'mail.farklisite.com',
    'smtp_port'  => 465,
    'username'   => 'info@farklisite.com',
    'password'   => 'Sifre123',
    'from_email' => 'info@farklisite.com',
    'from_name'  => 'Farklı Site'
]);

$customMail->send([
    'to'      => 'hedef@domain.com',
    'subject' => 'Dinamik Gönderim',
    'body'    => 'Dinamik konfigürasyon ile gönderildi.'
]);
*/

/**
 * YÖNTEM 3: Gelen Kutusu Okuma / Alma (POP3 & IMAP)
 */
/*
$gelenMails = $mail->fetchEmails(10);
foreach ($gelenMails as $msg) {
    echo "Gönderen: " . $msg['from'] . " | Konu: " . $msg['subject'] . "\n";
}
*/
?>