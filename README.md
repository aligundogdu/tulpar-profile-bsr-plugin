# Build Ship Repeat

Üreten bir topluluk için profil alanları: aylık gelir, aktif proje sayısı ve
yayınlanmış projeler.

## Ne yapar

Kişi kartına üç alan ekler. Aylık gelir ve aktif proje sayısı avatara
gelindiğinde açılan küçük kartta da görünür, projeler yalnızca tam kartta.

Alanları herkes kendi profil ayarlarından doldurur. Boş bırakılan alan hiç
görünmez, yani kimse gelirini paylaşmak zorunda değil.

## Kurulum

```bash
./bin/tulpar prod plugins:sync      # veya: php bin/console app:plugin:sync-builtin
```

## Yapılandırma

Para birimi manifest'te sabit (`USD`). Topluluğun başka bir birim kullanması
gerekiyorsa `plugin.json` içindeki `currency` alanını değiştirip sürümü
yükseltmek yeterli.

## Kod yok

Bu eklenti PHP çalıştırmaz; her şey `plugin.json` içinde tarif edilmiştir.
Sunucu alanları doğrular, saklar ve isteğin diline göre biçimlendirir; web ve
mobil istemciler aynı veriyi kendi bileşenleriyle çizer.

Sözleşmenin tamamı: `docs/plugin-api.md`
