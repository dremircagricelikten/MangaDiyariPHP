# Manga Diyarı PHP

Bootstrap, jQuery ve AJAX kullanarak hazırlanan hafif bir manga okuma CMS'si. WordPress Madara teması benzeri bir deneyim sunar: serileri listeleme, detay sayfası ve çevrim içi bölüm okuma, ayrıca üyelik ve rol tabanlı yönetim paneli içerir.

## Özellikler

- SQLite tabanlı hafif veritabanı
- Bootstrap 5 ile responsive arayüz
- AJAX ile filtreleme ve arama
- Öne çıkan seriler için carousel ve grid görünümü
- Seri detay sayfası ve bölüm okuma deneyimi
- Üyelik sistemi ile giriş/çıkış, rol tabanlı yetkilendirme
- Yönetim paneli üzerinden seri ve bölüm ekleme

## Kurulum

1. Gerekli dizinleri ve veritabanını oluşturmak için:

   ```bash
   php bin/setup.php
   ```

   Komut, `config.php` dosyasındaki bilgiler ile varsayılan bir yönetici hesabı (admin rolünde) oluşturur.

2. `config.php` dosyasındaki yönetici kullanıcı adı, e-posta ve parolayı ihtiyacınıza göre güncelleyin.

3. Ön yüzü başlatmak için:

   ```bash
   php -S localhost:8000 -t public
   ```

4. Yönetim panelini başlatmak için ayrı bir sunucu açabilirsiniz:

   ```bash
   php -S localhost:8001 -t admin
   ```

### Plesk üzerinde çalıştırma

1. Depoyu Plesk hesabınızdaki alan adınızın `httpdocs` dizinine çıkarın.
2. Varsayılan olarak gelen `.htaccess` dosyası, tüm istekleri otomatik olarak `public/` dizinine yönlendirir. Ek bir yapılandırmaya gerek yoktur.
3. Uygulama ilk ziyaret edildiğinde SQLite veritabanı, tablo yapıları ve varsayılan yönetici hesabı (config.php dosyasındaki bilgilerle) otomatik olarak oluşturulur.
4. `config.php` içerisindeki veritabanı dosya yolu ve yönetici bilgileri Plesk ortamına göre günceldir; yalnızca yönetici parolasını değiştirdiğinizden emin olun.
5. Yönetim paneline `https://alanadiniz.tld/admin/` adresinden erişebilirsiniz.

## Dizim

```
public/        → Ön yüz, kullanıcı giriş/kayıt sayfaları ve API uçları
admin/         → Oturum açma gerektiren yönetim paneli
src/           → Veritabanı, repository ve oturum yardımcıları
bin/setup.php  → Veritabanı tablolarını oluşturur, yönetici hesabını hazırlar
config.php     → Site ve yönetici ayarları
```

## Notlar

- Yönetim paneli ve API uçları, oturum açmış ve rolü `admin` veya `editor` olan kullanıcılarla kullanılabilir.
- Bölüm içerikleri düz metin, satır satır paragraf veya resim URL'leri olarak kaydedilebilir.
- Gerçek ortamda ek güvenlik kontrolleri (HTTPS, rate limiting, doğrulama e-postası vb.) uygulamanız önerilir.
