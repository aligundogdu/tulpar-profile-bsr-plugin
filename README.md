# Build Ship Repeat

Üreten bir topluluk için profil alanları: aylık gelir, aktif proje sayısı ve
yayınlanmış projeler.

## Ne yapar

Kişi kartına üç alan ekler. Aylık gelir ve aktif proje sayısı avatara
gelindiğinde açılan küçük kartta da görünür, projeler yalnızca tam kartta.

Alanları herkes kendi profil ayarlarından doldurur. Boş bırakılan alan hiç
görünmez, yani kimse gelirini paylaşmak zorunda değil.

## Kurulum

Eklenti [Tulpar eklenti kaydında](https://github.com/Tulpar-Chat/plugins) listeli.

**Arayüzden:** Yönetici ayarları → Eklentiler → Marketplace → **Kur**.

**Konsoldan:**

```bash
php bin/console app:plugin:install-remote dev.bsr.profile
```

Aynı komut kurulu bir sürümü güncellemek için de kullanılır; ayarlar ve
doldurulmuş alan değerleri korunur.

## Sürüm çıkarmak

`plugin.json` içindeki sürümü yükselt, etiketi at:

```bash
git tag v1.0.1 && git push --tags
```

İş akışı paketi kurar, `sha256`'sını hesaplar ve kayda eklenecek JSON girdisini
sürüm notlarına yazar. O girdiyi kayıt deposundaki `registry.json` dosyasına
ekleyip `latest` alanını güncellemek yeterli.

## Yapılandırma

Para birimi manifest'te sabit (`USD`). Topluluğun başka bir birim kullanması
gerekiyorsa `plugin.json` içindeki `currency` alanını değiştirip sürümü
yükseltmek yeterli.

## Kod yok

Bu eklenti PHP çalıştırmaz; her şey `plugin.json` içinde tarif edilmiştir.
Sunucu alanları doğrular, saklar ve isteğin diline göre biçimlendirir; web ve
mobil istemciler aynı veriyi kendi bileşenleriyle çizer.

Sözleşmenin tamamı: `docs/plugin-api.md`
