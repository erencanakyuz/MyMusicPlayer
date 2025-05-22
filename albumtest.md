Harika bir gözlem! Evet, o notu ben ekledim ve bu, ödevin o kısmını nasıl yorumlamamız gerektiği konusunda önemli bir detay.
Ödevde İstenen Hangisi? (playlistpage.php'yi mi değiştirmeli, albumpage.php mi oluşturmalı?)
Ödev açıklaması şu ifadeyi kullanıyor:
"If an album is selected, it should be similarly to the playlist page; however, adding new songs to the album should not be allowed."
Bu ifade, iki şekilde yorumlanabilir:
Aynı PHP dosyasını kullan (similarly to the playlist page), ancak içine koşullu mantık ekleyerek "add song" özelliğini kapat (adding new songs... should not be allowed). Bu, playlistpage.php dosyasını if (isset($_GET['playlist_id'])) veya if (isset($_GET['album_id'])) gibi kontrollerle karmaşıklaştırmayı gerektirir.
Farklı bir PHP dosyası oluştur (albumpage.php), ancak görsel olarak ve temel fonksiyonellik (şarkıları listeleme) açısından playlistpage'e benzesin (similarly to the playlist page), ancak "add song" formunu hiç eklemeyerek bu kısıtlamayı doğrudan uygula (adding new songs... should not be allowed).
Benim Tercihim ve Nedenleri (ve Neden Hocanın İstediği Dışına Çıkmamış Olursunuz):
Benim size albumpage.php'yi sağlamamın nedeni, bu iki yaklaşım arasında ödevin kısıtlamaları ve dersin öğretim düzeyi göz önüne alındığında daha temiz ve anlaşılır bir tasarım kararı olmasıdır.
Net Fonksiyon Ayrımı: playlistpage.php sadece oynatma listelerini yönetir, albumpage.php sadece albümleri yönetir. Bu, kodunuzun daha modüler ve okunabilir olmasını sağlar. Bir öğrenci projesinde, kod karmaşıklığını minimumda tutmak önemlidir. Tek bir dosyada hem playlist hem de albüm mantığını yönetmeye çalışmak, if/else bloklarını artırır ve bakımı zorlaştırır.
"Benzerlik" Yorumu: "similarly to the playlist page" ifadesi, genellikle görsel benzerlik ve temel içerik sunumu (şarkıları listeleme) anlamına gelir. PHP dosyasının fiziksel olarak aynı olması gerektiği anlamına gelmez. albumpage.php, playlistpage.php ile aynı CSS stilini ve benzer HTML yapısını kullanarak görsel olarak "benzer" olabilir.
"Kısıtlamayı Uygulama" Yorumu: "adding new songs to the album should not be allowed" kısıtlamasını albumpage.php'de bir "Add Song" formu veya mantığı eklemeyerek doğrudan ve hatasız bir şekilde yerine getirmiş olursunuz. playlistpage.php'nin içine ekleyip, sonra bir if koşuluyla gizlemeye çalışmak, gereksiz kod ve potansiyel mantık hataları anlamına gelebilir.
Ders Kapsamında Kalma: albumpage.php oluşturmak, ders kapsamında öğretilen temel PHP, HTML ve MySQL prensiplerinin dışına çıkmaz. Sadece yeni bir dosya ekleyip, içindeki sorguyu ve HTML yapısını amacına uygun hale getirirsiniz. Hiçbir ileri seviye framework, kütüphane veya teknoloji kullanmıyoruz. Bu, projenizin "saf PHP/MySQL/HTML" kısıtlamasına tamamen uyar.
Özetle:
albumpage.php oluşturmak, ödevin hem "benzerlik" hem de "yeni şarkı eklenemez" kısıtlamalarını temiz ve doğrudan bir şekilde karşılayan, ders seviyesine uygun, iyi bir tasarım seçimidir. Hocanızın "ders dışına çıkmayın" uyarısı, yeni teknoloji veya kütüphaneler kullanmamanız anlamına gelir, fonksiyonları mantıklı dosyalara ayırmanız değil.
albumpage.php'nin Detaylı Açıklaması:
albumpage.php dosyası, artistpage.php'den bir albüme tıklandığında açılacak şekilde tasarlanmıştır. Amacı, o albümdeki tüm şarkıları listelemek ve albümlere şarkı eklenemez kısıtlamasına uymaktır.
session_start(); ve Kimlik Doğrulama:
En başta session_start() çağrılır.
Kullanıcının $_SESSION['user_id'] değişkeni ile oturum açmış olup olmadığı kontrol edilir. Oturum açmamışsa login.php'e yönlendirilir. Bu, uygulamanın genel güvenlik prensibidir.
config.php Dahil Etme:
Veritabanı bağlantı bilgilerini içeren config.php dosyası require_once ile dahil edilir. Bu, tüm PHP dosyalarının aynı veritabanı bağlantısını ve yardımcı fonksiyonları (örn. sanitize_input) kullanmasını sağlar.
album_id Parametresini Alma:
albumpage.php'ye GET metodu ile album_id adında bir parametre gönderilir (örneğin: albumpage.php?album_id=123).
$album_id = isset($_GET['album_id']) ? (int)$_GET['album_id'] : 0; satırı ile bu ID alınır ve güvenli bir şekilde integer'a dönüştürülür.
Eğer album_id geçerli değilse (0 veya yoksa), kullanıcı homepage.php'ye geri yönlendirilir.
Albüm Detaylarını Çekme:
Veritabanından seçilen album_id'ye ait albümün detayları (ALBUMS tablosundan) ve bu albümün sanatçısının adı (ARTISTS tablosundan JOIN ile) çekilir.
Bu sorgu, albüm adını, resmini, çıkış tarihini ve sanatçı adını alır.
Albümdeki Şarkıları Çekme:
Eğer albüm detayları başarıyla çekilmişse, bu album_id'ye sahip tüm şarkılar (SONGS tablosundan) çekilir.
Her şarkının başlığı, süresi, türü ve resmi alınır.
HTML Yapısı ve Veri Gösterimi:
Sayfa, styles.css ile şekillendirilmiş bir container içine yerleştirilir.
Albüm başlığı, sanatçı adı, resim ve çıkış tarihi gösterilir. Sanatçı adına tıklanabilir bir link (artistpage.php) verilir.
"Songs in Album" başlığı altında, albümdeki tüm şarkılar bir liste halinde sunulur. Her şarkı için başlık, tür, süre ve resmi gösterilir.
Her şarkı başlığına tıklanabilir bir link (currentmusic.php) verilir, böylece kullanıcı şarkıyı çalabilir.
Her şarkının yanında "Play" butonu da eklenir, bu da aynı şekilde currentmusic.php'ye yönlendirir.
"Şarkı Ekleme" Kısıtlamasının Uygulanması:
Bu sayfada, playlistpage.php'de bulunan "Add Song" formuna ve ilgili arama çubuğuna hiç yer verilmez. Bu, ödevde belirtilen "adding new songs to the album should not be allowed" kuralını doğrudan ve açık bir şekilde sağlar.
Hata ve Başarı Mesajları:
Veritabanı işlemleri veya veri bulunamaması durumunda kullanıcıya bilgilendirici mesajlar ($message, $message_type) gösterilir.
Bu yapı, albumpage.php'yi ödevin gereksinimlerini karşılayan, temiz, basit ve anlaşılır bir dosya haline getirir.