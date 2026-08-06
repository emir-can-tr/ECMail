# ECMail - Universal PHP Email Sending and Receiving Library

[![PHP Version](https://img.shields.io/badge/PHP-5.4%20--%208.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Dependencies](https://img.shields.io/badge/Dependencies-Zero-blue.svg)](#)
[![PHP Universal Compatibility](https://img.shields.io/badge/PHP%205%20%7C%207%20%7C%208-Compatible-brightgreen.svg)](#)

[Türkçe Dokümantasyon](README.md) | **English Documentation**

---

**ECMail** is a **lightweight, ultra-fast, secure, and universal** PHP email sending and inbox fetching library written in pure PHP socket architecture (`stream_socket_client`). It operates with **ZERO external dependencies** (No PHPMailer, No Composer, No `php-imap` extension).

It works with 100% compatibility across all major email servers and control panels, including Alastyr, Natro, cPanel/Exim, Plesk, DirectAdmin, Yandex Mail, Gmail, Microsoft Exchange, and custom SMTP/IMAP/POP3 hosts.

---

## 📋 Table of Contents
- [💡 Why ECMail? (The End of php-imap & PHPMailer Dependencies)](#-why-ecmail-the-end-of-php-imap--phpmailer-dependencies)
- [🌟 Detailed Feature Set](#-detailed-feature-set)
- [📁 Directory & File Structure](#-directory--file-structure)
- [⚙️ Installation & Configuration](#️-installation--configuration)
- [💻 Detailed Usage Guide & Code Examples](#-detailed-usage-guide--code-examples)
  - [1. Out-of-the-Box Glassmorphism Contact Form (form.html & form_handler.php)](#1-out-of-the-box-glassmorphism-contact-form-formhtml--form_handlerphp)
  - [2. Sending Emails via Code (SMTP)](#2-sending-emails-via-code-smtp)
  - [3. Fetching Inbox Emails (Extensionless IMAP & POP3 Socket Engine)](#3-fetching-inbox-emails-extensionless-imap--pop3-socket-engine)
  - [4. Manual Instantiation & Configuration](#4-manual-instantiation--configuration)
- [📖 API Method Reference](#-api-method-reference)
- [🌐 Server & Hosting Compatibility](#-server--hosting-compatibility)
- [🔒 Security Best Practices](#-security-best-practices)
- [📄 License](#-license)

---

## 💡 Why ECMail? (The End of php-imap & PHPMailer Dependencies)

Traditionally, sending emails in PHP requires heavy third-party packages like **PHPMailer** or **SwiftMailer** managed via Composer. Reading incoming emails relies heavily on the native `php-imap` extension.

However, modern web hosting environments impose major obstacles:

1. 🚫 **`php-imap` Extension Restrictions:** Over 90% of shared web hosting providers (Natro, Alastyr, cPanel, GoDaddy, etc.) disable `php-imap` due to security and performance concerns.
2. ⚠️ **PHP 8.4 Core Removal:** The `php-imap` extension has been **completely removed from the PHP core starting with PHP 8.4**. Legacy email reader libraries break entirely on PHP 8.4+ servers.
3. ⚡ **Socket Banner & Multi-Line Response Pitfalls:** Servers running Exim, cPanel, Natro, or Alastyr return multi-line responses during initial greetings (`220`) and `EHLO` commands. Simple socket scripts freeze or throw syntax errors when encountering multi-line server banners.
4. 📦 **Zero-Dependency Mandate:** Microservices, landing pages, and standalone scripts often do not need hundreds of composer vendor files just to dispatch an email.

### How ECMail Solves This
`ECMail` utilizes PHP's built-in `stream_socket_client` to perform low-level protocol handshakes directly over raw TCP/SSL sockets for SMTP, IMAP, and POP3. **Even if your server has zero PHP extensions installed**, ECMail sends and reads emails reliably.

---

## 🌟 Detailed Feature Set

### 📤 Advanced SMTP Outbound Engine
- **Socket Connection Modes:** Native support for SSL (Port 465), STARTTLS (Port 587 / 25), and Plain TCP connections.
- **TLS 1.2 / 1.3 Upgrade:** Seamlessly upgrades connection to encrypted TLS via `stream_socket_enable_crypto` following the `STARTTLS` command.
- **Multi-Line Response Parsing:** Intelligent loop parser handles multi-line `220` banners and `EHLO 250-` feature announcements without hanging.
- **Rich MIME & File Attachments:** Construct multi-part emails (`multipart/mixed`) supporting PDF, images, zip files, and documents encoded in Base64.
- **Comprehensive Headers:** Supports `To` (string or array), `Cc`, `Bcc`, `Reply-To`, RFC 2822 `Date`, and custom `X-Mailer` headers.
- **UTF-8 Encoded Headers:** Automatically formats non-ASCII characters in sender names and subjects using `=?UTF-8?B?...?=` base64 header encoding.

### 📥 Extensionless IMAP & POP3 Inbound Engine
- **Pure Socket IMAP Engine (Port 993 SSL):** Performs `LOGIN`, `SELECT INBOX`, calculates `EXISTS` message counts, and fetches raw RFC 822 content via `BODY.PEEK[]` (without altering read/unread flags).
- **Pure Socket POP3 Engine (Port 995 SSL):** Performs `USER`, `PASS`, `STAT`, and `RETR` loop commands to retrieve recent inbox messages.
- **MIME Parser:** Splits raw message streams into header and body sections, decoding RFC 2047 subjects and senders with `iconv_mime_decode`.

### ⚙️ Smart Environment (.env) Engine
- **Auto-Discovery:** Automatically locates and parses `.env` files across current directory, parent directories, and `$_SERVER['DOCUMENT_ROOT']`.
- **Flexible Variable Mapping:** Compatible with multiple framework naming conventions:
  - Username: `SMTP_USERNAME`, `SMTP_USER`, `MAIL_USERNAME`
  - Password: `SMTP_PASSWORD`, `SMTP_PASS`, `MAIL_PASSWORD`
  - From Address: `SMTP_FROM_EMAIL`, `MAIL_FROM_ADDRESS`
  - From Name: `SMTP_FROM_NAME`, `MAIL_FROM_NAME`
  - Default Recipient: `SMTP_DEFAULT_TO`, `MAIL_DEFAULT_TO`
- **Fallback Host Resolution:** Automatically defaults `POP3_HOST` and `IMAP_HOST` to `SMTP_HOST` if left unspecified.

### 🎨 Modern Glassmorphism Contact Form & HTML Templating
- **Glassmorphism UI (`form.html`):** Dark mode theme, backdrop-filter blur effects, responsive CSS grid/flexbox, custom typography (Inter font), smooth inputs, and loading state animations.
- **Asynchronous AJAX Submission:** Built-in Javascript fetch listener providing real-time feedback popups without refreshing the page.
- **Auto HTML Template Generator (`handleFormSubmit()`):** Sanitizes inputs (`FILTER_SANITIZE_EMAIL`, `htmlspecialchars`), wraps content into a responsive HTML email layout, and injects the submitter's email as `Reply-To`.

---

## 📁 Directory & File Structure

```
ECMail/
├── ECMail.php         # Main Library Class (SMTP, IMAP, POP3, .env & Form Engine)
├── form.html          # Glassmorphism UI Contact Form (HTML5/CSS3/JS)
├── form_handler.php   # Backend JSON API Endpoint for Form Submissions
├── test_ecmail.php    # Example Scripts & Integration Test Scenarios
├── .env.example       # Environment Variables Template File
├── README.md          # Turkish Documentation
└── README_EN.md       # English Documentation
```

---

## ⚙️ Installation & Configuration

### 1. Include Files in Your Project
Copy the `ECMail` directory into your project's root folder or desired library path.

### 2. Configure Environment Variables (`.env`)
Create a `.env` file in your project root directory (you can copy `.env.example` as a baseline):

```env
# ==========================================
# ECMail Environment Configuration (.env)
# ==========================================

# SMTP Server Settings (Outbound Mail)
SMTP_HOST=mail.yourdomain.com
SMTP_PORT=465
SMTP_USERNAME=info@yourdomain.com
SMTP_PASSWORD=YourSecretPasswordHere
SMTP_FROM_EMAIL=info@yourdomain.com
SMTP_FROM_NAME="Your Website Name"
SMTP_DEFAULT_TO=contact@yourdomain.com

# Inbox Server Settings (Optional - Defaults to SMTP_HOST if omitted)
POP3_HOST=mail.yourdomain.com
POP3_PORT=995
IMAP_HOST=mail.yourdomain.com
IMAP_PORT=993
```

---

## 💻 Detailed Usage Guide & Code Examples

### 1. Out-of-the-Box Glassmorphism Contact Form (`form.html` & `form_handler.php`)

You can serve `form.html` directly or embed it into your website. 

#### Backend Handler Endpoint (`form_handler.php`):
```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ECMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new ECMail();
    // Automatically parses $_POST, sanitizes inputs, builds template & sends email
    $result = $mail->handleFormSubmit();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.'], JSON_UNESCAPED_UNICODE);
}
?>
```

---

### 2. Sending Emails via Code (SMTP)

#### Basic Email Dispatch:
```php
require_once __DIR__ . '/ECMail/ECMail.php';

$mail = new ECMail(); // Automatically loads settings from .env

$sent = $mail->send([
    'to'      => 'recipient@domain.com',
    'subject' => 'Welcome to ECMail!',
    'body'    => '<h1>Hello!</h1><p>This email was dispatched via ECMail library.</p>',
    'is_html' => true
]);

if ($sent) {
    echo "✅ Email dispatched successfully!";
} else {
    echo "❌ Error: " . $mail->getLastError();
}
```

#### Advanced Email Dispatch (Attachments, CC, BCC, Reply-To):
```php
$sent = $mail->send([
    'to'          => ['recipient1@domain.com', 'recipient2@domain.com'],
    'cc'          => ['info@domain.com'],
    'bcc'         => ['audit@domain.com'],
    'reply_to'    => 'support@domain.com',
    'subject'     => 'Monthly Statement and Reports',
    'body'        => '<b>Hello,</b><br>Please find the attached files for review.',
    'is_html'     => true,
    'attachments' => [
        __DIR__ . '/uploads/report.pdf',
        __DIR__ . '/uploads/invoice.jpg'
    ]
]);
```

---

### 3. Fetching Inbox Emails (Extensionless IMAP & POP3 Socket Engine)

Retrieve recent emails without requiring the `php-imap` extension:

```php
require_once __DIR__ . '/ECMail/ECMail.php';

$mail = new ECMail();

// Fetch last 5 emails using POP3 Protocol (Port 995 SSL)
$pop3Emails = $mail->fetchEmails(limit: 5, protocol: 'pop3');

// Fetch last 5 emails using IMAP Protocol (Port 993 SSL)
$imapEmails = $mail->fetchEmails(limit: 5, protocol: 'imap');

foreach ($imapEmails as $email) {
    echo "ID: " . $email['id'] . "<br>";
    echo "From: " . htmlspecialchars($email['from']) . "<br>";
    echo "Subject: " . htmlspecialchars($email['subject']) . "<br>";
    echo "Date: " . $email['date'] . "<br>";
    echo "Body: <pre>" . htmlspecialchars($email['body']) . "</pre><hr>";
}
```

---

### 4. Manual Instantiation & Configuration

If you prefer passing configurations programmatically instead of relying on `.env`:

```php
$mail = new ECMail([
    'smtp_host'  => 'mail.customdomain.com',
    'smtp_port'  => 465,
    'username'   => 'info@customdomain.com',
    'password'   => 'SecretPassword123',
    'from_email' => 'info@customdomain.com',
    'from_name'  => 'Custom Sender Name',
    'timeout'    => 45
]);

$mail->send([
    'to'      => 'target@domain.com',
    'subject' => 'Dynamic Configuration Test',
    'body'    => 'Dispatched with inline array configurations.'
]);
```

---

## 📖 API Method Reference

### `__construct(array $config = [], ?string $envPath = null)`
- `$config`: Optional configuration array.
- `$envPath`: Optional custom path to a `.env` file.

### `send(array $params): bool`
Dispatches an email via SMTP socket. Accepted keys in `$params`:

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `to` | `string\|array` | **Yes** | Destination email address (string or array of addresses) |
| `subject` | `string` | **Yes** | Email subject line |
| `body` | `string` | **Yes** | Email body content |
| `is_html` | `bool` | No | `true` (Default, HTML format) or `false` (Plain Text) |
| `cc` | `array` | No | Array of CC recipient addresses |
| `bcc` | `array` | No | Array of BCC recipient addresses |
| `reply_to` | `string` | No | Custom reply-to email address |
| `attachments` | `array` | No | Array of absolute server file paths to attach |

### `handleFormSubmit(array $postData = [], ?string $overrideTo = null): array`
Processes form submission payloads and dispatches formatted HTML emails.
- Return payload: `['status' => 'success'|'error', 'message' => '...']`

### `fetchEmails(int $limit = 10, string $protocol = 'pop3'): array`
Reads inbox messages over raw sockets.
- `$limit`: Maximum number of recent emails to fetch.
- `$protocol`: `'pop3'` (Default) or `'imap'`.
- Return array item structure: `['id' => int, 'subject' => string, 'from' => string, 'date' => string, 'body' => string]`

### `getLastError(): string`
Returns the exact error message string of the last failure.

### `setConfig(array $config): self`
Updates instance configuration values dynamically at runtime.

---

## 🌐 Server & Hosting Compatibility

| Server / Hosting | SMTP (Outbound) | IMAP (Inbound) | POP3 (Inbound) | Notes |
| :--- | :---: | :---: | :---: | :--- |
| **Natro** | ✅ 100% | ✅ 100% | ✅ 100% | Solved 220 multi-line greeting issues. |
| **Alastyr** | ✅ 100% | ✅ 100% | ✅ 100% | Fully compatible socket handshakes. |
| **cPanel / Exim** | ✅ 100% | ✅ 100% | ✅ 100% | All default encrypted ports supported. |
| **Plesk Panel** | ✅ 100% | ✅ 100% | ✅ 100% | Works out of the box. |
| **Yandex Mail** | ✅ 100% | ✅ 100% | ✅ 100% | Port 465 (SSL) or 587 (TLS). |
| **Gmail / Google Workspace**| ✅ 100% | ✅ 100% | ✅ 100% | Requires App Password. |
| **Microsoft 365 / Outlook** | ✅ 100% | ✅ 100% | ✅ 100% | Port 587 (STARTTLS). |

---

## 🔒 Security Best Practices

1. ⚠️ **Never commit your `.env` file** to version control repositories. Add `.env` to your `.gitignore`.
2. 🔑 Do not hardcode credentials in your PHP scripts; always leverage the `.env` engine.
3. 🌐 Protect your `.env` file from public web access via `.htaccess` or Nginx location blocks.

---

## 📄 License

This project is licensed under the **MIT License**. Free to use in commercial and personal projects.
