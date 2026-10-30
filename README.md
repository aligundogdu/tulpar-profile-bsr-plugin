# Build Ship Repeat

Üreten bir topluluk için profil alanları (aylık gelir, aktif proje sayısı,
yayınlanmış projeler) ve referanslı davet.

## Ne yapar

Kişi kartına üç alan ekler. Aylık gelir ve aktif proje sayısı avatara
gelindiğinde açılan küçük kartta da görünür, projeler yalnızca tam kartta.

Alanları herkes kendi profil ayarlarından doldurur. Boş bırakılan alan hiç
görünmez, yani kimse gelirini paylaşmak zorunda değil.

## Referanslı davet

Yönetici olmayan bir üye birini davet etmek istediğinde yöneticiden onay ister.
Standart davet mekanizması değişmez: yöneticiler davet penceresini kullanmaya devam
eder, bu yol yalnızca üyelerin referanslı davetinin önünü açar.

1. Üye bir kanalda `/davet` yazar, formu doldurur: ad, e-posta, kısa not.
2. **Davet Botu** çalışma alanının yöneticilerine özelden yazar.
3. Bir yönetici herhangi bir kanalda `/davet onayla <no>` yazar. Eklenti onun adına
   tek kullanımlık, 7 gün geçerli bir davet oluşturur; link talep eden üyeye özelden
   gider. Diğer yöneticilerin mesajı "onaylandı" olarak güncellenir.
   `/davet reddet <no> [sebep]` ile reddedilirse üye sebebiyle birlikte haber alır.
4. Davet edilen kişi linkle katılır; üyeliğinde kimin onayladığı ve kimin önerdiği
   (referans) kayıtlı kalır.

| Komut | Kim | Ne yapar |
|---|---|---|
| `/davet` | Üye | Davet talebi formunu açar |
| `/davet benim` | Herkes | Kendi taleplerini ve durumlarını gösterir |
| `/davet talepler` | Yönetici | Bekleyen talepleri listeler |
| `/davet onayla <no>` | Yönetici | Talebi onaylar, linki üyeye iletir |
| `/davet reddet <no> [sebep]` | Yönetici | Talebi reddeder |
| `/davet gecmis` | Yönetici | Son kararlar ve davet edilenin katılıp katılmadığı |

Ayar: **Bir üyenin en fazla bekleyen davet talebi** (varsayılan 3). Aynı e-posta için
aynı anda tek talep açılabilir.

Gereken Tulpar özellikleri: eklenti botu, eklenti davetleri ve konuşma bağlamı
(`bot:dm`, `invites:write`, `channels:read`, `members:read`).

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
