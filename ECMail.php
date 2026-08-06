<?php
/**
 * ECMail - Evrensel PHP E-Posta Gönderme ve Alma Kütüphanesi
 * 
 * Özellikler:
 * - Harici bağımlılık gerektirmez (PHPMailer, Composer, php-imap vb. OLMADAN çalışır).
 * - Tüm e-posta sağlayıcılarıyla (Alastyr, Natro, cPanel/Exim, Gmail, Yandex, Microsoft vb.) %100 uyumludur.
 * - PHP 5.4 - PHP 8.4+ arasındaki TÜM PHP sürümleriyle evrensel uyumludur (PHP 5.4, 5.5, 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4+).
 * - .env dosyalarından otomatik yapılandırma okur. Güvenlik için dosya içinde şifre barındırmaz.
 * - Çok satırlı (multi-line) EHLO ve 220 banner yanıtlarını hatasız işler.
 * - SSL (465), STARTTLS (587/25) ve Düz TCP modlarını destekler.
 * - Dosya eki (Attachment), CC, BCC, HTML e-posta gönderimi yapar.
 * - php-imap eklentisine İHTİYAÇ DUYMADAN hem IMAP hem POP3 ile gelen kutusunu okur.
 */

// PHP 8.0 öncesi eski PHP sürümleri için str_starts_with polifili (PHP 5.4 - 7.4 Evrensel Uyumluluk)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

class ECMail {
    private $config = array(
        'smtp_host'   => '',
        'smtp_port'   => 465,
        'pop3_host'   => '',
        'pop3_port'   => 995,
        'imap_host'   => '',
        'imap_port'   => 993,
        'username'    => '',
        'password'    => '',
        'from_email'  => '',
        'from_name'   => '',
        'default_to'  => '',
        'timeout'     => 30
    );

    private $lastError = '';

    /**
     * @param array $config Opsiyonel yapılandırma dizisi (Verilmezse .env dosyasından okur)
     * @param string|null $envPath Özel .env dosya yolu
     */
    public function __construct($config = array(), $envPath = null) {
        $this->loadEnvConfig($envPath);

        if (!empty($config) && is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (empty($this->config['from_email'])) {
            $this->config['from_email'] = $this->config['username'];
        }
        if (empty($this->config['pop3_host'])) {
            $this->config['pop3_host'] = $this->config['smtp_host'];
        }
        if (empty($this->config['imap_host'])) {
            $this->config['imap_host'] = $this->config['smtp_host'];
        }
    }

    /**
     * .env dosyasından ve ortam değişkenlerinden yapılandırmayı yükler
     * 
     * @param string|null $envPath
     */
    private function loadEnvConfig($envPath = null) {
        $docRootEnv = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/.env' : null;
        $possiblePaths = array_filter(array(
            $envPath,
            __DIR__ . '/.env',
            dirname(__DIR__) . '/.env',
            $docRootEnv
        ));

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_readable($path)) {
                $this->parseEnvFile($path);
                break;
            }
        }

        // Ortam değişkenlerinden yükle
        $this->config['smtp_host']  = $this->getEnvVar('SMTP_HOST', $this->config['smtp_host']);
        $this->config['smtp_port']  = (int)$this->getEnvVar('SMTP_PORT', $this->config['smtp_port']);
        $this->config['username']   = $this->getEnvVar(array('SMTP_USERNAME', 'SMTP_USER', 'MAIL_USERNAME'), $this->config['username']);
        $this->config['password']   = $this->getEnvVar(array('SMTP_PASSWORD', 'SMTP_PASS', 'MAIL_PASSWORD'), $this->config['password']);
        $this->config['from_email'] = $this->getEnvVar(array('SMTP_FROM_EMAIL', 'MAIL_FROM_ADDRESS'), $this->config['from_email']);
        $this->config['from_name']  = $this->getEnvVar(array('SMTP_FROM_NAME', 'MAIL_FROM_NAME'), $this->config['from_name']);
        $this->config['default_to'] = $this->getEnvVar(array('SMTP_DEFAULT_TO', 'MAIL_DEFAULT_TO'), $this->config['default_to']);
        
        $this->config['pop3_host']  = $this->getEnvVar('POP3_HOST', $this->config['smtp_host']);
        $this->config['pop3_port']  = (int)$this->getEnvVar('POP3_PORT', $this->config['pop3_port']);
        $this->config['imap_host']  = $this->getEnvVar('IMAP_HOST', $this->config['smtp_host']);
        $this->config['imap_port']  = (int)$this->getEnvVar('IMAP_PORT', $this->config['imap_port']);
    }

    /**
     * @param string $filePath
     */
    private function parseEnvFile($filePath) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * @param string|array $keys
     * @param mixed $default
     * @return mixed
     */
    private function getEnvVar($keys, $default = '') {
        $keyList = (array)$keys;
        foreach ($keyList as $key) {
            if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
            $val = getenv($key);
            if ($val !== false && $val !== '') return $val;
        }
        return $default;
    }

    /**
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig($config = array()) {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }

    // =========================================================================
    // 1. GÖNDERİM İŞLEMLERİ (SMTP)
    // =========================================================================

    /**
     * @param array $params
     * @return bool
     */
    public function send($params = array()) {
        $to = isset($params['to']) ? $params['to'] : $this->config['default_to'];
        $subject = isset($params['subject']) ? $params['subject'] : '';
        $body = isset($params['body']) ? $params['body'] : '';
        $isHtml = isset($params['is_html']) ? $params['is_html'] : true;
        $cc = (array)(isset($params['cc']) ? $params['cc'] : array());
        $bcc = (array)(isset($params['bcc']) ? $params['bcc'] : array());
        $replyTo = isset($params['reply_to']) ? $params['reply_to'] : '';
        $attachments = (array)(isset($params['attachments']) ? $params['attachments'] : array());

        if (empty($to) || empty($subject) || empty($body)) {
            $this->lastError = 'Alıcı (to), Konu (subject) ve İçerik (body) zorunludur.';
            return false;
        }

        if (empty($this->config['smtp_host']) || empty($this->config['username'])) {
            $this->lastError = 'SMTP sunucu ayarları (host, username, password) eksik. Lütfen .env dosyasını veya konfigürasyonu kontrol edin.';
            return false;
        }

        $toList = is_array($to) ? $to : array_map('trim', explode(',', $to));

        try {
            $host = $this->config['smtp_host'];
            $port = (int)$this->config['smtp_port'];

            $serverName = !empty($_SERVER['SERVER_NAME']) 
                ? $_SERVER['SERVER_NAME'] 
                : (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

            $context = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            ));

            $protocol = ($port === 465) ? 'ssl://' : '';
            $socket = @stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, $this->config['timeout'], STREAM_CLIENT_CONNECT, $context);

            if (!$socket) {
                $this->lastError = "Bağlantı kurulamadı ($host:$port): $errstr ($errno)";
                return false;
            }

            // 1. Sunucu Banner (220)
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                $this->lastError = "Sunucu hazır değil: " . trim($response);
                return false;
            }

            // 2. HELO / EHLO
            fputs($socket, "EHLO " . $serverName . "\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '250') {
                fclose($socket);
                $this->lastError = "EHLO reddedildi: " . trim($response);
                return false;
            }

            // 3. STARTTLS (Port 587 veya 25)
            if ($port === 587 || $port === 25) {
                fputs($socket, "STARTTLS\r\n");
                $response = $this->getSmtpResponse($socket);
                if (substr($response, 0, 3) === '220') {
                    $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;

                    if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                        fclose($socket);
                        $this->lastError = "TLS şifreleme başlatılamadı.";
                        return false;
                    }

                    fputs($socket, "EHLO " . $serverName . "\r\n");
                    $response = $this->getSmtpResponse($socket);
                }
            }

            // 4. AUTH LOGIN
            fputs($socket, "AUTH LOGIN\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                $this->lastError = "AUTH LOGIN reddedildi: " . trim($response);
                return false;
            }

            // Kullanıcı Adı
            fputs($socket, base64_encode($this->config['username']) . "\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                $this->lastError = "Kullanıcı adı reddedildi: " . trim($response);
                return false;
            }

            // Şifre
            fputs($socket, base64_encode($this->config['password']) . "\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                $this->lastError = "Şifre reddedildi / Kimlik doğrulama başarısız: " . trim($response);
                return false;
            }

            // 5. MAIL FROM
            fputs($socket, "MAIL FROM: <" . $this->config['from_email'] . ">\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '250') {
                fclose($socket);
                $this->lastError = "MAIL FROM hatası: " . trim($response);
                return false;
            }

            // 6. RCPT TO
            $allRecipients = array_unique(array_merge($toList, $cc, $bcc));
            foreach ($allRecipients as $recipient) {
                if (empty($recipient)) continue;
                fputs($socket, "RCPT TO: <$recipient>\r\n");
                $response = $this->getSmtpResponse($socket);
                if (substr($response, 0, 3) !== '250') {
                    fclose($socket);
                    $this->lastError = "RCPT TO ($recipient) hatası: " . trim($response);
                    return false;
                }
            }

            // 7. DATA
            fputs($socket, "DATA\r\n");
            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '354') {
                fclose($socket);
                $this->lastError = "DATA komutu hatası: " . trim($response);
                return false;
            }

            // 8. Headers ve İçerik
            $boundary = "----=_NextPart_" . md5(uniqid((string)time(), true));
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Date: " . date("r");
            $headers[] = "From: " . $this->encodeHeader($this->config['from_name']) . " <" . $this->config['from_email'] . ">";
            $headers[] = "To: " . implode(', ', $toList);
            if (!empty($cc)) $headers[] = "Cc: " . implode(', ', $cc);
            if (!empty($replyTo)) $headers[] = "Reply-To: <$replyTo>";
            $headers[] = "Subject: " . $this->encodeHeader($subject);
            $headers[] = "X-Mailer: ECMail-PHP";

            $emailContent = "";

            if (!empty($attachments)) {
                $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
                
                $emailContent .= "--$boundary\r\n";
                $emailContent .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
                $emailContent .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
                $emailContent .= str_replace(array("\r\n", "\r", "\n"), "\r\n", $body) . "\r\n\r\n";

                foreach ($attachments as $filePath) {
                    if (file_exists($filePath)) {
                        $fileName = basename($filePath);
                        $fileData = chunk_split(base64_encode(file_get_contents($filePath)));
                        $mimeType = function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream';
                        if (!$mimeType) $mimeType = 'application/octet-stream';

                        $emailContent .= "--$boundary\r\n";
                        $emailContent .= "Content-Type: $mimeType; name=\"$fileName\"\r\n";
                        $emailContent .= "Content-Transfer-Encoding: base64\r\n";
                        $emailContent .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                        $emailContent .= $fileData . "\r\n\r\n";
                    }
                }
                $emailContent .= "--$boundary--\r\n";
            } else {
                $headers[] = "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8";
                $headers[] = "Content-Transfer-Encoding: 8bit";
                $emailContent = str_replace(array("\r\n", "\r", "\n"), "\r\n", $body);
            }

            $headersStr = implode("\r\n", $headers);
            fputs($socket, "$headersStr\r\n\r\n$emailContent\r\n.\r\n");

            $response = $this->getSmtpResponse($socket);
            if (substr($response, 0, 3) !== '250') {
                fclose($socket);
                $this->lastError = "Mesaj gönderilemedi: " . trim($response);
                return false;
            }

            fputs($socket, "QUIT\r\n");
            fclose($socket);
            return true;

        } catch (Exception $e) {
            $this->lastError = "SMTP İstisnası: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Otomatik Form Gönderim İşleyici (HTML Formlardan Gelen POST İsteklerini İşler)
     * 
     * @param array $postData Form POST verileri (Boş verilirse $_POST kullanılır)
     * @param string|null $overrideTo Alıcı adresini değiştirmek istenirse
     * @return array ['status' => 'success'|'error', 'message' => '...']
     */
    public function handleFormSubmit($postData = array(), $overrideTo = null) {
        $data = !empty($postData) ? $postData : $_POST;

        $rawEmail = isset($data['email']) ? $data['email'] : '';
        $email   = filter_var($rawEmail, FILTER_SANITIZE_EMAIL);
        
        $rawName = isset($data['name']) ? $data['name'] : (isset($data['fullname']) ? $data['fullname'] : 'İsimsiz Gönderici');
        $name    = htmlspecialchars(trim($rawName));
        
        $rawSubject = isset($data['subject']) ? $data['subject'] : 'Yeni Form Mesajı';
        $subject = htmlspecialchars(trim($rawSubject));
        
        $rawMessage = isset($data['message']) ? $data['message'] : (isset($data['content']) ? $data['content'] : '');
        $message = htmlspecialchars(trim($rawMessage));

        if (empty($message)) {
            return array('status' => 'error', 'message' => 'Lütfen mesaj alanını doldurunuz.');
        }

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
                .card { background: #ffffff; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 16px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e1e8ed; }
                .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff; padding: 24px; font-size: 20px; font-weight: bold; }
                .content { padding: 24px; line-height: 1.6; }
                .field { margin-bottom: 16px; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }
                .field-title { font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 0.8px; font-weight: 700; }
                .field-value { font-size: 15px; color: #222; margin-top: 4px; }
                .footer { background: #f8fafc; padding: 16px 24px; font-size: 12px; color: #777; border-top: 1px solid #e1e8ed; text-align: center; }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='header'>📩 Yeni Mesaj Alındı</div>
                <div class='content'>
                    <div class='field'>
                        <div class='field-title'>Gönderen Adı</div>
                        <div class='field-value'>" . ($name ? $name : 'Belirtilmedi') . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-title'>E-Posta Adresi</div>
                        <div class='field-value'>" . ($email ? $email : 'Belirtilmedi') . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-title'>Konu</div>
                        <div class='field-value'>$subject</div>
                    </div>
                    <div class='field' style='border:none;'>
                        <div class='field-title'>Mesaj İçeriği</div>
                        <div class='field-value'>" . nl2br($message) . "</div>
                    </div>
                </div>
                <div class='footer'>Bu mesaj ECMail Form Sistemi tarafından otomatik oluşturulmuştur.</div>
            </div>
        </body>
        </html>";

        $params = array(
            'subject'  => "Form: $subject",
            'body'     => $htmlBody,
            'is_html'  => true,
            'reply_to' => $email
        );

        if (!empty($overrideTo)) {
            $params['to'] = $overrideTo;
        }

        $sent = $this->send($params);

        if ($sent) {
            return array('status' => 'success', 'message' => 'Mesajınız başarıyla iletildi!');
        } else {
            return array('status' => 'error', 'message' => $this->getLastError());
        }
    }

    private function getSmtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
            if (strlen($line) < 4) {
                break;
            }
        }
        return $response;
    }

    private function encodeHeader($str) {
        if (mb_detect_encoding($str, 'ASCII', true)) {
            return $str;
        }
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }

    // =========================================================================
    // 2. ALMA İŞLEMLERİ (IMAP & POP3 SOKET MOTORU - EKLENTİSİZ)
    // =========================================================================

    /**
     * Gelen kutusundan e-postaları çeker.
     * Hangi protokol istenirse (varsayılan: 'pop3' veya 'imap') eklentisiz soket ile çeker.
     * 
     * @param int $limit
     * @param string $protocol
     * @return array
     */
    public function fetchEmails($limit = 10, $protocol = 'pop3') {
        if (strtolower($protocol) === 'imap') {
            return $this->fetchViaImapSocket($limit);
        }
        return $this->fetchViaPop3Socket($limit);
    }

    /**
     * Saf Soket IMAP ile E-Posta Okuma (php-imap eklentisi gerektirmez!)
     */
    private function fetchViaImapSocket($limit) {
        $host = $this->config['imap_host'];
        $port = (int)$this->config['imap_port'];

        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        ));

        $protocolPrefix = ($port === 993) ? 'ssl://' : '';
        $socket = @stream_socket_client($protocolPrefix . $host . ':' . $port, $errno, $errstr, $this->config['timeout'], STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            $this->lastError = "IMAP Soket Bağlantı Hatası ($host:$port): $errstr ($errno)";
            return array();
        }

        // Welcome banner
        $res = fgets($socket, 512);

        // LOGIN
        fputs($socket, "A01 LOGIN " . $this->config['username'] . " " . $this->config['password'] . "\r\n");
        $res = $this->getImapResponse($socket, 'A01');
        if (strpos($res, 'A01 OK') === false) {
            fclose($socket);
            $this->lastError = "IMAP Giriş Hatası: " . trim($res);
            return array();
        }

        // SELECT INBOX
        fputs($socket, "A02 SELECT INBOX\r\n");
        $res = $this->getImapResponse($socket, 'A02');
        
        // Exists sayısını bul
        preg_match('/\* (\d+) EXISTS/i', $res, $matches);
        $totalEmails = (int)(isset($matches[1]) ? $matches[1] : 0);

        if ($totalEmails === 0) {
            fputs($socket, "A03 LOGOUT\r\n");
            fclose($socket);
            return array();
        }

        $start = max(1, $totalEmails - $limit + 1);
        fputs($socket, "A03 FETCH $start:$totalEmails (BODY.PEEK[])\r\n");
        $res = $this->getImapResponse($socket, 'A03');

        fputs($socket, "A04 LOGOUT\r\n");
        fclose($socket);

        // Raw parçalama
        $emailBlocks = explode('* ', $res);
        $results = array();
        $id = 1;
        foreach ($emailBlocks as $block) {
            $trimmed = trim($block);
            if (empty($trimmed)) continue;
            $parsed = $this->parseRawEmail($block);
            if (!empty($parsed['subject']) || !empty($parsed['from'])) {
                $parsed['id'] = $id++;
                $results[] = $parsed;
            }
        }

        return array_reverse(array_slice($results, 0, $limit));
    }

    private function getImapResponse($socket, $tag) {
        $response = '';
        while ($line = fgets($socket, 2048)) {
            $response .= $line;
            if (str_starts_with($line, "$tag OK") || str_starts_with($line, "$tag NO") || str_starts_with($line, "$tag BAD")) {
                break;
            }
        }
        return $response;
    }

    /**
     * Saf Soket POP3 ile E-Posta Okuma (php-imap eklentisi gerektirmez!)
     */
    private function fetchViaPop3Socket($limit) {
        $host = $this->config['pop3_host'];
        $port = (int)$this->config['pop3_port'];
        
        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        ));

        $protocol = ($port === 995) ? 'ssl://' : '';
        $socket = @stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, $this->config['timeout'], STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            $this->lastError = "POP3 Bağlantı Hatası ($host:$port): $errstr ($errno)";
            return array();
        }

        $res = fgets($socket, 512);
        if (substr($res, 0, 3) !== '+OK') {
            fclose($socket);
            $this->lastError = "POP3 Sunucu Hatası: " . trim($res);
            return array();
        }

        fputs($socket, "USER " . $this->config['username'] . "\r\n");
        $res = fgets($socket, 512);
        if (substr($res, 0, 3) !== '+OK') {
            fclose($socket);
            $this->lastError = "POP3 Kullanıcı Adı Reddedildi: " . trim($res);
            return array();
        }

        fputs($socket, "PASS " . $this->config['password'] . "\r\n");
        $res = fgets($socket, 512);
        if (substr($res, 0, 3) !== '+OK') {
            fclose($socket);
            $this->lastError = "POP3 Şifre Reddedildi: " . trim($res);
            return array();
        }

        fputs($socket, "STAT\r\n");
        $res = fgets($socket, 512);
        $parts = explode(' ', trim($res));
        $totalEmails = (int)(isset($parts[1]) ? $parts[1] : 0);

        if ($totalEmails === 0) {
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            return array();
        }

        $results = array();
        $start = max(1, $totalEmails - $limit + 1);

        for ($i = $totalEmails; $i >= $start; $i--) {
            fputs($socket, "RETR $i\r\n");
            $res = fgets($socket, 512);
            if (substr($res, 0, 3) !== '+OK') continue;

            $rawEmail = '';
            while ($line = fgets($socket, 1024)) {
                if (trim($line) === '.') break;
                $rawEmail .= $line;
            }

            $parsed = $this->parseRawEmail($rawEmail);
            $parsed['id'] = $i;
            $results[] = $parsed;
        }

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return $results;
    }

    private function parseRawEmail($raw) {
        $parts = explode("\r\n\r\n", $raw, 2);
        $headerStr = isset($parts[0]) ? $parts[0] : '';
        $body = isset($parts[1]) ? $parts[1] : '';

        $headers = array();
        $lines = explode("\r\n", $headerStr);
        foreach ($lines as $line) {
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                $headers[strtolower(trim($matches[1]))] = trim($matches[2]);
            }
        }

        $subj = isset($headers['subject']) ? iconv_mime_decode($headers['subject'], 0, 'UTF-8') : '';
        $from = isset($headers['from']) ? iconv_mime_decode($headers['from'], 0, 'UTF-8') : '';
        $date = isset($headers['date']) ? $headers['date'] : '';

        return array(
            'subject' => $subj ? $subj : '',
            'from'    => $from ? $from : '',
            'date'    => $date,
            'body'    => trim($body)
        );
    }
}
?>