SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tablas del Core de Laravel y Autenticación
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tablas del Sistema de Mi Jardín Restaurante

-- Configuración general del sistema (incluye monedafactor y tasa)
CREATE TABLE IF NOT EXISTS `z_config_sistema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas bancarias para pagos móviles y transferencias
CREATE TABLE IF NOT EXISTS `z_config_bancos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `banco` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titular` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_cuenta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('PAGO_MOVIL','TRANSFERENCIA','ZELLE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAGO_MOVIL',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesas activas abiertas sincronizadas desde Premium Soft
CREATE TABLE IF NOT EXISTS `z_mesas_activas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mesa` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_pin` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal_bs` decimal(14,2) NOT NULL DEFAULT '0.00',
  `impuesto_bs` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_bs` decimal(14,2) NOT NULL DEFAULT '0.00',
  `subtotal_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `impuesto_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tasa_dia` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `items_json` json DEFAULT NULL,
  `estado` enum('ACTIVA','CERRADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVA',
  `ultima_sincronizacion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `z_mesas_activas_documento_unique` (`documento`),
  KEY `z_mesas_activas_token_pin_index` (`token_pin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estado y confirmación de pagos de clientes
CREATE TABLE IF NOT EXISTS `z_status_pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_pin` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `metodo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco_origen` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT '0.00',
  `moneda` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VES',
  `fecha_reporte` datetime DEFAULT NULL,
  `aprobado_por` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_confirmacion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `z_status_pago_documento_unique` (`documento`),
  KEY `z_status_pago_token_pin_index` (`token_pin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Top de productos más vendidos para el carrusel
CREATE TABLE IF NOT EXISTS `z_top_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `posicion` tinyint NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_bs` decimal(18,2) NOT NULL,
  `precio_usd` decimal(18,2) NOT NULL,
  `imagen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_vendido` decimal(10,2) NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_posicion` (`posicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de inserción inicial para monedafactor en caso de estar vacía
INSERT INTO `z_config_sistema` (`clave`, `valor`, `created_at`, `updated_at`)
VALUES ('monedafactor', '887.302', NOW(), NOW())
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

SET FOREIGN_KEY_CHECKS = 1;