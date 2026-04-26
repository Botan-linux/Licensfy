# Licensfy - Katkıda Bulunma Rehberi

Yardımlarınız için teşekkürler! Bu rehber, projeye katkıda bulunma sürecini açıklar.

## 🤝 Nasıl Katkıda Bulunabilirsiniz?

### Bug Bildirme

1. GitHub Issues bölümünden yeni bir issue açın
2. Hatayı yeniden üretme adımlarını yazın
3. Beklenen davranışı ve gerçekleşen davranışı açıklayın
4. PHP versiyonunuzu, tarayıcınızı ve sunucu ortamınızı belirtin

### Özellik Önerisi

1. Var olan issue'ları kontrol edin (benzer bir öneri olabilir)
2. Yeni bir issue açın, önerinizi detaylı açıklayın
3. Kullanım senaryosu ve beklenen fayda ekleyin

### Pull Request

1. Bu repo'yu fork'layın
2. Yeni bir branch oluşturun (`git checkout -b ozellik/adim`)
3. Değişikliklerinizi yapın ve commit edin
4. Branch'inizi push'layın (`git push origin ozellik/adim`)
5. Pull request açın

### Kod Standartları

- PHP 7.4+ uyumlu kod yazın
- Tüm çıktılar `e()` veya `htmlspecialchars()` ile escape edilmeli
- Veritabanı sorguları mutlaka prepared statements kullanmalı
- Yeni sayfa eklendiğinde her iki dil dosyasına da (`tr.php`, `en.php`) çeviri ekleyin
- CSRF token tüm formlarda zorunludur
- Girinti: 4 boşluk (tab yok)
- UTF-8 encoding zorunludur

### Dil Dosyalarına Çeviri Ekleme

Yeni bir dil string'i eklediğinizde:
```php
// tr.php
'yeni_anahtar' => 'Türkçe çeviri',

// en.php
'yeni_anahtar' => 'English translation',
```

## 📋 Geliştirme Ortamı

### Gereksinimler
- PHP 7.4+ (pdo_mysql, curl, mbstring)
- MySQL 5.7+ veya MariaDB 10.3+
- Apache (mod_rewrite)

### Yerel Kurulum
```bash
# Ortam değişkenlerini ayarlayın
export LICENSFY_DB_HOST="localhost"
export LICENSFY_DB_NAME="licensfy_test"
export LICENSFY_DB_USER="root"
export LICENSFY_DB_PASS=""

# Veritabanını oluşturun
php setup_mysql.php

# Yerel sunucuyu başlatın
php -S localhost:8000 router.php
```

## 🏗️ Proje Yapısı

| Dizin | Açıklama |
|-------|----------|
| `api/` | REST API endpoint'leri |
| `assets/` | CSS, JS ve görseller |
| `includes/` | Yardımcı fonksiyonlar, dil dosyaları |
| `data/` | Veri dizini (korumalı) |

## 📜 Davranış Kuralları

- Saygılı ve yapıcı olun
- Farklı deneyim seviyelerindeki geliştiricilere yardımcı olun
- Yapıcı eleştiride bulunun
- Kişisel saldırılardan kaçının

---

Sorularınız varsa issue açmaktan çekinmeyin!
