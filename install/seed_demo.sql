-- Demo veri (isteğe bağlı kurulum)

INSERT INTO `categories` (`id_category`, `id_parent`, `category_name`, `category_link`, `meta_title`, `meta_description`, `active`) VALUES
(1, 0, 'Katalog', 'katalog', '', '', 1),
(2, 1, 'Kozmetik', 'kozmetik', '', '', 1),
(3, 1, 'Vitamin & Takviye', 'vitamin-takviye', '', '', 1),
(4, 1, 'Elektronik', 'elektronik', '', '', 1),
(5, 2, 'Saç Bakım', 'sac-bakim', '', '', 1);

INSERT INTO `brands` (`id_brand`, `brand_name`, `brand_link`, `meta_title`, `meta_description`, `active`) VALUES
(1, 'TechnoMark', 'technomark', '', '', 1),
(2, 'FriSay', 'frisay', '', '', 1),
(3, 'Solgar', 'solgar', '', '', 1);

INSERT INTO `products` (`id_product`, `id_category`, `id_brand`, `product_name`, `short_description`, `meta_title`, `meta_description`, `description`, `product_link`, `price`, `pack_price_override`, `cost`, `doviz`, `doviz_price`, `doviz_old_price`, `doviz_cost`, `old_price`, `vat`, `active`, `stock`, `stock_empty_at`, `cargo_day`, `label`, `product_video`, `stock_code`, `barcode`, `desi`, `product_type`, `virtual_kind`, `virtual_file`, `virtual_file_name`, `virtual_text`) VALUES
(1, 4, 1, 'Jabra Evolve2 65 Kulaklık Için Şarj Standı Usb-C - Siyah 14207-63', 'Jabra Evolve2 65 şarj standı ile kulaklığınızı kolay ve pratik şekilde masanızda tutabilir ve şarj edebilirsiniz.', '', '', '<p>Jabra Evolve2 65 şarj standı ile kulaklığınızı kolay ve pratik şekilde masanızda tutabilir ve şarj edebilirsiniz.</p>', 'jabra-evolve2-65-kulaklik-icin-sarj-standi-usb-c-siyah-14207-63', 899.00, NULL, 0.00, 'try', 899.00, 1099.00, 0.00, 1099.00, 20.00, 1, 48, NULL, 2, '3 Al 2 Öde', '', 'SOLNUTROF30', '8690000000011', 1, 'physical', '', '', '', ''),
(2, 4, 1, 'Jabra Evolve2 Buds Usb-A Ms Truewireless Earbuds Kulak İçi Kulaklık', 'Hibrit çalışmanın yeni standardı. Sektör lideri arama kalitesi için birinci sınıf ses mühendisliği.', '', '', '<p>Hibrit &ccedil;alışmanın yeni standardı. Sekt&ouml;r lideri arama kalitesi i&ccedil;in birinci sınıf ses m&uuml;hendisliği.</p>', 'jabra-evolve2-buds-usb-a-ms-truewireless-earbuds-kulak-ici-kulaklik', 549.00, NULL, 0.00, 'try', 549.00, 0.00, 0.00, 0.00, 20.00, 1, 71, NULL, 1, 'Yeni', '', 'NUTSERUM30', '8690000000028', 1, 'physical', '', '', '', NULL),
(3, 4, 2, 'Apple Airpods 4. Nesil ANC Bluetooth Kulak İçi Kulaklık', 'Aktif gürültü engelleme özellikli kulaklık.', '', '', '<p>40 saate kadar pil &ouml;mr&uuml;, Bluetooth 5.3, şarj kutusu dahil.</p>', 'apple-airpods-4-nesil-anc-bluetooth-kulak-ici-kulaklik', 2356.17, NULL, 0.00, 'usd', 49.99, 59.99, 0.00, 2827.50, 20.00, 1, 22, NULL, 3, 'Çok Satan', '', 'TECH-HP-49', '8690000000035', 2, 'physical', '', '', '', NULL),
(4, 4, 2, 'Sinbo Türk Kahvesi Makinesi SCM 2951', '400ml su alma haznesi ile ortalama 5 fincan kahve demleyeceğiniz eşsiz bir plastik gövdeli kahve makinesi', '', '', '<ul>\r\n<li>5 fincan T&uuml;rk kahve kapasitesi</li>\r\n<li>400 ml su kapasitesi</li>\r\n<li>Kısa s&uuml;rede su kaynatma &ouml;zelliği</li>\r\n<li>Aşırı ısınma ve susuz &ccedil;alışmayı &ouml;nleyen emniyet sistemi</li>\r\n<li>Paslanmaz &ccedil;elik gizli rezistans</li>\r\n<li>A&ccedil;ma/kapama d&uuml;ğmesi</li>\r\n</ul>', 'sinbo-turk-kahvesi-makinesi-scm-2951', 4261.00, NULL, 0.00, 'eur', 79.00, 0.00, 0.00, 0.00, 20.00, 1, 19, NULL, 0, 'Fırsat', '', 'TECH-WATCH79', '8690000000042', 1, 'physical', '', '', '', NULL),
(5, 3, 3, 'Multivitamin Complex 60 Tablet', 'Günlük vitamin ve mineral desteği.', '', '', '<p>60 tabletlik ekonomik ambalaj.</p>', 'multivitamin-complex-60', 329.00, NULL, 0.00, 'try', 329.00, 399.00, 0.00, 399.00, 10.00, 1, 184, NULL, 1, '', '', 'DEMO-MULTI60', '8690000000059', 1, 'physical', '', '', '', NULL),
(6, 3, 1, 'Ocean Vitamin D3', '', '', '', '', 'ocean-vitamin-d3', 250.00, NULL, 0.00, 'try', 250.00, 290.00, 0.00, 290.00, 20.00, 1, 14, NULL, 0, '', '', '15233', '', 1, 'physical', '', '', '', ''),
(7, 4, 2, 'Xiaomi Redmi 13 8 GB RAM 256 GB Yeşil Cep Telefonu', 'Android işletim sistemi ile geniş uygulama ekosistemine erişim sağlayarak, ihtiyacınıza uygun uygulamaları kolayca indirip kullanabilirsiniz', '', '', '<p><strong>Xiaomi Redmi 13</strong> modeli gelişmiş teknik &ouml;zellikleri, modern tasarımı ve ulaşılabilir fiyatı ile akıllı telefon modelleri arasında &ouml;ne &ccedil;ıkar. Geniş ekranı, g&uuml;&ccedil;l&uuml; kamerası ve y&uuml;ksek RAM kapasitesi ile g&uuml;nl&uuml;k kullanıma uygun bir performans sunar. Redmi 13, giriş orta segmentte konumlanarak hem işlevsel hem de estetik beklentilere yanıt verir. &Ouml;nceki modellere g&ouml;re ileri g&uuml;ncellemeler i&ccedil;erir, Redmi Note 13 &ouml;zellikleri ile karşılaştırıldığında da rekabet&ccedil;i bir profil &ccedil;izer. 128 GB ve 256 GB depolama se&ccedil;enekleri ile gelen 8 GB RAM 256 GB versiyonu, geniş depolama ihtiyacı olan kullanıcılar i&ccedil;in ideal se&ccedil;enek sunar. Cihazda yer alan y&uuml;ksek verimli işlemci ve uzun &ouml;m&uuml;rl&uuml; batarya, g&uuml;n boyu kesintisiz kullanım imk&acirc;nı sunar. &Uuml;stelik bu donanıma rağmen fiyatı ulaşılabilir seviyelerde tutulur. Farklı renk se&ccedil;enekleri, modern hatlara sahip tasarımı ve y&uuml;ksek &ccedil;&ouml;z&uuml;n&uuml;rl&uuml;kl&uuml; arka kamerası ile akıllı telefon d&uuml;nyasında iddialı bir alternatif oluşturur. Kullanıcı yorumları da genellikle olumlu y&ouml;nde şekillenir. Bu da modeli daha &ccedil;ekici hale getirir.</p>\r\n<p>&nbsp;</p>\r\n<h2>Xiaomi Redmi 13 Ayırt Edici &Ouml;zellikleri</h2>\r\n<p>Xiaomi Redmi 13, kullanıcılarına y&uuml;ksek performansı şık bir tasarımla beraber sunar. Redmi 13 &ouml;zellikleri, hem teknik hem de tasarımsal a&ccedil;ıdan dikkat &ccedil;ekicidir. Xiaomi Redmi 13, 6.7 in&ccedil;lik geniş ekranı boyutuyla konforlu bir g&ouml;r&uuml;nt&uuml;leme deneyimi sağlar. Full HD+ &ccedil;&ouml;z&uuml;n&uuml;rl&uuml;ğe sahip AMOLED panel, canlı ve keskin renkler sunarak g&ouml;rsel i&ccedil;eriklerin keyfini &ccedil;ıkarmanıza olanak tanır. İnce &ccedil;er&ccedil;eveleri ve zarif tasarımı, telefonun estetik &ccedil;ekiciliğini artırır. Redmi 13 işlemci cihazın kalbinde yer alan Snapdragon 7 Gen 2 işlemcidir. &Ccedil;oklu g&ouml;revlerde akıcılık sağlarken oyun ve grafik yoğun uygulamalar i&ccedil;in &uuml;st&uuml;n performans sunar. Orta segmentte yer alan model, sunduğu &ouml;zelliklerle bir&ccedil;ok kullanıcının beklentisini karşılar. Redmi 13 fiyatı, segmentteki diğer markaların modelleriyle karşılaştırıldığında genellikle daha rekabet&ccedil;i bir konumda yer alır. Rakipleriyle kıyaslandığında işlemci enerji verimliliği ve performans dengesi ile dikkat &ccedil;eker.</p>\r\n<p><strong>Redmi 13</strong> kamera &ouml;zellikleri ise fotoğraf tutkunları i&ccedil;in etkileyici bir deneyim sunar. &Uuml;&ccedil;l&uuml; kamera sistemi, 108 MP ana kamera, geniş a&ccedil;ı ve makro lenslerden oluşur. Gece modu ve yapay zeka destekli sahne tanıma &ouml;zellikleri ile başarılı &ccedil;ekimler yapabilirsiniz. Xiaomi Redmi 13 kamera sisteminin sunduğu bu &ouml;zellikler, rakiplerinden bir adım &ouml;ne &ccedil;ıkmasını sağlar. 5000 mAh kapasiteli bataryası, yoğun kullanımda dahi g&uuml;n&uuml; rahatlıkla &ccedil;ıkarırken 33W hızlı şarj desteği sayesinde kısa s&uuml;rede tekrar kullanıma hazır hale gelir. G&uuml;nl&uuml;k kullanımın yanı sıra mobil oyun oynayan ya da fotoğraf &ccedil;ekmeyi seven kullanıcılar i&ccedil;in g&uuml;&ccedil;l&uuml; bir alternatif oluşturan Redmi 13, b&uuml;t&ccedil;e dostu segmentte &ouml;ne &ccedil;ıkar. Cihaz teknik detayları ve zarif tasarımı, kullanıcılarını memnun eden deneyim vadeder.</p>', 'xiaomi-redmi-13-8-gb-ram-256-gb-yesil-cep-telefonu', 647.52, NULL, 0.00, 'eur', 12.00, 0.00, 0.00, 0.00, 20.00, 1, 99, NULL, 0, '', '', '12555', '333665599', 1, 'virtual', 'text', '', '', 'Teşekkürler'),
(8, 2, 2, 'Jeven Brus Love Me 50ml Kadın Parfümü', 'Jeven brus love me kadın parfümü, her anınızı büyüleyen ve karşı konulmaz bir çekiciliğe sahip özel bir parfümdür.', '', '', '<p class=\"product-description-content\">Jeven brus love me kadın parf&uuml;m&uuml;, her anınızı b&uuml;y&uuml;leyen ve karşı konulmaz bir &ccedil;ekiciliğe sahip &ouml;zel bir parf&uuml;md&uuml;r.</p>\r\n<p class=\"product-description-content\">Tatlı aromalı, &ccedil;i&ccedil;eksi ve vanilyalı notalarıyla &ouml;ne &ccedil;ıkan bu başyapıt, sadece kokusuyla değil aynı zamanda ortama getirdiği yoğun ve feminen atmosferle de b&uuml;y&uuml;k ilgi uyandırıyor.</p>\r\n<p class=\"product-description-content\">Jeven brus love me kadın parf&uuml;m&uuml;, ilk sıktığınız anda tatlı ve canlandırıcı armut aromalı notalarıyla sizi sarar.</p>\r\n<p class=\"product-description-content\">Bu etkileyici başlangı&ccedil;, parf&uuml;m&uuml;n etkisini hemen hissettirir.</p>\r\n<p class=\"product-description-content\">Ardından &ccedil;i&ccedil;eksi ve hafif deri notalarıyla derinleşir ve sofistike bir karakter kazanır.</p>\r\n<p class=\"product-description-content\">En alt notasında ise kehribar ve vanilya ile sıcaklık ve şehvet sunar.</p>\r\n<p class=\"product-description-content\">Jeven brus love me kadın parf&uuml;m&uuml;, hafiflik ve derinlik arayan kadınlar i&ccedil;in m&uuml;kemmel bir se&ccedil;enektir</p>\r\n<p class=\"product-description-content\">&ccedil;&uuml;nk&uuml; her n&uuml;ansı, karakterinizi ve enerjinizi &ouml;n plana &ccedil;ıkarır.</p>\r\n<p class=\"product-description-content\">&Ouml;zel form&uuml;l&uuml; sayesinde, jeven brus love me kadın parf&uuml;m&uuml; g&uuml;n boyu kalıcıdır ve etrafınızdaki herkesi etkisi altına alır.</p>\r\n<p class=\"product-description-content\">D&ouml;rt mevsim kullanıma uygun olan bu parf&uuml;m, iş toplantılarından gece dışarı &ccedil;ıkma planlarına kadar her anınıza zarafet ve g&uuml;&ccedil; katacak.</p>\r\n<p class=\"product-description-content\">Şimdi jeven brus love me kadın parf&uuml;m&uuml;\'n&uuml; keşfedin ve unutulmaz bir kokunun keyfini &ccedil;ıkarın!</p>', 'jeven-brus-love-me-50ml-kadin-parfumu', 271.76, NULL, 0.00, 'try', 271.76, 0.00, 0.00, 0.00, 20.00, 1, 8, NULL, 0, '', '', '2642', '850081345541', 1, 'physical', '', '', '', ''),
(9, 4, 2, 'Xiaomi Redmi Watch 5 Active', 'Gümüş rengi ile şık bir görünüme sahip olan Xiaomi Redmi Watch 5 Active, sağlık ve fitness takibinizi kolaylaştırır.', 'Xiaomi Redmi Watch 5 Active', 'Xiaomi Redmi Watch 5 Active, sağlık ve fitness takibinizi kolaylaştıran şık bir akıllı saat.', '<p>Xiaomi Redmi Watch 5 Active, gelişmiş sağlık ve fitness &ouml;zellikleri ile g&uuml;nl&uuml;k hayatınızı kolaylaştırır. Bu akıllı saat, nabız takip, adım sayar, mesafe &ouml;l&ccedil;er ve kalori yakma takibi gibi &ouml;zelliklerle donatılmıştır.</p>\r\n<p>&Ouml;zellikler:</p>\r\n<ul>\r\n<li>1.43 in&ccedil; AMOLED ekran</li>\r\n<li>5 ATM su ge&ccedil;irmezlik</li>\r\n<li>&Uuml;st&uuml;n pil performansı</li>\r\n<li>&Ccedil;oklu spor modu</li>\r\n</ul>', 'xiaomi-redmi-watch-5-active-silver-akilli-saat-gumus', 600.00, NULL, 350.00, 'try', 600.00, 0.00, 350.00, 0.00, 20.00, 1, 9, NULL, 5, '3 Al 2 Öde', '', '349', '8697595870761', 1, 'physical', '', '2a1c5bcd0d84b9ed945c7d02d5ece6f8.zip', 'fcurrency.zip', ''),
(10, 2, 2, 'Solante Gold SPF 50 Güneş Losyonu 150ml', 'Yüksek koruma sağlayan Solante Gold SPF 50+ Güneş Losyonu, cildinizi UVA/UVB ışınlarına karşı korur. Hassas ciltler dahil tüm cilt tiplerine uygun.', 'Solante Gold SPF 50 Güneş Losyonu', 'Solante Gold SPF 50+ Güneş Losyonu, cildinizi UVA/UVB ışınlarına karşı korur. Yüksek koruma, hassas ciltler için uygun.', '<p>Solante Gold SPF 50+ Losyon, geniş spektrumlu form&uuml;l&uuml; ve &ouml;zel filtreleri ile cildinizi g&uuml;neşin zararlı ışınlarından korur.</p>\r\n<p>&Ouml;zellikleri:</p>\r\n<ul>\r\n<li>&Ccedil;ok y&uuml;ksek UVA/UVB koruma</li>\r\n<li>&Ouml;zel cilt koruması</li>\r\n<li>Antioksidan i&ccedil;erik</li>\r\n<li>Y&uuml;ksek nemlendirici</li>\r\n<li>Parf&uuml;m katkısız</li>\r\n</ul>\r\n<p>Kullanım: Dışarı &ccedil;ıkmadan 15 dakika &ouml;nce cildinize uygulayınız. Her 3-4 saatte bir tekrar uygulayınız.</p>', 'solante-gold-gunes-losyonu-spf-50-faktor-150-ml', 2606.17, NULL, 471.59, 'try', 2606.17, 0.00, 471.59, 0.00, 20.00, 1, 0, NULL, 1, '', '', 'GOLD', '8699278480120', 1, 'pack', '', '', '', '');

INSERT INTO `images` (`id_image`, `id_product`, `cover`) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 1),
(7, 7, 1),
(8, 8, 1),
(9, 9, 1),
(10, 10, 1);

INSERT INTO `modules` (`id_module`, `name`, `version`, `active`, `installed`, `date_add`) VALUES
(2, 'same-category-products', '1.0.0', 1, 1, '2026-06-22 11:48:12'),
(3, 'question', '1.0.0', 1, 1, '2026-06-22 11:48:33'),
(4, 'reviews', '1.0.0', 1, 1, '2026-06-22 11:48:35'),
(5, 'backup-pro', '1.0.0', 1, 1, '2026-07-08 14:15:37'),
(6, 'bankwire', '1.0.0', 1, 1, '2026-07-10 17:36:18'),
(7, 'main-menu', '1.2.0', 1, 1, '2026-07-15 23:05:16'),
(8, 'ai-assistant', '1.0.0', 1, 1, '2026-07-18 23:26:43'),
(9, 'export-excel', '1.0.0', 1, 1, '2026-07-19 16:54:38'),
(10, 'xml-import', '1.0.0', 1, 1, '2026-07-19 22:42:43'),
(11, 'mobil-app', '1.0.0', 1, 1, '2026-07-20 11:18:31'),
(12, 'slider', '1.0.0', 1, 1, '2026-07-24 23:13:39');

INSERT INTO `module_display_hooks` (`id_hook`, `module_name`, `hook_name`, `position`) VALUES
(3, 'same-category-products', 'product', 0),
(4, 'question', 'product_tab', 0),
(5, 'question', 'product_tab_content', 1),
(6, 'reviews', 'product_tab', 0),
(7, 'reviews', 'product_tab_content', 1),
(8, 'reviews', 'product_inf', 2),
(9, 'bankwire', 'order_payment', 0),
(10, 'bankwire', 'order_confirmation', 1),
(11, 'main-menu', 'main_menu', 0),
(12, 'ai-assistant', 'admin_header', 0),
(13, 'export-excel', 'admin_header', 0),
(14, 'mobil-app', 'head.top', 0),
(15, 'mobil-app', 'mobile_menu', 1),
(16, 'slider', 'home_slider', 0),
(17, 'slider', 'home_promo_slider', 1);

INSERT INTO `orders` (`id_order`, `id_user`, `reference`, `status`, `cargo_company`, `tracking_number`, `payment_method`, `customer_name`, `customer_phone`, `customer_email`, `company_name`, `tax_office`, `tax_number`, `address_city`, `address_district`, `address_text`, `note`, `coupon_code`, `coupon_discount`, `promotion_name`, `promotion_discount`, `payment_discount`, `payment_discount_label`, `subtotal`, `shipping`, `total`, `date_add`, `date_delivered`) VALUES
(1, 1, 'FS000001', 2, '', '', 'bank_transfer', 'Ramazan Benek', '0123456789', '', '', '', '', 'Antalya', 'Muratpaşa', 'Deneme mahallesi', '', '', 0.00, '', 0.00, 0.00, '', 4248.69, 0.00, 4248.69, '2026-06-17 16:32:15', NULL);

INSERT INTO `order_detail` (`id_order_detail`, `id_order`, `id_product`, `id_variation`, `product_name`, `variation_label`, `price`, `qty`, `total`, `virtual_delivery`, `download_token`) VALUES
(1, 1, 6, 0, 'Ocean Vitamin D3', '', 4248.69, 1, 4248.69, NULL, '');

INSERT INTO `users` (`id_user`, `user_full_name`, `phone`, `email`, `google_id`, `password`, `image`, `login_code`, `reset_token`, `reset_expires`, `active`, `date_add`) VALUES
(1, 'Ramazan Benek', '0123456789', 'info@frisay.com', NULL, '$2y$10$uZPeEet42SAFoUJhOTNflOL2uE9IpGhSb2CyGu4O/mnnPcLDpDNXW', '', '4569f6e9902d4dc45cc04bf2e9027b8528f8fd919a1c16fdaff22dc19fd130a4', '', NULL, 1, '2026-07-20 14:31:32');

DROP TABLE IF EXISTS `slider_slides`;
CREATE TABLE IF NOT EXISTS `slider_slides` (
  `id_slide` int(11) NOT NULL AUTO_INCREMENT,
  `slide_group` varchar(32) NOT NULL DEFAULT 'hero',
  `title` varchar(255) NOT NULL DEFAULT '',
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `promo_lines` text DEFAULT NULL,
  `button_text` varchar(64) NOT NULL DEFAULT 'View',
  `link_url` varchar(512) NOT NULL DEFAULT '',
  `image_file` varchar(128) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_slide`),
  KEY `slide_group` (`slide_group`,`active`,`position`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `slider_slides` (`id_slide`, `slide_group`, `title`, `subtitle`, `promo_lines`, `button_text`, `link_url`, `image_file`, `position`, `active`, `date_add`) VALUES
(1, 'hero', '', '', '', 'View', '', 'slide_4.jpg', 0, 1, '2026-07-24 23:13:49');