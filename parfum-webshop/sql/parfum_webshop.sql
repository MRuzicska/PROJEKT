-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2026. Jan 30. 13:40
-- Kiszolgáló verziója: 10.4.28-MariaDB
-- PHP verzió: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `parfum_webshop`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;


ALTER TABLE cart_items ADD COLUMN variant_id INT NULL;
ALTER TABLE cart_items ADD CONSTRAINT fk_cart_variant
  FOREIGN KEY (variant_id) REFERENCES product_variants(id);

--
-- A tábla adatainak kiíratása `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(4, 1, 3, 1, '2026-01-29 17:11:18');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- A tábla adatainak kiíratása `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(2, 'Férfi'),
(1, 'Női'),
(3, 'Unisex');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` int(11) NOT NULL,
  `status` enum('NEW','PROCESSING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'NEW',
  `shipping_name` varchar(120) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `shipping_phone` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- A tábla adatainak kiíratása `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `shipping_name`, `shipping_address`, `shipping_phone`, `created_at`) VALUES
(1, 1, 38970, 'NEW', 'Ruzicska Marcell', 'Bethlen Gábor utca', '06204187821', '2026-01-29 15:32:14'),
(2, 7, 22980, 'NEW', 'asd', 'asd', '0612345678', '2026-01-30 11:48:31');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `unit_price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `line_total` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- A tábla adatainak kiíratása `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 1, 12990, 1, 12990),
(2, 1, 2, 15990, 1, 15990),
(3, 1, 3, 9990, 1, 9990),
(4, 2, 1, 12990, 1, 12990),
(5, 2, 3, 9990, 1, 9990);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- A tábla adatainak kiíratása `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `description`, `price`, `stock`, `category_id`, `image_url`, `created_at`) VALUES
(1, 'Rose Bloom EDP 50ml', 'Flora', 'Virágos-gyümölcsös, mindennapokra.', 12990, 20, 1, NULL, NOW()),
(2, 'Night Cedar EDT 100ml', 'Nord', 'Fás, friss, elegáns.', 15990, 20, 2, NULL, NOW()),
(3, 'Citrus Mist EDC 75ml', 'Aqua', 'Citrusos, könnyed.', 9990, 20, 3, NULL, NOW()),
(5, 'Sauvage EDT 100ml', 'Dior', 'Friss, fás, férfias illat.', 35990, 18, 2, NULL, NOW()),
(6, 'La Vie Est Belle EDP 50ml', 'Lancome', 'Édes, virágos, elegáns.', 34990, 12, 1, NULL, NOW()),
(7, 'Acqua Di Gio EDT 100ml', 'Armani', 'Friss tengeri citrusos illat.', 33990, 20, 2, NULL, NOW()),
(8, 'Light Blue EDT 100ml', 'Dolce & Gabbana', 'Friss, citrusos, nyári illat.', 29990, 14, 3, NULL, NOW()),
(9, 'Eros EDT 100ml', 'Versace', 'Édes, mentás, erős férfi illat.', 31990, 16, 2, NULL, NOW()),
(10, 'Good Girl EDP 80ml', 'Carolina Herrera', 'Édes, virágos, elegáns.', 36990, 10, 1, NULL, NOW()),
(11, 'Bleu de Chanel EDT 100ml', 'Chanel', 'Fás-aromás, elegáns férfi illat.', 38990, 13, 2, NULL, NOW()),
(12, 'Alien EDP 60ml', 'Mugler', 'Erős, virágos, különleges illat.', 35990, 11, 1, NULL, NOW()),
(13, 'CK One EDT 100ml', 'Calvin Klein', 'Friss citrusos unisex illat.', 19990, 22, 3, NULL, NOW()),
(14, 'Idole EDP 50ml', 'Lancome', 'Modern, tiszta virágos illat.', 32990, 17, 1, NULL, NOW()),
(15, 'Invictus EDT 100ml', 'Paco Rabanne', 'Friss, sportos férfi illat.', 33990, 19, 2, NULL, NOW()),
(16, 'Olympéa EDP 80ml', 'Paco Rabanne', 'Vaníliás, sós, különleges női illat.', 34990, 14, 1, NULL, NOW()),
(17, 'Hugo Man EDT 75ml', 'Hugo Boss', 'Zöld, friss férfi illat.', 25990, 18, 2, NULL, NOW()),
(18, 'Boss Bottled EDT 100ml', 'Hugo Boss', 'Fás-fűszeres klasszikus férfi illat.', 32990, 20, 2, NULL, NOW()),
(19, 'Daisy EDT 50ml', 'Marc Jacobs', 'Friss virágos női illat.', 28990, 13, 1, NULL, NOW()),
(20, 'Flowerbomb EDP 50ml', 'Viktor & Rolf', 'Intenzív virágos női illat.', 36990, 11, 1, NULL, NOW()),
(21, 'Le Male EDT 125ml', 'Jean Paul Gaultier', 'Édes vaníliás férfi illat.', 33990, 15, 2, NULL, NOW()),
(22, 'Scandal EDP 80ml', 'Jean Paul Gaultier', 'Mézes, édes női illat.', 35990, 12, 1, NULL, NOW()),
(23, 'Terre d Hermes EDT 100ml', 'Hermes', 'Fás-citrusos elegáns férfi illat.', 37990, 16, 2, NULL, NOW()),
(24, 'Mon Guerlain EDP 50ml', 'Guerlain', 'Vaníliás levendulás női illat.', 34990, 15, 1, NULL, NOW()),
(25, 'L Homme EDT 100ml', 'YSL', 'Friss fás férfi illat.', 32990, 14, 2, NULL, NOW()),
(26, 'Libre EDP 50ml', 'YSL', 'Modern virágos női illat.', 35990, 13, 1, NULL, NOW()),
(27, 'Gentleman EDT 100ml', 'Givenchy', 'Fás aromás elegáns férfi illat.', 33990, 16, 2, NULL, NOW()),
(28, 'Angel EDP 50ml', 'Mugler', 'Édes gourmand női illat.', 34990, 12, 1, NULL, NOW()),
(29, 'Legend EDT 100ml', 'Montblanc', 'Friss aromás férfi illat.', 27990, 18, 2, NULL, NOW()),
(30, 'Signature EDP 50ml', 'Montblanc', 'Krémes vaníliás női illat.', 28990, 17, 1, NULL, NOW()),
(31, 'Explorer EDP 100ml', 'Montblanc', 'Fás férfi illat bergamottal.', 29990, 19, 2, NULL, NOW()),
(32, 'Armani Code EDT 75ml', 'Armani', 'Fűszeres elegáns férfi illat.', 33990, 15, 2, NULL, NOW()),
(33, 'Si EDP 50ml', 'Armani', 'Gyümölcsös virágos női illat.', 34990, 12, 1, NULL, NOW()),
(34, 'My Way EDP 50ml', 'Armani', 'Modern virágos női illat.', 35990, 14, 1, NULL, NOW()),
(35, 'Code Parfum 75ml', 'Armani', 'Intenzív fás férfi illat.', 37990, 10, 2, NULL, NOW()),
(36, 'Wanted EDT 100ml', 'Azzaro', 'Fűszeres férfi illat.', 29990, 16, 2, NULL, NOW()),
(37, 'Wanted Girl EDP 50ml', 'Azzaro', 'Virágos gourmand női illat.', 28990, 13, 1, NULL, NOW()),
(38, 'Chrome EDT 100ml', 'Azzaro', 'Friss citrusos férfi illat.', 25990, 18, 2, NULL, NOW()),
(39, 'Toy 2 EDP 50ml', 'Moschino', 'Friss gyümölcsös női illat.', 26990, 17, 1, NULL, NOW()),
(40, 'Toy Boy EDP 100ml', 'Moschino', 'Fűszeres rózsás férfi illat.', 29990, 15, 2, NULL, NOW()),
(41, 'Bright Crystal EDT 90ml', 'Versace', 'Friss virágos női illat.', 31990, 16, 1, NULL, NOW()),
(42, 'Pour Homme EDT 100ml', 'Versace', 'Friss mediterrán férfi illat.', 30990, 17, 2, NULL, NOW()),
(43, 'Crystal Noir EDP 50ml', 'Versace', 'Édes fűszeres női illat.', 32990, 11, 1, NULL, NOW()),
(44, 'K EDT 100ml', 'Dolce & Gabbana', 'Fás citrusos férfi illat.', 32990, 14, 2, NULL, NOW()),
(45, 'The One EDP 50ml', 'Dolce & Gabbana', 'Meleg édes női illat.', 34990, 13, 1, NULL, NOW()),
(46, 'The One EDT 100ml', 'Dolce & Gabbana', 'Fűszeres elegáns férfi illat.', 33990, 12, 2, NULL, NOW()),
(47, 'Omnia Coral EDT 65ml', 'Bvlgari', 'Gyümölcsös virágos női illat.', 28990, 16, 1, NULL, NOW()),
(48, 'Man Wood Essence EDP 100ml', 'Bvlgari', 'Fás zöld férfi illat.', 32990, 13, 2, NULL, NOW()),
(49, 'Goldea EDP 50ml', 'Bvlgari', 'Krémes pézsmás női illat.', 31990, 12, 1, NULL, NOW()),
(50, 'Euphoria EDP 50ml', 'Calvin Klein', 'Édes egzotikus női illat.', 28990, 17, 1, NULL, NOW()),
(51, 'Defy EDT 100ml', 'Calvin Klein', 'Friss modern férfi illat.', 29990, 19, 2, NULL, NOW()),
(52, 'Obsession EDT 100ml', 'Calvin Klein', 'Meleg fűszeres illat.', 27990, 14, 3, NULL, NOW()),
(53, '212 VIP EDP 50ml', 'Carolina Herrera', 'Édes parti illat.', 33990, 11, 1, NULL, NOW()),
(54, '212 Men EDT 100ml', 'Carolina Herrera', 'Friss zöld férfi illat.', 30990, 15, 2, NULL, NOW()),
(55, 'Bad Boy EDT 100ml', 'Carolina Herrera', 'Fűszeres kakaós férfi illat.', 34990, 10, 2, NULL, NOW()),
(56, 'Phantom EDT 100ml', 'Paco Rabanne', 'Édes levendulás férfi illat.', 33990, 16, 2, NULL, NOW()),
(57, 'Lady Million EDP 50ml', 'Paco Rabanne', 'Édes virágos női illat.', 34990, 12, 1, NULL, NOW()),
(58, '1 Million EDT 100ml', 'Paco Rabanne', 'Fűszeres édes férfi illat.', 33990, 14, 2, NULL, NOW()),
(59, 'L Interdit EDP 50ml', 'Givenchy', 'Fehér virágos női illat.', 34990, 13, 1, NULL, NOW()),
(60, 'Pi EDT 100ml', 'Givenchy', 'Vaníliás férfi illat.', 29990, 15, 2, NULL, NOW()),
(61, 'Gentleman Boisee EDP 100ml', 'Givenchy', 'Fás kakaós férfi illat.', 37990, 9, 2, NULL, NOW()),
(62, 'Replica Jazz Club EDT 100ml', 'Maison Margiela', 'Rum és dohány illat.', 39990, 8, 3, NULL, NOW()),
(63, 'Replica By The Fireplace EDT 100ml', 'Maison Margiela', 'Füstös édes illat.', 39990, 8, 3, NULL, NOW()),
(64, 'Replica Lazy Sunday Morning EDT 100ml', 'Maison Margiela', 'Tiszta pézsmás illat.', 38990, 10, 3, NULL, NOW()),
(65, 'Oud Wood EDP 50ml', 'Tom Ford', 'Luxus oud fás illat.', 69990, 7, 3, NULL, NOW()),
(66, 'Black Orchid EDP 50ml', 'Tom Ford', 'Sötét virágos illat.', 64990, 6, 1, NULL, NOW()),
(67, 'Neroli Portofino EDP 50ml', 'Tom Ford', 'Friss citrusos illat.', 69990, 6, 3, NULL, NOW()),
(68, 'Wood Sage & Sea Salt Cologne 100ml', 'Jo Malone', 'Friss tengeri illat.', 45990, 9, 3, NULL, NOW()),
(69, 'Peony & Blush Suede Cologne 100ml', 'Jo Malone', 'Virágos elegáns illat.', 45990, 10, 1, NULL, NOW()),
(70, 'Lime Basil & Mandarin Cologne 100ml', 'Jo Malone', 'Friss citrusos illat.', 45990, 11, 3, NULL, NOW()),
(71, 'Cloud EDP 50ml', 'Ariana Grande', 'Édes krémes illat.', 25990, 18, 1, NULL, NOW()),
(72, 'God Is A Woman EDP 50ml', 'Ariana Grande', 'Gyümölcsös pézsmás illat.', 26990, 17, 1, NULL, NOW()),
(73, 'Thank U Next EDP 50ml', 'Ariana Grande', 'Édes kókuszos illat.', 24990, 19, 1, NULL, NOW());


UPDATE products 
SET image_url = 'images/5.jpg' 
WHERE id = 5;

UPDATE products 
SET image_url = 'images/6.jpg' 
WHERE id = 6;

UPDATE products 
SET image_url = 'images/7.jpg' 
WHERE id = 7;

UPDATE products 
SET image_url = 'images/8.avif' 
WHERE id = 8;

UPDATE products 
SET image_url = 'images/9.jpg' 
WHERE id = 9;

UPDATE products 
SET image_url = 'images/10.webp' 
WHERE id = 10;


UPDATE products 
SET image_url = 'images/11.jpeg' 
WHERE id = 11;

UPDATE products 
SET image_url = 'images/12.jpeg' 
WHERE id = 12;

UPDATE products 
SET image_url = 'images/13.webp' 
WHERE id = 13;

UPDATE products 
SET image_url = 'images/14.jpg' 
WHERE id = 14;

UPDATE products 
SET image_url = 'images/15.jpg' 
WHERE id = 15;

UPDATE products 
SET image_url = 'images/16.jpg' 
WHERE id = 16;

UPDATE products 
SET image_url = 'images/17.jpg' 
WHERE id = 17;

UPDATE products
SET image_url = 'images/18.webp' 
WHERE id = 18;

UPDATE products
SET image_url = 'images/19.jpeg'
WHERE id = 19;

UPDATE products
SET image_url = 'images/20.jpg'
WHERE id = 20;

UPDATE products
SET image_url = 'images/21.jpg'
WHERE id = 21;

UPDATE products
SET image_url = 'images/22.jpg'
WHERE id = 22;

UPDATE products
SET image_url = 'images/23.webp'
WHERE id = 23;

UPDATE products
SET image_url = 'images/24.webp'
WHERE id = 24;

UPDATE products
SET image_url = 'images/25.jpg'
WHERE id = 25;

UPDATE products
SET image_url = 'images/26.avif'
WHERE id = 26;

UPDATE products
SET image_url = 'images/27.jpg'
WHERE id = 27;

UPDATE products
SET image_url = 'images/28.jpg'
WHERE id = 28;

UPDATE products
SET image_url = 'images/29.jpg'
WHERE id = 29;

UPDATE products
SET image_url = 'images/30.avif'
WHERE id = 30;

UPDATE products
SET image_url = 'images/31.jpg'
WHERE id = 31;

UPDATE products
SET image_url = 'images/32.jpeg'
WHERE id = 32;

UPDATE products
SET image_url = 'images/33.jpg'
WHERE id = 33;

UPDATE products
SET image_url = 'images/34.jpg'
WHERE id = 34;

UPDATE products
SET image_url = 'images/35.jpg'
WHERE id = 35;

UPDATE products
SET image_url = 'images/36.webp'
WHERE id = 36;

UPDATE products
SET image_url = 'images/37.jpg'
WHERE id = 37;

UPDATE products
SET image_url = 'images/38.jpg'
WHERE id = 38;

UPDATE products
SET image_url = 'images/39.avif'
WHERE id = 39;

UPDATE products
SET image_url = 'images/40.jpg'
WHERE id = 40;

UPDATE products
SET image_url = 'images/41.webp'
WHERE id = 41;

UPDATE products
SET image_url = 'images/42.jpg'
WHERE id = 42;

UPDATE products
SET image_url = 'images/43.jpeg'
WHERE id = 43;

UPDATE products
SET image_url = 'images/44.jpg'
WHERE id = 44;

UPDATE products
SET image_url = 'images/45.png'
WHERE id = 45;

UPDATE products
SET image_url = 'images/46.jpg'
WHERE id = 46;

UPDATE products
SET image_url = 'images/47.jpg'
WHERE id = 47;

UPDATE products
SET image_url = 'images/48.jpg'
WHERE id = 48;

UPDATE products
SET image_url = 'images/49.jpg'
WHERE id = 49;

UPDATE products
SET image_url = 'images/50.webp'
WHERE id = 50;

UPDATE products
SET image_url = 'images/51.jpeg'
WHERE id = 51;

UPDATE products
SET image_url = 'images/52.jpg'
WHERE id = 52;

UPDATE products
SET image_url = 'images/53.jpg'
WHERE id = 53;

UPDATE products
SET image_url = 'images/54.jpg'
WHERE id = 54;

UPDATE products
SET image_url = 'images/55.jpg' 
WHERE id = 55;

UPDATE products
SET image_url = 'images/56.webp'
WHERE id = 56;

UPDATE products
SET image_url = 'images/57.jpg'
WHERE id = 57;

UPDATE products
SET image_url = 'images/58.jpg'
WHERE id = 58;

UPDATE products
SET image_url = 'images/59.jpg'
WHERE id = 59;

UPDATE products
SET image_url = 'images/60.jpg'
WHERE id = 60;

UPDATE products
SET image_url = 'images/61.jpg'
WHERE id = 61;

UPDATE products
SET image_url = 'images/62.webp'
WHERE id = 62;

UPDATE products
SET image_url = 'images/63.avif'
WHERE id = 63;

UPDATE products
SET image_url = 'images/64.jpg'
WHERE id = 64;

UPDATE products
SET image_url = 'images/65.jpg'
WHERE id = 65;

UPDATE products
SET image_url = 'images/66.jpg'
WHERE id = 66;

UPDATE products
SET image_url = 'images/67.jpg'
WHERE id = 67;

UPDATE products
SET image_url = 'images/68.jpeg'
WHERE id = 68;

UPDATE products
SET image_url = 'images/69.jpg'
WHERE id = 69;

UPDATE products
SET image_url = 'images/70.jpg'
WHERE id = 70;

UPDATE products
SET image_url = 'images/71.jpg'
WHERE id = 71;

UPDATE products
SET image_url = 'images/72.webp' 
WHERE id = 72;

UPDATE products
SET image_url = 'images/73.jpg'
WHERE id = 73;


-- INSERT INTO `categories` (`id`, `name`) VALUES
--(2, 'Férfi'),
--(1, 'Női'),
--(3, 'Unisex');
-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Ruzicska Marcell', 'ruzicskamarci@gmail.com', '$2y$10$t0uaLga.xxfMElwrX6NYuu2toLNcLNAoxfTxw.b1UJ3Q.4srvQViy', 'admin', '2026-01-29 15:21:59'),
(7, 'asd', 'asd@gmail.com', '$2y$10$UAt4J.z9iZDswJSVIrlN0uds417uulyVB48IY8n1SvbaG9U/DqOe.', 'user', '2026-01-30 11:47:27');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- A tábla indexei `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- A tábla indexei `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- A tábla indexei `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT a táblához `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT a táblához `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- Tábla szerkezet ehhez a táblához `product_variants`
--

CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  size_ml INT NOT NULL,
  price INT NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_product_size (product_id, size_ml),
  CONSTRAINT fk_variant_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
);
--------------------------------------------------------

INSERT INTO product_variants (product_id, size_ml, price, stock) VALUES
(1, 50, 19990, 10),
(1, 100, 29990, 7),
(2, 50, 14990, 15),
(2, 100, 15990, 20),
(3, 75, 9990, 22),
(3, 100, 12990, 12),
(5, 50, 21990, 12),
(5, 100, 35990, 18),
(6, 50, 34990, 12),
(6, 100, 43990, 8),
(7, 50, 22990, 10),
(7, 100, 33990, 20),
(8, 50, 21990, 9),
(8, 100, 29990, 14),
(9, 50, 24990, 11),
(9, 100, 31990, 16),
(10, 50, 29990, 5),
(10, 80, 36990, 10),
(11, 50, 25990, 12),
(11, 100, 38990, 13),
(12, 60, 35990, 11),
(13, 50, 17990, 20),
(13, 100, 19990, 22),
(14, 50, 32990, 17),
(14, 100, 39990, 8),
(15, 50, 27990, 14),
(15, 100, 33990, 19),
(16, 50, 24990, 10),
(16, 80, 34990, 14),
(17, 50, 21990, 12),
(17, 75, 25990, 18),
(18, 50, 29990, 14),
(18, 100, 32990, 20),
(19, 50, 28990, 13),
(20, 50, 36990, 11),
(21, 75, 29990, 9),
(21, 125, 33990, 15),
(22, 50, 31990, 10),
(22, 80, 35990, 12),
(23, 50, 29990, 10),
(23, 100, 37990, 16),
(24, 50, 34990, 15),
(25, 50, 29990, 10),
(25, 100, 32990, 14),
(26, 50, 35990, 13),
(27, 50, 29990, 12),
(27, 100, 33990, 16),
(28, 50, 34990, 12),
(29, 50, 25990, 15),
(29, 100, 27990, 18),
(30, 50, 28990, 17),
(31, 50, 24990, 14),
(31, 100, 29990, 19),
(32, 50, 29990, 12),
(32, 75, 33990, 15),
(33, 50, 34990, 12),
(34, 50, 35990, 14),
(35, 75, 37990, 10),
(36, 50, 25990, 12),
(36, 100, 29990, 16),
(37, 50, 28990, 13),
(38, 50, 23990, 15),
(38, 100, 25990, 18),
(39, 50, 26990, 17),
(40, 50, 27990, 12),
(40, 100, 29990, 15),
(41, 50, 31990, 16),
(42, 50, 28990, 12),
(42, 100, 30990, 17),
(43, 50, 32990, 11),
(44, 50, 29990, 10),
(44, 100, 32990, 14),
(45, 50, 34990, 13),
(46, 50, 31990, 10),
(46, 100, 33990, 12),
(47, 65, 28990, 16),
(48, 50, 29990, 11),
(48, 100, 32990, 13),
(49, 50, 31990, 12),
(50, 50, 28990, 17),
(51, 50, 24990, 12),
(51, 100, 29990, 19),
(52, 100, 27990, 14),
(53, 50, 33990, 11),
(54, 50, 27990, 10),
(54, 100, 30990, 15),
(55, 50, 29990, 8),
(55, 100, 34990, 10),
(56, 100, 33990, 16),
(57, 50, 34990, 12),
(58, 50, 29990, 11),
(58, 100, 33990, 14),
(59, 50, 34990, 13),
(60, 50, 27990, 12),
(60, 100, 29990, 15),
(61, 50, 32990, 7),
(61, 100, 37990, 9),
(62, 100, 39990, 8),
(63, 100, 39990, 8),
(64, 100, 38990, 10),
(65, 50, 69990, 7),
(66, 50, 64990, 6),
(67, 50, 69990, 6),
(68, 50, 42990, 8),
(68, 100, 45990, 9),
(69, 50, 42990, 8),
(69, 100, 45990, 10),
(70, 50, 42990, 10),
(70, 100, 45990, 11),
(71, 50, 25990, 18),
(72, 50, 26990, 17),
(73, 50, 24990, 19);



SHOW INDEX FROM cart_items;

SHOW CREATE TABLE cart_items;

ALTER TABLE cart_items
ADD INDEX idx_cart_user_id (user_id);

ALTER TABLE cart_items
DROP INDEX uq_user_product;

ALTER TABLE cart_items
ADD UNIQUE KEY uq_user_product_variant (user_id, product_id, variant_id);

ALTER TABLE cart_items
ADD CONSTRAINT cart_items_ibfk_2
FOREIGN KEY (product_id) REFERENCES products(id)
ON UPDATE CASCADE;