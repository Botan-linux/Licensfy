# Licensfy - Kullanım Politikası ve Kurallar

**Son Güncelleme:** 27 Nisan 2026

---

## 1. Genel Bakış

Licensfy, yazılım geliştiriciler için lisans anahtarı oluşturma, dağıtma ve doğrulama platformudur. Bu platformu kullanarak aşağıdaki kurallara uymanız gerekmektedir. Hizmeti kullanarak bu politikayı kabul etmiş sayılırsınız.

## 2. Hesap ve Güvenlik

- Her kullanıcı yalnızca bir hesap oluşturabilir. Çoklu hesap açmak yasaktır.
- Kullanıcı adları en az 3 karakter olmalıdır ve başka bir kullanıcı tarafından kullanılmamalıdır.
- Şifreler en az 6 karakter uzunluğunda olmalıdır.
- API anahtarınızı gizli tutmak sizin sorumluluğunuzdadır. API anahtarınızı üçüncü şahıslarla paylaşmamalısınız.
- Hesabınızda şüpheli aktivite tespit edilmesi halinde yönetim hesabınızı geçici olarak askıya alma hakkını saklı tutar.
- API anahtarınızı istediğiniz zaman ayarlar sayfasından yenileyebilirsiniz. Eski anahtar geçersiz olacaktır.

## 3. Lisans Yönetimi Kuralları

- Oluşturulan her lisans anahtarı benzersiz olmalı ve sistem tarafından otomatik üretilir.
- Lisans anahtarları manuel olarak girilemez; sistem `XXXX-XXXX-XXXX-XXXX` formatında otomatik üretim yapar.
- Bir lisansın `max_activations` değeri, aynı anda kaç farklı cihazda aktif olabileceğini belirler. Bu sınır aşıldığında yeni cihaz eklenemez.
- Süresiz lisanslar için `expires_at` alanı boş bırakılabilir. Tarih belirtilen lisanslar, süre dolduğunda otomatik olarak `expired` durumuna geçer.
- Bir lisans iptal edildiğinde (`revoked`), bu lisansla yapılan tüm doğrulama istekleri reddedilir.
- İptal edilen bir lisansı tekrar aktif edebilirsiniz, ancak cihaz kayıtları korunur.
- Toplu lisans üretimi en fazla 100 adet/istek ile sınırlıdır.

## 4. API Kullanım Kuralları

- API endpoint'leri yalnızca `POST` metodunu kabul eder.
- Dakikada en fazla **30 istek** yapabilirsiniz (IP bazlı rate limiting). Bu sınır aşıldığında `429 Too Many Requests` yanıtı alırsınız.
- Her API yanıtı `X-Signature` başlığı ile HMAC-SHA256 imzalanır. Bu imzayı doğrulayarak yanıtın bütünlüğünden emin olabilirsiniz.
- Lisans doğrulama endpoint'i (`/api/validate.php`) için minimum zorunlu alan `license_key`'dir. `hwid` ve `version` alanları opsiyoneldir.
- `hwid` (Hardware ID) alanı cihaz aktivasyon takibi için kullanılır. Format: `a-zA-Z0-9-_` karakterleri, 8-128 karakter arası.
- API yanıtındaki `update_required` alanı, istemci sürümü ürün sürümünden düşükse `true` döner.

## 5. Blacklist Politikası

Blacklist sistemi aşağıdaki türlerde engelleme kuralları oluşturmanıza olanak tanır:

| Tür | Açıklama |
|-----|----------|
| `license_key` | Belirli bir lisans anahtarını tüm doğrulama isteklerinde reddeder |
| `ip_address` | Belirli bir IP adresinden gelen tüm istekleri engeller |
| `hwid` | Belirli bir cihaz kimliğinin kullanımını engeller |
| `customer_email` | Bu e-posta ile ilişkilendirilmiş tüm lisansları reddeder |

- Blacklist kuralları kullanıcı bazlıdır. Sizin eklediğiniz kurallar sadece sizin ürünlerinizdeki lisansları etkiler.
- Aynı tür ve değer kombinasyonu yalnızca bir kez eklenebilir (benzersiz kısıtlaması).
- Blacklist kontrolü lisans doğrulama sırasında otomatik yapılır ve `403 Forbidden` yanıtı döner.

## 6. Webhook Kuralları

- Webhook endpoint'leri HTTPS URL olmalıdır.
- Her webhook için bir `secret_key` belirleyebilirsiniz. Bu anahtar ile gönderilen istek gövdesi HMAC-SHA256 ile imzalanır.
- Webhook gönderimleri eşzamanlı (senkron) yapılır. Hedef sunucu 5 saniye içinde yanıt vermezse istek zaman aşımına uğrar.
- Aşağıdaki olaylar için webhook tetiklenebilir:
  - `license.created` - Yeni lisans üretildiğinde
  - `license.revoked` - Lisans iptal edildiğinde
  - `license.activated` - Lisans tekrar aktif edildiğinde
  - `license.validated` - Lisans doğrulama başarılı olduğunda
  - `license.validation_failed` - Doğrulama başarısız olduğunda (blacklist, iptal, süre dolumu, cihaz limiti)

## 7. Veri Gizliliği

- Kullanıcı bilgileri (kullanıcı adı, e-posta, şifre hash) veritabanında şifreli olarak saklanır.
- Şifreler `bcrypt` (PASSWORD_DEFAULT) ile hash'lenir. Ham şifre asla saklanmaz.
- Müşteri bilgileri (isim, e-posta) yalnızca lisans sahibi kullanıcı tarafından görülebilir.
- IP adresleri yalnızca güvenlik ve aktivasyon takibi amacıyla kaydedilir.
- Aktivasyon cihaz kayıtları (HWID, IP) lisans doğrulama sırasında otomatik oluşturulur ve yönetilebilir.

## 8. Rate Limiting (Hız Sınırlaması)

| Kaynak | Limit | Pencere |
|--------|-------|---------|
| API istekleri | 30 istek | 60 saniyelik (dakika) |
| Giriş denemesi | 5 deneme | 15 dakikalık kilitleme |

- API rate limit bilgisi yanıt başlıklarında (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`) bulunur.
- Giriş denemesi sınırı aşıldığında IP adresi 15 dakika süreyle kilitlenir.
- Rate limit aşıldığında doğrulama endpoint'leri otomatik olarak 250ms gecikme uygular (timing attack koruması).

## 9. Güvenlik Önlemleri

Platform aşağıdaki güvenlik önlemlerini içerir:

- **CSRF Koruması:** Tüm formlarda session bazlı CSRF token zorunludur.
- **SQL Injection Koruması:** Tüm veritabanı sorguları prepared statements kullanır.
- **XSS Koruması:** Çıktılar `htmlspecialchars()` ile escape edilir.
- **Güvenlik Başlıkları:** `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`
- **Oturum Yönetimi:** Giriş başarılı olduğunda `session_regenerate_id(true)` çağrılır.
- **Hassas Dizin Koruması:** `includes/` ve `data/` dizinlerine doğrudan erişim engellenir.
- **Dosya Uzantısı Engellemesi:** `.py`, `.md`, `.log`, `.db`, `.sql`, `.sh`, `.bak` dosyaları sunucuda engellenir.

## 10. Yasaklı Kullanımlar

Aşağıdaki kullanımlar kesinlikle yasaktır:

- Platformu yasadışı yazılım lisanslamak için kullanmak
- Başka bir kullanıcının hesabına yetkisiz erişim sağlamaya çalışmak
- API endpoint'lerini aşırı yüklemek (DDoS, brute force)
- Lisans doğrulama sistemini atlatmaya yönelik araçlar geliştirmek
- Üçüncü şahısların API anahtarlarını toplamak veya kullanmak
- Sistemin güvenlik önlemlerini aşmaya yönelik girişimlerde bulunmak

Yasaklı kullanım tespit edilmesi halinde hesap kalıcı olarak kapatılabilir.

## 11. Sorumluluk Reddi

Licensfy, bir lisans yönetim aracı olarak sunulmaktadır. Platformun kesintisiz çalışması garanti edilmez. Kullanıcıların lisans anahtarlarını ve API anahtarlarını düzenli olarak yedeklemesi önerilir. Platform üzerindeki verilerinizin yedeklenmesinden siz sorumlusunuz.

---

© 2026 Licensfy - Tüm hakları saklıdır.
