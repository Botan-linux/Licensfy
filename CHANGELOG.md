# Sürüm Geçmişi

Tüm notable değişiklikler bu dosyada tutulacaktır.

## [1.0.0] - 2026-04-27

### Eklenen
- Lisans anahtarı oluşturma ve yönetim sistemi
- API tabanlı lisans doğrulama (`/api/validate.php`)
- HWID cihaz aktivasyon takibi
- Blacklist sistemi (IP, HWID, lisans anahtarı, müşteri e-postası)
- Webhook entegrasyonu (HMAC-SHA256 imza)
- Discord webhook entegrasyonu (`/api/discord.php`)
- Çoklu dil desteği (Türkçe/İngilizce) + IP bazlı otomatik algılama
- Aktivite logları (sayfalama, filtreleme, arama)
- Dashboard istatistikleri
- Toplu lisans üretimi (max 100/istek)
- Toplu lisans iptal
- API anahtarı yönetimi (görüntüleme, kopyalama, yenileme)
- Şifre değiştirme
- Temiz URL yapısı (mod_rewrite)
- Dark theme responsive UI
- Güvenlik: CSRF, rate limiting, bcrypt, prepared statements
- Security headers (X-Content-Type-Options, X-Frame-Options, vb.)
- API yanıt imzalama (HMAC-SHA256)
- Timing attack koruması
- Kurulum sihirbazı (`install.php`)
- API dokümantasyonu sayfası
- Python, JavaScript, cURL entegrasyon örnekleri

### Güvenlik Düzeltmeleri
- `CURLOPT_SSL_VERIFYPEER` true yapıldı
- Hardcoded veritabanı kimlik bilgileri kaldırıldı
- Tab parametresi whitelist validasyonu eklendi
- Flash message double consumption bug düzeltildi
