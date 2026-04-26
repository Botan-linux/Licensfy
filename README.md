# Licensfy - Yazılım Lisanslama Platformu

Licensfy, yazılım geliştiriciler için açık kaynaklı bir lisans yönetim platformudur. GUI uygulamaları, masaüstü yazılımları, Discord botları veya özel istemciler fark etmez — tek bir çekirdek ile hepsini doğrulayın.

## ✨ Özellikler

- 🔑 **Lisans Yönetimi** — Anahtar oluşturma, dağıtma ve kontrol
- 🛡️ **HWID Cihaz Aktivasyonu** — Cihaz bazlı lisans doğrulama ve limit takibi
- 🚫 **Blacklist Sistemi** — IP, HWID, lisans anahtarı ve e-posta bazlı engelleme
- 🔗 **Webhook Entegrasyonu** — HMAC-SHA256 imzalı gerçek zamanlı bildirimler
- 🌍 **Çoklu Dil Desteği** — Türkçe/İngilizce, IP tabanlı otomatik algılama
- 📊 **Aktivite Logları** — Tüm işlemlerin detaylı takibi
- 🔐 **Güvenlik** — CSRF, rate limiting, bcrypt, prepared statements
- 🎨 **Dark Theme UI** — Responsive, modern arayüz

## 📋 Kurulum

### Gereksinimler

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache (mod_rewrite)
- cURL extension

### Adımlar

1. Projeyi klonlayın:
```bash
git clone https://github.com/Botan-linux/Licensfy.git
cd Licensfy
```

2. Ortam değişkenlerini ayarlayın:
```bash
export LICENSFY_DB_HOST="localhost"
export LICENSFY_DB_NAME="licensfy"
export LICENSFY_DB_USER="root"
export LICENSFY_DB_PASS="sifreniz"
```

3. Tarayıcıda `install.php` dosyasını açın veya manuel olarak tabloları oluşturun:
```bash
php setup_mysql.php
```

4. `.htaccess` dosyasının aktif olduğundan emin olun (Apache `mod_rewrite`).

## 🚀 API Kullanımı

### Lisans Doğrulama

```bash
curl -X POST https://sizin-domain.com/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "hwid": "device-identifier",
    "version": "1.0.0"
  }'
```

### Başarılı Yanıt

```json
{
  "success": true,
  "valid": true,
  "status": "active",
  "data": {
    "product": "Premium App",
    "latest_version": "2.0.0",
    "expires_at": null,
    "activations": { "current": 1, "max": 3 }
  }
}
```

## 📁 Proje Yapısı

```
Licensfy/
├── api/                    # API endpoint'leri
│   ├── validate.php        # Lisans doğrulama
│   └── discord.php         # Discord webhook
├── assets/
│   ├── css/style.css       # Ana stil dosyası
│   └── img/                # Logolar ve görseller
├── includes/
│   ├── config.php          # Yapılandırma ve yardımcı fonksiyonlar
│   ├── database.php        # Veritabanı şeması
│   ├── api_helpers.php     # API çekirdek mantığı
│   ├── header.php          # Navbar
│   ├── footer.php          # Footer
│   ├── geo.php             # IP bazlı dil algılama
│   └── lang/               # Dil dosyaları
│       ├── tr.php
│       └── en.php
├── data/                   # Veri dizini (korumalı)
├── .htaccess               # Apache yapılandırması
├── router.php              # URL yönlendirici
└── *.php                   # Sayfa dosyaları
```

## 🛡️ Güvenlik

- CSRF token koruması (tüm formlar)
- Prepared statements (SQL injection koruması)
- HMAC-SHA256 yanıt imzalama
- Rate limiting (API: 30/dk, Giriş: 5/15dk)
- bcrypt şifre hashleme
- Hassas dizin erişim engelleme
- Timing attack koruması (250ms gecikme)

## 📄 Lisans

Bu proje özel bir lisansla korunmaktadır. Detaylar için [POLICY.md](POLICY.md) dosyasına bakın.

---

© 2026 Licensfy
