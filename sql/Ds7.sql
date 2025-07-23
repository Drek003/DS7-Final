-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-07-2025 a las 18:35:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ds7`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_next_invoice_number` () RETURNS VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE next_number INT;
    DECLARE current_year INT;
    DECLARE invoice_num VARCHAR(50);
    
    SET current_year = YEAR(CURDATE());
    
    -- Obtener o crear secuencia para el año actual
    INSERT INTO invoice_sequence (year, last_number) 
    VALUES (current_year, 0)
    ON DUPLICATE KEY UPDATE last_number = last_number;
    
    -- Incrementar el número
    UPDATE invoice_sequence 
    SET last_number = last_number + 1 
    WHERE year = current_year;
    
    -- Obtener el nuevo número
    SELECT last_number INTO next_number 
    FROM invoice_sequence 
    WHERE year = current_year;
    
    -- Formatear el número de factura
    SET invoice_num = CONCAT('INV-', current_year, '-', LPAD(next_number, 3, '0'));
    
    RETURN invoice_num;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Computadoras y Laptops', 'Equipos de cómputo portátiles y de escritorio, estaciones de trabajo y accesorios relacionados', 'https://blog.bestbuy.ca/wp-content/uploads/2017/11/versussas20.jpg', '2025-05-30 19:17:20'),
(2, 'Smartphones y Tablets', 'Dispositivos móviles, tablets, e-readers y accesorios para dispositivos móviles', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR0yWL_QR10XlWg3oSbqD15gxWDl54fGsTu_g&amp;s', '2025-05-30 19:17:20'),
(3, 'Audio y Video', 'Auriculares, bocinas, equipos de sonido, cámaras, televisores y equipos de video', 'https://img.freepik.com/vector-premium/12-instrumentos-musicales-establecidos-produccion-musical-contornos-negros-ilustraciones-vectoriales_636653-485.jpg', '2025-05-30 19:17:20'),
(4, 'Gaming', 'Consolas de videojuegos, accesorios gaming, sillas gamer, teclados y mouse gaming', 'https://www.timeoutdoha.com/cloud/timeoutdoha/2021/08/17/s3TzLVrP-gaming-gear-1200x800.jpg', '2025-05-30 19:17:20'),
(5, 'Redes y Conectividad', 'Routers, switches, cables, adaptadores WiFi, equipos de red y telecomunicaciones', 'https://i.blogs.es/76b3bd/asus-rog-rapture-gt-ax11000-pro/650_1200.png', '2025-05-30 19:17:20'),
(6, 'Almacenamiento', 'Discos duros externos, SSD, USB, tarjetas de memoria y soluciones de respaldo', 'https://pcoutlet.com/wp-content/uploads/ssd-vs-hdd-1024x589.webp', '2025-05-30 19:17:20'),
(7, 'Componentes de PC', 'Procesadores, tarjetas gráficas, memoria RAM, motherboards, fuentes de poder', 'https://img.pccomponentes.com/pcblog/6505/componentes-ordenador.jpg', '2025-05-30 19:17:20'),
(8, 'Accesorios y Periféricos', 'Teclados, mouse, monitores, webcams, bases para laptop, hubs USB', 'https://i.ibb.co/YTcD5qdq/Perifericos-tecnol-gicos-en-blanco-y-negro.png', '2025-05-30 19:17:20'),
(9, 'Smart Home', 'Dispositivos inteligentes para el hogar, asistentes de voz, cámaras de seguridad, domótica', 'https://www.beachesliving.ca/beacheslife/wp-content/uploads/2018/03/apple-homepod-google-home-amazon-echo.png', '2025-05-30 19:17:20'),
(10, 'Wearables y Fitness', 'Smartwatches, fitness trackers, auriculares deportivos, dispositivos de salud', 'https://dy6o3vurind23.cloudfront.net/img/developerimg/choco_life_20161214074908_db/mebase/CustomSectionStyle/Images/smart_wearabldsses.webp', '2025-05-30 19:17:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Panamá',
  `tax_id` varchar(50) DEFAULT NULL,
  `customer_type` enum('individual','empresa') DEFAULT 'individual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `country`, `tax_id`, `customer_type`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Juan Pérez', 'juan.perez@email.com', 'cliente123', '+507 6123-4567', 'Calle 50, Edificio Plaza 2000, Piso 15', 'Ciudad de Panamá', 'Panamá', NULL, 'individual', 'Cliente frecuente - Descuentos aplicables', '2025-07-20 13:55:42', '2025-07-20 16:06:05'),
(2, 'María González', 'maria.gonzalez@email.com', 'maria2024', '+507 6234-5678', 'Vía España, Centro Comercial El Dorado', 'Ciudad de Panamá', 'Panamá', NULL, 'individual', 'Prefiere pago en efectivo', '2025-07-20 13:55:42', '2025-07-20 16:06:05'),
(3, 'Carlos Rodríguez', 'carlos.rodriguez@email.com', 'carlos456', '+507 6345-6789', 'Av. Balboa, Torre de las Américas', 'Ciudad de Panamá', 'Panamá', NULL, 'individual', 'Ejecutivo de empresa tecnológica', '2025-07-20 13:55:42', '2025-07-20 16:06:05'),
(4, 'Tecno-Y', 'tecnoY@gmail.com', 'tecno2024', '+50768382443', 'Mi casa en el cerro Canajagua', 'Panamá', 'Panamá', '8-991-1598', 'empresa', 'Cliente frecuente.', '2025-07-20 15:36:17', '2025-07-20 16:06:05'),
(5, 'Empresas Épsilon', 'adrianalbertojimenez@gmail.com', 'epsilon123', '+50768382443', 'Empresas Épsilon, chorrera, junto al pio pio', 'Panamá', 'Panamá', '4-713-434', 'empresa', 'Empresa del Lic. Adrian Jimenez M.', '2025-07-20 15:40:13', '2025-07-20 16:23:30'),
(11, 'nico', 'nico@gmail.com', 'n12345', '12345678', 'panama', 'Ciudad de Panamá', 'Panamá', '8-1010-2029', 'individual', 'Cliente registrado vía web', '2025-07-23 16:07:58', '2025-07-23 16:07:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 7.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `invoice_status` enum('borrador','enviada','pagada','vencida','cancelada') DEFAULT 'borrador',
  `payment_terms` varchar(100) DEFAULT 'Pago inmediato',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `invoices`
--

INSERT INTO `invoices` (`id`, `sale_id`, `invoice_number`, `invoice_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `discount_amount`, `total_amount`, `invoice_status`, `payment_terms`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'INV-2025-001', '2025-07-20 13:55:42', NULL, 1324.97, 7.00, 95.40, 0.00, 1420.37, 'pagada', 'Pago inmediato', 'Factura pagada con tarjeta de crédito', '2025-07-20 13:55:42', '2025-07-20 13:55:42'),
(2, 2, 'INV-2025-002', '2025-07-20 13:55:42', NULL, 899.99, 7.00, 64.80, 50.00, 914.79, 'pagada', 'Pago inmediato', 'Descuento aplicado - Cliente frecuente', '2025-07-20 13:55:42', '2025-07-20 13:55:42'),
(3, 3, 'INV-2025-003', '2025-07-20 13:55:42', NULL, 209.98, 7.00, 15.12, 0.00, 225.10, 'enviada', 'Pago a 15 días', 'Pendiente de pago por transferencia', '2025-07-20 13:55:42', '2025-07-20 13:55:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_sequence`
--

CREATE TABLE `invoice_sequence` (
  `id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0,
  `prefix` varchar(10) DEFAULT 'INV',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `invoice_sequence`
--

INSERT INTO `invoice_sequence` (`id`, `year`, `last_number`, `prefix`, `created_at`, `updated_at`) VALUES
(1, 2025, 3, 'INV', '2025-07-20 13:55:42', '2025-07-20 13:55:42');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `pending_invoices`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `pending_invoices` (
`id` int(11)
,`invoice_number` varchar(50)
,`invoice_date` datetime
,`due_date` datetime
,`total_amount` decimal(10,2)
,`invoice_status` enum('borrador','enviada','pagada','vencida','cancelada')
,`customer_name` varchar(100)
,`customer_email` varchar(100)
,`customer_phone` varchar(20)
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 5,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `min_stock`, `image`, `category_id`, `created_at`) VALUES
(1, 'MacBook Air M2', 'Laptop ultradelgada con chip M2, pantalla de 13.6 pulgadas, 8GB RAM, 256GB SSD', 1199.99, 15, 5, 'https://macstore.com.pa/cdn/shop/files/IMG-16751954_53071053-8c26-4b51-9a16-5b6230c0aa2f_550x.jpg?v=1741187967', 1, '2025-05-30 19:20:55'),
(2, 'Dell XPS 13', 'Laptop premium con procesador Intel Core i7, 16GB RAM, 512GB SSD, pantalla InfinityEdge', 1299.99, 12, 5, 'https://i.dell.com/is/image/DellContent/content/dam/ss2/product-images/dell-client-products/notebooks/xps-notebooks/9345/media-gallery/touch/gray/notebook-xps-13-9345-t-gray-gallery-4.psd?fmt=png-alpha&amp;pscan=auto&amp;scl=1&amp;hei=402&amp;wid=678&amp;', 1, '2025-05-30 19:20:55'),
(3, 'HP Pavilion Desktop', 'PC de escritorio con AMD Ryzen 5, 8GB RAM, 1TB HDD, tarjeta gráfica integrada', 649.99, 25, 5, 'https://ssl-product-images.www8-hp.com/digmedialib/prodimg/lowres/c06426060.png?imdensity=1&amp;impolicy=Png_Res', 1, '2025-05-30 19:20:55'),
(4, 'Lenovo ThinkPad X1 Carbon', 'Laptop empresarial con Intel Core i5, 16GB RAM, 512GB SSD, pantalla 14 pulgadas', 1399.99, 8, 5, 'https://p1-ofp.static.pub/medias/bWFzdGVyfHJvb3R8MjgwNzc3fGltYWdlL3BuZ3xoOWUvaGI4LzE0MDgwNDY4MDkwOTEwLnBuZ3w4MzIxNDk3M2E4NTA5ZjY5ZjYzYTc5ZDk3MWYwNjk3OTczNzYxMjM2MDU0ZTQ0MDBjMTU0ZmRhZWUxM2Q2ODYx/lenovo-laptop-thinkpad-x1-carbon-gen-8-hero.png', 1, '2025-05-30 19:20:55'),
(5, 'ASUS ROG Strix Desktop', 'PC Gaming con Intel Core i7, 32GB RAM, RTX 4060, 1TB SSD', 1899.99, 6, 5, 'https://www.asus.com/media/Odin/Websites/global/Series/52/P_setting_xxx_0_90_end_185.png?webp', 1, '2025-05-30 19:20:55'),
(6, 'iPhone 15 Pro', 'Smartphone con chip A17 Pro, cámara de 48MP, pantalla de 6.1 pulgadas, 128GB', 999.99, 30, 5, 'https://cdsassets.apple.com/live/7WUAS350/images/tech-specs/iphone_15_pro.png', 2, '2025-05-30 19:20:55'),
(7, 'Samsung Galaxy S24', 'Android flagship con Snapdragon 8 Gen 3, 8GB RAM, 256GB almacenamiento', 899.99, 35, 5, 'https://rodelag.com/cdn/shop/files/PS0012159_0.png?v=1712180503', 2, '2025-05-30 19:20:55'),
(8, 'iPad Air', 'Tablet con chip M1, pantalla de 10.9 pulgadas, 64GB WiFi, compatible con Apple Pencil', 599.99, 20, 5, 'https://www.apple.com/newsroom/images/product/ipad/standard/apple_new-ipad-air_new-design_09152020.jpg.news_app_ed.jpg', 2, '2025-05-30 19:20:55'),
(9, 'Google Pixel 8', 'Smartphone Android puro con cámara avanzada con IA, 8GB RAM, 128GB', 699.99, 18, 5, 'https://m.media-amazon.com/images/I/71SfoZu9a3L._AC_SL1500_.jpg', 2, '2025-05-30 19:20:55'),
(10, 'Samsung Galaxy Tab S9', 'Tablet Android premium con S Pen incluido, pantalla AMOLED de 11 pulgadas', 799.99, 14, 5, 'https://shop.samsung.com/latin/pub/media/catalog/product/cache/a69170b4a4f0666a52473c2224ba9220/0/0/009-galaxy-tabs9-graphite-combo_4.png', 2, '2025-05-30 19:20:55'),
(11, 'Sony WH-1000XM5', 'Auriculares inalámbricos con cancelación de ruido premium, 30h de batería', 399.99, 22, 5, 'https://www.sony.com.pa/image/6145c1d32e6ac8e63a46c912dc33c5bb?fmt=pjpeg&amp;wid=165&amp;bgcolor=FFFFFF&amp;bgc=FFFFFF', 3, '2025-05-30 19:20:55'),
(12, 'JBL Flip 6', 'Bocina portátil Bluetooth resistente al agua, sonido potente y graves profundos', 129.99, 45, 5, 'https://www.jbl.com.pa/dw/image/v2/AAUJ_PRD/on/demandware.static/-/Sites-masterCatalog_Harman/default/dwcb0a3b1e/2_JBL_FLIP6_3_4_RIGHT_WHITE_30192_x1.png?sw=537&amp;sfrm=png', 3, '2025-05-30 19:20:55'),
(13, 'Canon EOS R50', 'Cámara mirrorless para creadores de contenido, 24.2MP, grabación 4K', 679.99, 10, 5, 'https://www.panafoto.com/media/catalog/product/cache/d9d0e56184dc11f5b1bf90662cef36b8/1/5/151816-001.jpg', 3, '2025-05-30 19:20:55'),
(14, 'Samsung 55&quot; QLED 4K', 'Smart TV QLED de 55 pulgadas con tecnología Quantum Dot, HDR10+', 899.99, 8, 5, 'https://images.samsung.com/is/image/samsung/p6pim/latin/qn55q60dapxpa/gallery/latin-qled-tv-qn55q60dapxpa-qn--q--dagxzs-542805111?$684_547_PNG$', 3, '2025-05-30 19:20:55'),
(15, 'Bose SoundLink Mini', 'Bocina Bluetooth compacta con sonido premium y 12 horas de reproducción', 199.99, 28, 5, 'https://assets.bose.com/content/dam/Bose_DAM/Web/consumer_electronics/global/products/speakers/soundlink_mini_ii/product_silo_images/soundlink_mini_II_carbon_EC.psd/jcr:content/renditions/cq5dam.web.600.600.png', 3, '2025-05-30 19:20:55'),
(16, 'PlayStation 5', 'Consola de videojuegos de nueva generación con SSD ultrarrápido y gráficos 4K', 499.99, 5, 5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRljFdzeedf61z2hUqsspeGJ46wg-0WOpA9nA&amp;s', 4, '2025-05-30 19:20:55'),
(17, 'Xbox Series X', 'Consola Xbox más potente con 12 TFLOPS, compatibilidad 4K/120fps', 499.99, 7, 5, 'https://birriagamers.com/cdn/shop/products/Consola_Xbox_Series_X_1TB_panama_birriagamers_videojuegos.jpg?v=1723330241', 4, '2025-05-30 19:20:55'),
(18, 'Razer DeathAdder V3', 'Mouse gaming ergonómico con sensor Focus Pro 30K, switches ópticos', 99.99, 40, 5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTMIYoBNPCAWoTqh2NGVpQf-pupvGMkPCin8w&amp;s', 4, '2025-05-30 19:20:55'),
(19, 'Corsair K95 RGB', 'Teclado mecánico gaming con switches Cherry MX, iluminación RGB personalizable', 199.99, 32, 5, 'https://assets.corsair.com/image/upload/f_auto,q_auto/content/CH-9000220-NA-CGK95-RGB-NA-01.png', 4, '2025-05-30 19:20:55'),
(20, 'Secretlab Titan Evo', 'Silla gaming ergonómica con soporte lumbar ajustable y materiales premium', 449.99, 12, 5, 'https://images.secretlab.co/turntable/tr:n-w_450/R22PU-CP2077_02.jpg', 4, '2025-05-30 19:20:55'),
(21, 'ASUS AX6000 Router', 'Router WiFi 6 de doble banda con velocidades hasta 6000 Mbps', 299.99, 18, 5, 'https://dlcdnwebimgs.asus.com/gain/4b5404a5-c8b3-4353-8c3e-74527fc51249/w692', 5, '2025-05-30 19:20:55'),
(22, 'TP-Link Deco M5', 'Sistema mesh WiFi para hogar completo, cobertura hasta 500m², pack de 3', 179.99, 25, 5, 'https://static.tp-link.com/M5_EU_1.0_01_normal_1524021448229j.jpg', 5, '2025-05-30 19:20:55'),
(23, 'Netgear 24-Port Switch', 'Switch Gigabit no administrado de 24 puertos para redes empresariales', 149.99, 15, 5, 'https://www.netgear.com/zone1/cid/fit/1024x633/to/jpg/https/www.netgear.com/es/media/GS324p_productcarousel_hero_image_tcm174-113255.png', 5, '2025-05-30 19:20:55'),
(24, 'USB-C to Ethernet Adapter', 'Adaptador USB-C a Ethernet Gigabit para laptops sin puerto RJ45', 29.99, 60, 5, 'https://www.multimax.net/cdn/shop/files/PSN0106490.jpg?v=1683384031', 5, '2025-05-30 19:20:55'),
(25, 'Cat 6 Ethernet Cable 10ft', 'Cable de red categoría 6 de 3 metros, alta velocidad y baja latencia', 15.99, 100, 5, 'https://m.media-amazon.com/images/I/71qWbUP8+wL._SX522_.jpg', 5, '2025-05-30 19:20:55'),
(26, 'Samsung T7 Portable SSD 1TB', 'SSD externo portátil con velocidades hasta 1,050 MB/s, USB-C', 129.99, 30, 5, 'https://image-us.samsung.com/SamsungUS/home/computing/01242022/MU-PC500T_003_R-Perspective_Black.jpg?$product-details-jpg$', 6, '2025-05-30 19:20:55'),
(27, 'WD Black 2TB External HDD', 'Disco duro externo para gaming con 2TB de capacidad, USB 3.2', 89.99, 35, 5, 'https://www.westerndigital.com/content/dam/store/en-us/assets/products/desktop/wd-black-p10-game-drive-usb-3-2-hdd/gallery/2tb/WD-Black-P10-Game-Drive-2TB-Hero.png.wdthumb.1280.1280.webp', 6, '2025-05-30 19:20:55'),
(28, 'SanDisk Ultra 128GB USB', 'Memoria USB 3.0 de alta velocidad con 128GB de capacidad', 24.99, 75, 5, 'https://electroimport.com.pa/wp-content/uploads/2024/04/4b91fbbd-cb9d-4006-97df-2138de6b74ed.jpg', 6, '2025-05-30 19:20:55'),
(29, 'Kingston 64GB MicroSD', 'Tarjeta MicroSD Clase 10 UHS-I de 64GB para smartphones y cámaras', 19.99, 90, 5, 'https://cdn.panacompu.com/cdn-img/pv/kingston-canvas-micro-sd-64gb-box.jpg?width=550&amp;height=400&amp;fixedwidthheight=false', 6, '2025-05-30 19:20:55'),
(30, 'Seagate Backup Plus 5TB', 'Disco duro externo de escritorio con 5TB, ideal para respaldos masivos', 139.99, 20, 5, 'https://m.media-amazon.com/images/I/41zjnTo3K4L.__AC_SY300_SX300_QL70_FMwebp_.jpg', 6, '2025-05-30 19:20:55'),
(31, 'AMD Ryzen 7 7700X', 'Procesador de 8 núcleos y 16 hilos, frecuencia base 4.5GHz, socket AM5.', 350.00, 25, 5, 'https://m.media-amazon.com/images/I/51lXCYo7GkL._AC_SX466_.jpg', 7, '2025-05-30 19:20:55'),
(32, 'NVIDIA RTX 4070', 'Tarjeta gráfica para gaming 1440p con 12GB GDDR6X y tecnología DLSS 3', 599.99, 10, 5, 'https://m.media-amazon.com/images/I/71Sqt8X-MfL._AC_SX466_.jpg', 7, '2025-05-30 19:20:55'),
(33, 'Corsair Vengeance 32GB DDR4', 'Kit de memoria RAM DDR4-3200 de 32GB (2x16GB) para gaming y trabajo', 119.99, 42, 5, 'https://m.media-amazon.com/images/I/71Z9y+sUBuS._AC_SX466_.jpg', 7, '2025-05-30 19:20:55'),
(34, 'ASUS ROG Strix B650', 'Motherboard ATX para AMD AM5 con WiFi 6E, múltiples slots PCIe', 229.99, 16, 5, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxojtDyXYAyg1GqrMnIrFqAxUlUprEYQ8JxQ&amp;s', 7, '2025-05-30 19:20:55'),
(35, 'EVGA 750W Gold PSU', 'Fuente de poder modular 80+ Gold de 750W, certificada y eficiente', 149.99, 22, 5, 'https://m.media-amazon.com/images/I/61wCOVcyvFL.__AC_SX300_SY300_QL70_FMwebp_.jpg', 7, '2025-05-30 19:20:55'),
(36, 'Logitech MX Master 3S', 'Mouse inalámbrico de precisión con desplazamiento MagSpeed y 8K DPI', 99.99, 50, 5, 'https://m.media-amazon.com/images/I/61+OT7FPABL._AC_SY300_SX300_.jpg', 8, '2025-05-30 19:20:55'),
(37, 'Keychron K8 Mechanical', 'Teclado mecánico inalámbrico 75% con switches Gateron, retroiluminado', 89.99, 38, 5, 'https://www.keychron.com/cdn/shop/products/Keychron-K8-tenkeyless-wireless-mechanical-keyboard-for-Mac-Windows-iOS-RGB-white-backlight-with-gateron-Optical-switch-brown..jpg?v=1657360255&amp;width=900', 8, '2025-05-30 19:20:55'),
(38, 'LG 27&quot; 4K Monitor', 'Monitor IPS 4K de 27 pulgadas con USB-C, HDR10 y ajuste de altura', 399.99, 12, 5, 'https://www.lg.com/content/dam/channel/wcms/es/images/monitores/27ul650-w_aeu_eees_es_c/gallery/large07.jpg', 8, '2025-05-30 19:20:55'),
(39, 'Logitech C920 HD Webcam', 'Cámara web Full HD 1080p con micrófono integrado y enfoque automático', 79.99, 55, 5, 'https://www.quickservicepanama.com/cdn/shop/products/Camara-Web-Logitech-HD-Pro-Webcam-C920-960-000764.jpg?v=1703363548&amp;width=640', 8, '2025-05-30 19:20:55'),
(40, 'Anker USB-C Hub 7-in-1', 'Hub USB-C con HDMI 4K, puertos USB 3.0, lector SD y carga PD', 59.99, 45, 5, 'https://m.media-amazon.com/images/I/61-SlepAfrL._AC_SX466_.jpg', 8, '2025-05-30 19:20:55'),
(41, 'Amazon Echo Dot 5ta Gen', 'Asistente de voz inteligente con Alexa, sonido mejorado y hub Zigbee', 49.99, 60, 5, 'https://m.media-amazon.com/images/I/710exCeNPJL._AC_SY450_.jpg', 9, '2025-05-30 19:20:55'),
(42, 'Philips Hue Starter Kit', 'Kit de iluminación inteligente con 3 bombillas LED y puente Hue', 199.99, 18, 5, 'https://www.assets.signify.com/is/image/Signify/8719514291331-929002468803A-Philips-Hue_WCA-9W-A60-E27-3set-sb-EU-RTP?wid=1280&amp;hei=960&amp;qlt=82', 9, '2025-05-30 19:20:55'),
(43, 'Ring Video Doorbell', 'Timbre inteligente con cámara HD, detección de movimiento y audio bidireccional', 179.99, 25, 5, 'https://alexapanama.com/admin/imagenes/O9M8D2P314B08SSDZ6R8-1.jpg', 9, '2025-05-30 19:20:55'),
(44, 'Google Nest Thermostat', 'Termostato inteligente programable con control desde smartphone', 129.99, 30, 5, 'https://micellpty.com/wp-content/uploads/2024/10/60558859.jpg', 9, '2025-05-30 19:20:55'),
(45, 'TP-Link Kasa Smart Plug', 'Enchufe inteligente WiFi con control remoto y programación de horarios', 14.99, 80, 5, 'https://static.tp-link.com/HS100(US)2.0-package_1508143441338j.jpg', 9, '2025-05-30 19:20:55'),
(46, 'Apple Watch Series 9', 'Smartwatch con pantalla Always-On, GPS, monitor de salud avanzado', 399.99, 20, 5, 'https://i0.wp.com/celularespanama.net/wp-content/uploads/2024/01/APPLE_MR8X3.jpg?fit=1100%2C1024&amp;ssl=1', 10, '2025-05-30 19:20:55'),
(47, 'Fitbit Charge 6', 'Pulsera de actividad con GPS, monitor de frecuencia cardíaca y 6 días de batería', 159.99, 35, 5, 'https://images-cdn.ubuy.co.in/65637a48f2e47b1ee16710ac-fitbit-charge-6-fitness-tracker-black.jpg', 10, '2025-05-30 19:20:55'),
(48, 'Samsung Galaxy Watch 6', 'Smartwatch Android con pantalla AMOLED, Wear OS y sensores de salud', 329.99, 22, 5, 'https://images.samsung.com/sa_en/galaxy-watch6/feature/galaxy-watch6-kv-pc.jpg', 10, '2025-05-30 19:20:55'),
(49, 'Garmin Forerunner 255', 'Reloj GPS para running con métricas avanzadas y autonomía de 14 días', 349.99, 15, 5, 'https://m.media-amazon.com/images/I/41oPOwZvB0L._SS400_.jpg', 10, '2025-05-30 19:20:55'),
(50, 'Jabra Elite 85t', 'Earbuds inalámbricos con cancelación de ruido adaptativa y 31h de batería', 179.99, 28, 5, 'https://m.media-amazon.com/images/I/71-3C3aYveL.jpg', 10, '2025-05-30 19:20:55'),
(51, 'ROG Xbox Ally | 1T', '¡Descubre la ROG Ally, tu puerta de entrada a la élite del gaming portátil! Con la potencia de un PC y la versatilidad de una consola, juega tus títulos favoritos de Xbox y más, donde sea y cuando sea. ¡Rendimiento inigualable en tus manos', 649.99, 8, 5, 'https://cms-assets.xboxservices.com/assets/8b/26/8b26b482-20a7-4182-a64a-c97f2a221f59.png?n=9856321_Hero-Gallery-0_V01-01_1400x800.png', 4, '2025-06-13 20:02:25'),
(52, 'Galaxy S24 Fe', 'teléfono inteligente Android desbloqueado de 256 GB, cámara de alta resolución de 50 MP, batería de larga duración, pantalla de visualización más brillante', 700.00, 25, 5, 'https://m.media-amazon.com/images/I/51SG-FYxj7L._AC_SX679_.jpg', 2, '2025-06-25 02:15:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('efectivo','tarjeta_credito','tarjeta_debito','transferencia','cheque') DEFAULT 'efectivo',
  `payment_status` enum('pendiente','pagado','cancelado','reembolsado') DEFAULT 'pendiente',
  `notes` text DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `sale_date`, `total_amount`, `tax_amount`, `discount_amount`, `final_amount`, `payment_method`, `payment_status`, `notes`, `invoice_number`, `created_at`, `updated_at`) VALUES
(1, 1, 'Juan Pérez', 'juan.perez@email.com', '+507 6123-4567', '2025-07-20 13:55:42', 1324.97, 95.40, 0.00, 1420.37, 'tarjeta_credito', 'pagado', 'Venta de laptop MacBook Air M2 y accesorios', 'INV-2025-001', '2025-07-20 13:55:42', '2025-07-20 13:55:42'),
(2, 1, 'María González', 'maria.gonzalez@email.com', '+507 6234-5678', '2025-07-20 13:55:42', 899.99, 64.80, 50.00, 914.79, 'efectivo', 'pagado', 'Descuento por cliente frecuente', 'INV-2025-002', '2025-07-20 13:55:42', '2025-07-20 13:55:42'),
(3, 2, 'Carlos Rodríguez', 'carlos.rodriguez@email.com', '+507 6345-6789', '2025-07-20 13:55:42', 209.98, 15.12, 0.00, 225.10, 'transferencia', 'pendiente', 'Pago pendiente por transferencia', 'INV-2025-003', '2025-07-20 13:55:42', '2025-07-20 13:55:42');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `sales_summary`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `sales_summary` (
`id` int(11)
,`invoice_number` varchar(50)
,`customer_name` varchar(100)
,`sale_date` datetime
,`total_amount` decimal(10,2)
,`tax_amount` decimal(10,2)
,`discount_amount` decimal(10,2)
,`final_amount` decimal(10,2)
,`payment_method` enum('efectivo','tarjeta_credito','tarjeta_debito','transferencia','cheque')
,`payment_status` enum('pendiente','pagado','cancelado','reembolsado')
,`seller_name` varchar(50)
,`total_items` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sale_details`
--

CREATE TABLE `sale_details` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_per_item` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sale_details`
--

INSERT INTO `sale_details` (`id`, `sale_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`, `discount_per_item`, `created_at`) VALUES
(1, 1, 1, 'MacBook Air M2', 1199.99, 1, 1199.99, 0.00, '2025-07-20 13:55:42'),
(2, 1, 36, 'Logitech MX Master 3S', 99.99, 1, 99.99, 0.00, '2025-07-20 13:55:42'),
(3, 1, 28, 'SanDisk Ultra 128GB USB', 24.99, 1, 24.99, 0.00, '2025-07-20 13:55:42'),
(4, 2, 7, 'Samsung Galaxy S24', 899.99, 1, 899.99, 0.00, '2025-07-20 13:55:42'),
(5, 3, 12, 'JBL Flip 6', 129.99, 1, 129.99, 0.00, '2025-07-20 13:55:42'),
(6, 3, 39, 'Logitech C920 HD Webcam', 79.99, 1, 79.99, 0.00, '2025-07-20 13:55:42');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `seller_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `seller_stats` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`total_sales` bigint(21)
,`total_revenue` decimal(32,2)
,`avg_sale_amount` decimal(14,6)
,`last_sale_date` datetime
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_time` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `top_selling_products`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `top_selling_products` (
`id` int(11)
,`name` varchar(100)
,`price` decimal(10,2)
,`category_name` varchar(100)
,`total_sold` decimal(32,0)
,`total_revenue` decimal(32,2)
,`times_sold` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','consultor','cliente') DEFAULT 'consultor',
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `customer_id`, `created_at`) VALUES
(1, 'admin_tech', 'admin@codecorp.com', 'admin123', 'admin', NULL, '2025-05-30 19:37:03'),
(2, 'consultor_V', 'consultor@codecorp.com', 'con123', 'consultor', NULL, '2025-05-30 19:37:03'),
(3, 'juan_perez', 'juan.perez@email.com', 'cliente123', 'cliente', 1, '2025-07-20 16:06:06'),
(4, 'maria_gonzalez', 'maria.gonzalez@email.com', 'maria2024', 'cliente', 2, '2025-07-20 16:06:06'),
(5, 'carlos_rodriguez', 'carlos.rodriguez@email.com', 'carlos456', 'cliente', 3, '2025-07-20 16:06:06'),
(6, 'tecno_y', 'tecnoY@gmail.com', 'tecno2024', 'cliente', 4, '2025-07-20 16:06:06'),
(7, 'epsilon_admin', 'adrianalbertojimenez@gmail.com', 'epsilon123', 'cliente', 5, '2025-07-20 16:06:06'),
(23, 'nico12', 'nico@gmail.com', 'n12345', 'cliente', 11, '2025-07-23 16:07:58');

-- --------------------------------------------------------

--
-- Estructura para la vista `pending_invoices`
--
DROP TABLE IF EXISTS `pending_invoices`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_invoices`  AS SELECT `i`.`id` AS `id`, `i`.`invoice_number` AS `invoice_number`, `i`.`invoice_date` AS `invoice_date`, `i`.`due_date` AS `due_date`, `i`.`total_amount` AS `total_amount`, `i`.`invoice_status` AS `invoice_status`, `s`.`customer_name` AS `customer_name`, `s`.`customer_email` AS `customer_email`, `s`.`customer_phone` AS `customer_phone`, to_days(curdate()) - to_days(`i`.`due_date`) AS `days_overdue` FROM (`invoices` `i` join `sales` `s` on(`i`.`sale_id` = `s`.`id`)) WHERE `i`.`invoice_status` in ('enviada','vencida') ORDER BY `i`.`due_date` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `sales_summary`
--
DROP TABLE IF EXISTS `sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_summary`  AS SELECT `s`.`id` AS `id`, `s`.`invoice_number` AS `invoice_number`, `s`.`customer_name` AS `customer_name`, `s`.`sale_date` AS `sale_date`, `s`.`total_amount` AS `total_amount`, `s`.`tax_amount` AS `tax_amount`, `s`.`discount_amount` AS `discount_amount`, `s`.`final_amount` AS `final_amount`, `s`.`payment_method` AS `payment_method`, `s`.`payment_status` AS `payment_status`, `u`.`username` AS `seller_name`, count(`sd`.`id`) AS `total_items` FROM ((`sales` `s` left join `users` `u` on(`s`.`user_id` = `u`.`id`)) left join `sale_details` `sd` on(`s`.`id` = `sd`.`sale_id`)) GROUP BY `s`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `seller_stats`
--
DROP TABLE IF EXISTS `seller_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `seller_stats`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, count(`s`.`id`) AS `total_sales`, sum(`s`.`final_amount`) AS `total_revenue`, avg(`s`.`final_amount`) AS `avg_sale_amount`, max(`s`.`sale_date`) AS `last_sale_date` FROM (`users` `u` left join `sales` `s` on(`u`.`id` = `s`.`user_id`)) WHERE `u`.`role` = 'admin' GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `top_selling_products`
--
DROP TABLE IF EXISTS `top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `top_selling_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`price` AS `price`, `c`.`name` AS `category_name`, sum(`sd`.`quantity`) AS `total_sold`, sum(`sd`.`subtotal`) AS `total_revenue`, count(distinct `sd`.`sale_id`) AS `times_sold` FROM ((`products` `p` left join `sale_details` `sd` on(`p`.`id` = `sd`.`product_id`)) left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) GROUP BY `p`.`id` ORDER BY sum(`sd`.`quantity`) DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `name` (`name`),
  ADD KEY `customer_type` (`customer_type`),
  ADD KEY `idx_customer_email` (`email`),
  ADD KEY `idx_customer_login` (`email`,`password`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `invoice_date` (`invoice_date`),
  ADD KEY `invoice_status` (`invoice_status`);

--
-- Indices de la tabla `invoice_sequence`
--
ALTER TABLE `invoice_sequence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_unique` (`year`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sale_date` (`sale_date`),
  ADD KEY `payment_status` (`payment_status`);

--
-- Indices de la tabla `sale_details`
--
ALTER TABLE `sale_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product_unique` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `added_at` (`added_at`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_customer_id` (`customer_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `invoice_sequence`
--
ALTER TABLE `invoice_sequence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sale_details`
--
ALTER TABLE `sale_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Filtros para la tabla `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `sale_details`
--
ALTER TABLE `sale_details`
  ADD CONSTRAINT `sale_details_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sale_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
