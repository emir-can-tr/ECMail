# ECMail - Evrensel PHP E-Posta Gönderme ve Alma Kütüphanesi

[![PHP Version](https://img.shields.io/badge/PHP-%E2%89%A5%208.0-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Dependencies](https://img.shields.io/badge/Dependencies-Zero-blue.svg)](#)
[![PHP 8.4 Ready](https://img.shields.io/badge/PHP%208.4-Compatible-brightgreen.svg)](#)

[English Version](README_EN.md) | **Türkçe Dokümantasyon**

---

**ECMail**, harici hiçbir bağımlılığa (PHPMailer, Composer, `php-imap` eklentisi vb.) ihtiyaç duymayan, saf PHP soket mimarisi (`stream_socket_client`) ile geliştirilmiş **hafif, yüksek hızlı, güvenli ve evrensel** bir e-posta gönderme ve okuma kütüphanesidir.

Alastyr, Natro, cPanel/Exim, Plesk, DirectAdmin, Yandex Mail, Gmail, Microsoft Exchange ve tüm özel SMTP/IMAP/POP3 sunucularıyla %100 uyumlu şekilde çalışır.

---

## 📋 İçindekiler
- [💡 Neden ECMail? (php-imap ve PHPMailer Bağımlılığının Sonu)](#-neden-ecmail-php-imap-ve-phpmailer-bağımlılığının-sonu)
- [🌟 Detaylı Özellik Listesi](#-detaylı-özellik-listesi)
- [📁 Klasör ve Dosya Yapısı](#-klasör-ve-dosya-yapısı)
- [⚙️ Kurulum ve Yapılandırma](#️-kurulum-ve-yapılandırma)
- [💻 Detaylı Kullanım Rehberi ve Örnekler](#-detaylı-kullanım-rehberi-ve-örnekler)
  - [1. Hazır Glassmorphism Form Kullanımı (form.html & form_handler.php)](#1-hazır-glassmorphism-form-kullanımı-formhtml--form_handlerphp)
  - [2. Kod İçinden E-Posta Gönderme (SMTP)](#2-kod-içinden-e-posta-gönderme-smtp)
  - [3. Gelen Kutusu Okuma (Eklentisiz IMAP & POP3 Soket Motoru)](#3-gelen-kutusu-okuma-eklentisiz-imap--pop3-soket-motoru)
  - [4. Manuel Yapılandırma ile Nesne Oluşturma](#4-manuel-yapılandırma-ile-nesne-oluşturma)
- [📖 API Metot Referansı](#-api-metot-referansı)
- [🌐 Sunucu ve Hosting Uyumluluğu](#-sunucu-ve-hosting-uyumluluğu)
- [🔒 Güvenlik Tavsiyeleri](#-güvenlik-tavsiyeleri)
- [📄 Lisans](#-lisans)

---

## 💡 Neden ECMail? (php-imap ve PHPMailer Bağımlılığının Sonu)

Geleneksel PHP projelerinde e-posta göndermek için devasa **PHPMailer** / **SwiftMailer** kütüphanelerine ve Composer bağımlılıklarına ihtiyaç duyulur. Gelen e-postaları okumak için ise sunucuda `php-imap` eklentisinin kurulu olması şart koşulur.

Ancak günümüz web sunucularında bu durum ciddi sorunlara yol açmaktadır:

1. 🚫 **`php-imap` Eklentisi Kısıtlamaları:** Paylaşımlı hosting sağlayıcılarının (Natro, Alastyr, cPanel, GoDaddy vb.) %90'ında güvenlik ve performans nedeniyle `php-imap` eklentisi devredışıdır.
2. ⚠️ **PHP 8.4 Çekirdek Değişikliği:** `php-imap` eklentisi **PHP 8.4 itibarıyla PHP çekirdeğinden tamamen kaldırılmıştır**. Eski kütüphaneler PHP 8.4+ sunucularda çalışmaz hale gelmiştir.
3. ⚡ **Soket Banner & Multi-Line Yanıt Hataları:** Natro, Alastyr, Exim ve cPanel sunucuları, SMTP karşılama (220) ve `EHLO` aşamalarında çok satırlı (multi-line) yanıtlar döndürür. Basit soket kodları bu yanıtları okurken kilitlenir veya hata verir.
4. 📦 **Sıfır Bağımlılık İhtiyacı:** Küçük veya orta ölçekli projelerde yüzlerce dosyalık Composer kütüphanelerini projeye dahil etmek gereksiz karmaşıklık yaratır.

### ECMail Nasıl Çözer?
`ECMail`, PHP'nin yerleşik `stream_socket_client` fonksiyonunu kullanarak doğrudan SMTP, IMAP ve POP3 protokol seviyesinde el sıkışır. Sunucunuzda **hiçbir PHP eklentisi kurulu olmasa dahi** e-posta gönderir ve okur. 

---

## 🌟 Detaylı Özellik Listesi

### 📤 Gelişmiş SMTP Gönderim Motoru
- **Soket Bağlantı Modları:** SSL (Port 465), STARTTLS (Port 587 / 25) ve Düz TCP bağlantılarını otomatik yönetir.
- **TLS 1.2 / 1.3 Desteği:** `STARTTLS` komutundan sonra güvenli `stream_socket_enable_crypto` ile şifreli kanala otomatik geçer.
- **Çok Satırlı Yanıt Desteği:** Exim, cPanel, Natro ve Alastyr sunucularındaki çok satırlı `220` ve `EHLO 250-` yanıtlarını akıllı döngü ile eksiksiz işler.
- **Gelişmiş MIME ve Dosya Ekleri:** `multipart/mixed` desteği ile her türlü dosyayı (PDF, resim, zip vb.) base64 formatında e-postaya ekler.
- **Zengin Başlık (Header) Desteği:** `To`, `Cc`, `Bcc`, `Reply-To`, `Date` (RFC 2822) ve özel `X-Mailer` başlıklarını içerir.
- **UTF-8 Başlık Kodlama:** Gönderen adı ve e-posta konusunda Türkçe / özel karakterlerin bozulmaması için otomatik `=?UTF-8?B?...?=` kodlaması yapar.

### 📥 Eklentisiz IMAP & POP3 Okuma Motoru
- **Saf Soket IMAP Engine (Port 993 SSL):** `LOGIN`, `SELECT INBOX`, `EXISTS` mesaj sayısı tespiti ve `BODY.PEEK[]` ile okunma durumunu değiştirmeden e-posta içeriği çeker.
- **Saf Soket POP3 Engine (Port 995 SSL):** `USER`, `PASS`, `STAT` ve `RETR` komutlarıyla gelen kutusundaki son mesajları çeker.
- **MIME Parser:** Ham e-posta metnini başlık ve gövde olarak ayırır, `iconv_mime_decode` ile konu ve gönderen bilgilerini okunabilir metne dönüştürür.

### ⚙️ Akıllı Ortam Değişkeni (.env) Motoru
- **Otomatik Arama:** Proje dizini, üst dizinler ve `DOCUMENT_ROOT` dahil tüm olası konumlarda `.env` dosyasını otomatik arar ve yükler.
- **Esnek Değişken İsimleri:** Farklı projelerdeki `.env` standartlarına tam uyum sağlar:
  - Kullanıcı Adı: `SMTP_USERNAME`, `SMTP_USER`, `MAIL_USERNAME`
  - Şifre: `SMTP_PASSWORD`, `SMTP_PASS`, `MAIL_PASSWORD`
  - Gönderen E-Posta: `SMTP_FROM_EMAIL`, `MAIL_FROM_ADDRESS`
  - Gönderen Adı: `SMTP_FROM_NAME`, `MAIL_FROM_NAME`
  - Varsayılan Alıcı: `SMTP_DEFAULT_TO`, `MAIL_DEFAULT_TO`
- **Otomatik Host Tamamlama:** `POP3_HOST` veya `IMAP_HOST` tanımlanmamışsa otomatik olarak `SMTP_HOST` değerini kullanır.

### 🎨 Modern İletişim Formu ve Şablonlama
- **Glassmorphism UI (`form.html`):** Dark mode, blur efektleri, CSS değişkenleri ve mobil uyumlu şık iletişim formu.
- **Asenkron Form Gönderimi:** Kullanıcı deneyimini aksatmayan AJAX / `fetch` entegrasyonu ve yükleniyor spinner'ı.
- **Otomatik HTML Şablonlayıcı (`handleFormSubmit()`):** Form verilerini güvenlik süzgecinden geçirir, responsive HTML e-posta kartına dönüştürür ve `Reply-To` başlığına formu dolduran kişinin e-postasını koyar.

---

## 📁 Klasör ve Dosya Yapısı

```
ECMail/
├── ECMail.php         # Ana Kütüphane Sınıfı (SMTP, IMAP, POP3, .env & Form Motoru)
├── form.html          # Modern Glassmorphism Arayüzlü İletişim Formu (HTML5/CSS3/JS)
├── form_handler.php   # Form İletişimi İçin JSON API Endpoint Backend Kodu
├── test_ecmail.php    # Test, Örnek Kullanım ve Entegrasyon Senaryoları
├── .env.example       # Ortam Değişkenleri Şablon Dosyası
├── README.md          # Türkçe Dokümantasyon
└── README_EN.md       # English Documentation
```

---

## ⚙️ Kurulum ve Yapılandırma

### 1. Dosyaları Projenize Ekleyin
`ECMail` klasörünü projenizin kök dizinine kopyalayın veya ihtiyacınıza göre istediğiniz bir dizine yerleştirin.

### 2. Ortam Değişkenlerini Tanımlayın (`.env`)
Projenizin kök dizininde bir `.env` dosyası oluşturun (`.env.example` dosyasını kopyalayabilirsiniz):

```env
# ==========================================
# ECMail Konfigürasyon Dosyası (.env)
# ==========================================

# SMTP Sunucu Ayarları (E-Posta Gönderimi)
SMTP_HOST=mail.siteniz.com
SMTP_PORT=465
SMTP_USERNAME=info@siteniz.com
SMTP_PASSWORD=GizliSifrenizBuraya
SMTP_FROM_EMAIL=info@siteniz.com
SMTP_FROM_NAME="Sitenizin Adı"
SMTP_DEFAULT_TO=iletisim@siteniz.com

# Gelen Kutusu Sunucu Ayarları (Opsiyonel - Tanımlanmazsa SMTP_HOST kullanılır)
POP3_HOST=mail.siteniz.com
POP3_PORT=995
IMAP_HOST=mail.siteniz.com
IMAP_PORT=993
```

---

## 💻 Detaylı Kullanım Rehberi ve Örnekler

### 1. Hazır Glassmorphism Form Kullanımı (`form.html` & `form_handler.php`)

Kütüphane ile birlikte gelen `form.html` ve `form_handler.php` dosyalarını doğrudan sitenizde iletişim sayfası olarak kullanabilirsiniz. 

#### Backend İşleyici (`form_handler.php`):
```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/ECMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new ECMail();
    // Otomatik olarak $_POST verisini alır, temizler, HTML şablonuna basar ve gönderir.
    $result = $mail->handleFormSubmit();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek yöntemi.'], JSON_UNESCAPED_UNICODE);
}
?>
```

---

### 2. Kod İçinden E-Posta Gönderme (SMTP)

#### Temel Gönderim:
```php
require_once __DIR__ . '/ECMail/ECMail.php';

$mail = new ECMail(); // .env dosyasından ayarları otomatik okur

$gonderildi = $mail->send([
    'to'      => 'alici@domain.com',
    'subject' => 'ECMail ile Hoş Geldiniz!',
    'body'    => '<h1>Merhaba!</h1><p>Bu mesaj ECMail kütüphanesi ile gönderilmiştir.</p>',
    'is_html' => true
]);

if ($gonderildi) {
    echo "✅ E-posta başarıyla gönderildi!";
} else {
    echo "❌ Hata Oluştu: " . $mail->getLastError();
}
```

#### Gelişmiş Gönderim (Dosya Eki, CC, BCC, Reply-To):
```php
$gonderildi = $mail->send([
    'to'          => ['alici1@domain.com', 'alici2@domain.com'],
    'cc'          => ['bilgi@domain.com'],
    'bcc'         => ['gizli@domain.com'],
    'reply_to'    => 'yanit@domain.com',
    'subject'     => 'Aylık Rapor ve Ekli Dosyalar',
    'body'        => '<b>Merhaba,</b><br>Ektekı dosyayı inceleyebilirsiniz.',
    'is_html'     => true,
    'attachments' => [
        __DIR__ . '/uploads/rapor.pdf',
        __DIR__ . '/uploads/gorsel.jpg'
    ]
]);
```

---

### 3. Gelen Kutusu Okuma (Eklentisiz IMAP & POP3 Soket Motoru)

`php-imap` eklentisine ihtiyaç duymadan gelen kutunuzdaki son mesajları çekebilirsiniz:

```php
require_once __DIR__ . '/ECMail/ECMail.php';

$mail = new ECMail();

// POP3 Protokolü ile Son 5 E-Postayı Çek (Port 995 SSL)
$gelenPop3 = $mail->fetchEmails(limit: 5, protocol: 'pop3');

// IMAP Protokolü ile Son 5 E-Postayı Çek (Port 993 SSL)
$gelenImap = $mail->fetchEmails(limit: 5, protocol: 'imap');

foreach ($gelenImap as $email) {
    echo "ID: " . $email['id'] . "<br>";
    echo "Gönderen: " . htmlspecialchars($email['from']) . "<br>";
    echo "Konu: " . htmlspecialchars($email['subject']) . "<br>";
    echo "Tarih: " . $email['date'] . "<br>";
    echo "İçerik Metni: <pre>" . htmlspecialchars($email['body']) . "</pre><hr>";
}
```

---

### 4. Manuel Yapılandırma ile Nesne Oluşturma

.env kullanmak istemiyorsanız veya çalışma anında dinamik konfigürasyon geçmek isterseniz:

```php
$mail = new ECMail([
    'smtp_host'  => 'mail.farklisite.com',
    'smtp_port'  => 465,
    'username'   => 'info@farklisite.com',
    'password'   => 'Sifreniz123',
    'from_email' => 'info@farklisite.com',
    'from_name'  => 'Özel Gönderen Adı',
    'timeout'    => 45
]);

$mail->send([
    'to'      => 'hedef@domain.com',
    'subject' => 'Dinamik Konfigürasyon Testi',
    'body'    => 'Dinamik parametreler ile gönderildi.'
]);
```

---

## 📖 API Metot Referansı

### `__construct(array $config = [], ?string $envPath = null)`
- `$config`: İsteğe bağlı konfigürasyon dizisi.
- `$envPath`: Özel bir `.env` dosyasının konumu.

### `send(array $params): bool`
E-posta gönderir. `$params` dizisinde kabul edilen anahtarlar:

| Anahtar | Tip | Zorunlu | Açıklama |
| :--- | :--- | :---: | :--- |
| `to` | `string\|array` | **Evet** | Alıcı e-posta adresi (tek adres veya dizi) |
| `subject` | `string` | **Evet** | E-posta konusu |
| `body` | `string` | **Evet** | E-posta gövde içeriği |
| `is_html` | `bool` | Hayır | `true` (Varsayılan HTML) veya `false` (Düz Metin) |
| `cc` | `array` | Hayır | CC alıcı e-posta dizisi |
| `bcc` | `array` | Hayır | BCC gizli alıcı e-posta dizisi |
| `reply_to` | `string` | Hayır | Yanıt adresi |
| `attachments` | `array` | Hayır | Sunucudaki mutlak dosya yolları dizisi |

### `handleFormSubmit(array $postData = [], ?string $overrideTo = null): array`
Form verilerini işler ve e-posta gönderir.
- Dönen Yanıt: `['status' => 'success'|'error', 'message' => '...']`

### `fetchEmails(int $limit = 10, string $protocol = 'pop3'): array`
Gelen kutusundan e-postaları okur.
- `$limit`: Çekilecek maksimum e-posta sayısı.
- `$protocol`: `'pop3'` (Varsayılan) veya `'imap'`.
- Dönen Dizi Elemanı Yapısı: `['id' => int, 'subject' => string, 'from' => string, 'date' => string, 'body' => string]`

### `getLastError(): string`
Oluşan son hatanın açıklama metnini döndürür.

### `setConfig(array $config): self`
Çalışma anında yapılandırma değerlerini günceller.

---

## 🌐 Sunucu ve Hosting Uyumluluğu

| Sunucu / Hosting | SMTP (Gönderim) | IMAP (Okuma) | POP3 (Okuma) | Notlar |
| :--- | :---: | :---: | :---: | :--- |
| **Natro** | ✅ %100 | ✅ %100 | ✅ %100 | 220 multi-line yanıt sorunu giderildi. |
| **Alastyr** | ✅ %100 | ✅ %100 | ✅ %100 | Soket el sıkışması tam uyumlu. |
| **cPanel / Exim** | ✅ %100 | ✅ %100 | ✅ %100 | Tüm varsayılan portlar şifreli desteklenir. |
| **Plesk Panel** | ✅ %100 | ✅ %100 | ✅ %100 | Sorunsuz çalışır. |
| **Yandex Mail** | ✅ %100 | ✅ %100 | ✅ %100 | Port 465 (SSL) veya 587 (TLS). |
| **Gmail / Google Workspace**| ✅ %100 | ✅ %100 | ✅ %100 | Uygulama şifresi (App Password) gerektirir. |
| **Microsoft 365 / Outlook** | ✅ %100 | ✅ %100 | ✅ %100 | Port 587 (STARTTLS). |

---

## 🔒 Güvenlik Tavsiyeleri

1. ⚠️ `.env` dosyanızı **asla** Git versiyon kontrol sisteminize eklemeyin. `.gitignore` dosyanıza `.env` satırını ekleyin.
2. 🔑 Veritabanı ve e-posta şifrelerinizi kod içerisinde hardcode olarak tutmayın, her zaman `.env` mekanizmasını kullanın.
3. 🌐 Web sunucunuzda (Apache/Nginx) `.env` dosyasına dışarıdan doğrudan erişimi engelleyin (`.htaccess` veya Nginx konum kuralları ile).

---

## 📄 Lisans

Bu proje **MIT Lisansı** altında lisanslanmıştır. Dilediğiniz gibi ticari ve kişisel projelerinizde ücretsiz olarak kullanabilirsiniz.
