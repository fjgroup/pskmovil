# Base de Datos Local - Mi Jardín Restaurant (Puerto 3307)

Este documento contiene el volcado completo de la base de datos local satélite **`mijar26273_sistema`** del **puerto 3307** para ser copiado en el pendrive y transferido a la PC del restaurante.

> [!TIP]
> **Archivo SQL listo para copiar al Pendrive:**  
> También se ha generado el archivo directo [sql_local.sql](file:///e:/herd/comandasmoviles/docs/sql_local.sql) en esta misma carpeta `docs/`. Puedes copiar directamente `sql_local.sql` a tu pendrive y abrirlo en HeidiSQL con **Archivo -> Cargar archivo SQL... (Ctrl + O)**.

---

## 📌 Pasos para importar en la PC del Restaurante con HeidiSQL

1. Conecta tu **pendrive** en la computadora del restaurante.
2. Abre **HeidiSQL** en la computadora del restaurante y conéctate a la sesión local de MySQL (habitualmente **Puerto 3307**, usuario `root`).
3. En el menú superior de HeidiSQL, haz clic en **Archivo -> Cargar archivo SQL...** y selecciona `sql_local.sql` desde tu pendrive (o abre una pestaña de **Consulta** y pega el código SQL de abajo).
4. Presiona la tecla **F9** (o el botón azul con forma de triángulo **Ejecutar**).
5. En el panel izquierdo de HeidiSQL presiona **F5** para refrescar: verás la base de datos **`mijar26273_sistema`** creada con todas sus tablas y datos cargados.
6. ¡Listo! Ya puedes ejecutar `iniciar_sistema.bat` para iniciar las comandas móviles y el sincronizador.

---

## 💾 Script SQL Completo (Puerto 3307 - `mijar26273_sistema`)

```sql
-- ============================================================================
-- BASE DE DATOS LOCAL SATÉLITE: MI JARDÍN RESTAURANT
-- Exportado desde MySQL Local (Puerto 3307) para la PC del Restaurante
-- Base de datos: mijar26273_sistema
-- Fecha: 2026-10-26 15:43:41
-- ============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- 1. Creación y selección de la Base de Datos
CREATE DATABASE IF NOT EXISTS `mijar26273_sistema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mijar26273_sistema`;

-- ============================================================================
-- Estructura de tabla para `z_config_sistema`
-- ============================================================================
DROP TABLE IF EXISTS `z_config_sistema`;
CREATE TABLE `z_config_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `z_config_sistema` (35 registros)
LOCK TABLES `z_config_sistema` WRITE;
/*!40000 ALTER TABLE `z_config_sistema` DISABLE KEYS */;
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('1', 'config_completa', '{\"modo_entorno\":\"desarrollo\",\"host\":\"127.0.0.1\",\"user\":\"root\",\"pw\":\"root\",\"bd\":\"adminbahiap\",\"bd_pos\":\"adminbahiap\",\"bd_sistema\":\"mijar26273_sistema\",\"port\":\"3307\",\"url_hosting\":\"https:\\/\\/mijardinrestaurant.com\",\"url_web_mesa\":\"https:\\/\\/mijardinrestaurant.com\\/mesa\",\"nombre_empresa\":\"INVERSIONES BAHIA PLAZA, C.A\",\"cod_empresa\":\"BAHIAP\",\"cod_agencia\":\"001\",\"cod_cliente\":\"V123456789\",\"nrorif\":\"\",\"cod_zona\":\"001\",\"cod_deposito\":\"01\",\"recargo\":\"0\",\"pw_supervisor\":\"PeDrO\",\"cantidad_por_pagina\":\"12\",\"cuentas_compartidas_entre_vendedores\":\"si\",\"agrupar_mesas_por_ubicacion\":\"si\",\"usar_subgrupos\":\"no\",\"no_usar_impuestos\":\"no\",\"no_editar_integrados\":\"si\",\"ocultar_integrados\":\"no\",\"cm_ancho_precuenta\":\"8\",\"tipo_doc\":\"ESP\",\"no_tiene_existencia\":\"no\",\"permitir_descuentos\":\"no\",\"minutos_inactividad\":\"0\",\"impuestos\":[{\"codigo\":\"03\",\"nombre\":\"Alicuota Adicional\",\"prc\":\"31.0000000\"}],\"dividemultiplica\":\"1\",\"monedafactor\":\"887.3020\"}', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('2', 'host', '127.0.0.1', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('3', 'user', 'root', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('4', 'pw', 'root', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('5', 'bd', 'adminbahiap', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('6', 'port', '3307', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('7', 'nombre_empresa', 'INVERSIONES BAHIA PLAZA, C.A', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('8', 'cod_empresa', 'BAHIAP', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('9', 'cod_agencia', '001', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('10', 'cod_cliente', 'V123456789', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('11', 'nrorif', '', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('12', 'cod_zona', '001', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('13', 'cod_deposito', '01', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('14', 'recargo', '0', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('15', 'pw_supervisor', 'PeDrO', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('16', 'cantidad_por_pagina', '12', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('17', 'cuentas_compartidas_entre_vendedores', 'si', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('18', 'agrupar_mesas_por_ubicacion', 'si', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('19', 'usar_subgrupos', 'no', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('20', 'no_usar_impuestos', 'no', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('21', 'no_editar_integrados', 'si', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('22', 'ocultar_integrados', 'no', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('23', 'cm_ancho_precuenta', '8', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('24', 'tipo_doc', 'ESP', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('25', 'no_tiene_existencia', 'no', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('26', 'permitir_descuentos', 'no', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('27', 'minutos_inactividad', '0', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('28', 'impuestos', '[{\"codigo\":\"03\",\"nombre\":\"Alicuota Adicional\",\"prc\":\"31.0000000\"}]', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('29', 'dividemultiplica', '1', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('30', 'monedafactor', '887.3020', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('31', 'url_web_mesa', 'https://mijardinrestaurant.com/mesa', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('32', 'bd_pos', 'adminbahiap', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('33', 'bd_sistema', 'mijar26273_sistema', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('34', 'url_hosting', 'https://mijardinrestaurant.com', '2026-10-24 10:13:46', '2026-10-26 00:01:38');
INSERT INTO `z_config_sistema` (`id`, `clave`, `valor`, `created_at`, `updated_at`) VALUES ('236', 'modo_entorno', 'desarrollo', '2026-10-25 10:01:06', '2026-10-26 00:01:38');
/*!40000 ALTER TABLE `z_config_sistema` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================================================
-- Estructura de tabla para `z_config_bancos`
-- ============================================================================
DROP TABLE IF EXISTS `z_config_bancos`;
CREATE TABLE `z_config_bancos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo_pos` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titular` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_cuenta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('PAGO_MOVIL','TRANSFERENCIA','ZELLE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAGO_MOVIL',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `z_config_bancos` (1 registros)
LOCK TABLES `z_config_bancos` WRITE;
/*!40000 ALTER TABLE `z_config_bancos` DISABLE KEYS */;
INSERT INTO `z_config_bancos` (`id`, `codigo_pos`, `banco`, `titular`, `identificacion`, `telefono`, `numero_cuenta`, `tipo`, `activo`, `created_at`, `updated_at`) VALUES ('1', '102', 'PAGO MOVIL (VENEZUELA 0102)', 'PEDRO GUZMAN', '12345678', '04', '0102', 'PAGO_MOVIL', '1', '2026-10-25 14:48:38', '2026-10-25 14:48:38');
/*!40000 ALTER TABLE `z_config_bancos` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================================================
-- Estructura de tabla para `z_mesas_activas`
-- ============================================================================
DROP TABLE IF EXISTS `z_mesas_activas`;
CREATE TABLE `z_mesas_activas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mesa` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_pin` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal_bs` decimal(14,2) NOT NULL DEFAULT '0.00',
  `impuesto_bs` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_bs` decimal(18,2) DEFAULT '0.00',
  `subtotal_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `impuesto_usd` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_usd` decimal(18,2) DEFAULT '0.00',
  `tasa_dia` decimal(18,4) DEFAULT '1.0000',
  `items_json` longtext COLLATE utf8mb4_unicode_ci,
  `estado` enum('ACTIVA','CERRADA') COLLATE utf8mb4_unicode_ci DEFAULT 'ACTIVA',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`),
  KEY `idx_token_pin` (`token_pin`),
  KEY `idx_estado_mesa` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5983 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `z_mesas_activas` (4 registros)
LOCK TABLES `z_mesas_activas` WRITE;
/*!40000 ALTER TABLE `z_mesas_activas` DISABLE KEYS */;
INSERT INTO `z_mesas_activas` (`id`, `documento`, `mesa`, `token_pin`, `subtotal_bs`, `impuesto_bs`, `total_bs`, `subtotal_usd`, `impuesto_usd`, `total_usd`, `tasa_dia`, `items_json`, `estado`, `created_at`, `updated_at`) VALUES ('1', 'EMESA01', 'MESA MESA01', '339109', '52779.21', '5629.78', '58408.99', '59.48', '6.34', '65.83', '887.3020', '[{\"hora\":\"07:27 AM\",\"cant\":1,\"nombre\":\"COMBO JARDINERO BROASTER\",\"preciounit\":17593.07,\"total\":17593.07},{\"hora\":\"08:45 AM\",\"cant\":2,\"nombre\":\"COMBO JARDINERO BROASTER\",\"preciounit\":17593.07,\"total\":35186.14}]', 'CERRADA', '2026-10-24 00:35:42', '2026-10-25 09:34:55');
INSERT INTO `z_mesas_activas` (`id`, `documento`, `mesa`, `token_pin`, `subtotal_bs`, `impuesto_bs`, `total_bs`, `subtotal_usd`, `impuesto_usd`, `total_usd`, `tasa_dia`, `items_json`, `estado`, `created_at`, `updated_at`) VALUES ('356', 'EMESA03', 'MESA MESA03', '419079', '764.96', '122.39', '887.35', '0.86', '0.14', '1.00', '887.3020', '[]', 'CERRADA', '2026-10-24 09:09:09', '2026-10-25 17:39:36');
INSERT INTO `z_mesas_activas` (`id`, `documento`, `mesa`, `token_pin`, `subtotal_bs`, `impuesto_bs`, `total_bs`, `subtotal_usd`, `impuesto_usd`, `total_usd`, `tasa_dia`, `items_json`, `estado`, `created_at`, `updated_at`) VALUES ('707', 'EMESA02', 'MESA MESA02', '331109', '1071.68', '171.47', '1243.15', '1.21', '0.19', '1.40', '887.3020', '[{\"hora\":\"05:34 PM\",\"cant\":1,\"nombre\":\"YUKY PAK\",\"preciounit\":1071.68,\"total\":1071.68}]', 'CERRADA', '2026-10-25 06:51:56', '2026-10-25 17:39:43');
INSERT INTO `z_mesas_activas` (`id`, `documento`, `mesa`, `token_pin`, `subtotal_bs`, `impuesto_bs`, `total_bs`, `subtotal_usd`, `impuesto_usd`, `total_usd`, `tasa_dia`, `items_json`, `estado`, `created_at`, `updated_at`) VALUES ('5982', 'EMESA11', 'MESA MESA11', '596766', '17593.07', '2814.89', '20407.96', '19.83', '3.17', '23.00', '887.3020', '[{\"hora\":\"06:18 PM\",\"cant\":1,\"nombre\":\"COMBO JARDINERO BRASA\",\"preciounit\":17593.07,\"total\":17593.07}]', 'ACTIVA', '2026-10-25 22:18:40', '2026-10-25 18:24:31');
/*!40000 ALTER TABLE `z_mesas_activas` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================================================
-- Estructura de tabla para `z_status_pago`
-- ============================================================================
DROP TABLE IF EXISTS `z_status_pago`;
CREATE TABLE `z_status_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_pin` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `metodo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco_origen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(18,2) DEFAULT NULL,
  `moneda` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'VES',
  `fecha_reporte` datetime DEFAULT NULL,
  `aprobado_por` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_confirmacion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento` (`documento`),
  KEY `idx_token` (`token_pin`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=2951 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `z_status_pago` (4 registros)
LOCK TABLES `z_status_pago` WRITE;
/*!40000 ALTER TABLE `z_status_pago` DISABLE KEYS */;
INSERT INTO `z_status_pago` (`id`, `documento`, `token_pin`, `estado`, `metodo`, `banco_origen`, `referencia`, `monto`, `moneda`, `fecha_reporte`, `aprobado_por`, `fecha_confirmacion`, `created_at`, `updated_at`) VALUES ('1', 'EMESA01', '339109', '0', NULL, NULL, NULL, NULL, 'VES', NULL, NULL, NULL, '2026-10-24 00:35:42', '2026-10-24 00:35:42');
INSERT INTO `z_status_pago` (`id`, `documento`, `token_pin`, `estado`, `metodo`, `banco_origen`, `referencia`, `monto`, `moneda`, `fecha_reporte`, `aprobado_por`, `fecha_confirmacion`, `created_at`, `updated_at`) VALUES ('2', 'EMESA03', '419079', '1', '', '', '', '0.00', 'VES', '2026-10-25 18:24:32', 'Caja Premium', '2026-10-25 11:22:02', '2026-10-24 09:09:09', '2026-10-25 18:24:32');
INSERT INTO `z_status_pago` (`id`, `documento`, `token_pin`, `estado`, `metodo`, `banco_origen`, `referencia`, `monto`, `moneda`, `fecha_reporte`, `aprobado_por`, `fecha_confirmacion`, `created_at`, `updated_at`) VALUES ('6', 'EMESA02', '331109', '0', NULL, NULL, NULL, NULL, 'VES', NULL, NULL, NULL, '2026-10-25 06:51:56', '2026-10-25 06:51:56');
INSERT INTO `z_status_pago` (`id`, `documento`, `token_pin`, `estado`, `metodo`, `banco_origen`, `referencia`, `monto`, `moneda`, `fecha_reporte`, `aprobado_por`, `fecha_confirmacion`, `created_at`, `updated_at`) VALUES ('2950', 'EMESA11', '596766', '0', NULL, NULL, NULL, NULL, 'VES', NULL, NULL, NULL, '2026-10-25 22:18:40', '2026-10-25 22:18:40');
/*!40000 ALTER TABLE `z_status_pago` ENABLE KEYS */;
UNLOCK TABLES;

-- ============================================================================
-- Estructura de tabla para `z_top_productos`
-- ============================================================================
DROP TABLE IF EXISTS `z_top_productos`;
CREATE TABLE `z_top_productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `posicion` tinyint(4) NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_bs` decimal(18,2) NOT NULL,
  `precio_usd` decimal(18,2) NOT NULL,
  `imagen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_vendido` decimal(10,2) NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_posicion` (`posicion`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `z_top_productos` (5 registros)
LOCK TABLES `z_top_productos` WRITE;
/*!40000 ALTER TABLE `z_top_productos` DISABLE KEYS */;
INSERT INTO `z_top_productos` (`id`, `posicion`, `codigo`, `nombre`, `precio_bs`, `precio_usd`, `imagen`, `total_vendido`, `updated_at`) VALUES ('1', '1', '052', 'CERVEZA POLAR LIGTH', '887.35', '1.00', 'POLAR L.JPG', '449.00', '2026-10-25 17:51:10');
INSERT INTO `z_top_productos` (`id`, `posicion`, `codigo`, `nombre`, `precio_bs`, `precio_usd`, `imagen`, `total_vendido`, `updated_at`) VALUES ('2', '2', '053', 'CERVEZA POLAR NEGRA', '887.35', '1.00', 'POLAR N.JPG', '349.00', '2026-10-25 17:51:10');
INSERT INTO `z_top_productos` (`id`, `posicion`, `codigo`, `nombre`, `precio_bs`, `precio_usd`, `imagen`, `total_vendido`, `updated_at`) VALUES ('3', '3', '009', 'COMBO FAMILIAR BRASA', '18633.36', '21.00', 'CPBRASA.JPG', '27.00', '2026-10-25 17:51:10');
INSERT INTO `z_top_productos` (`id`, `posicion`, `codigo`, `nombre`, `precio_bs`, `precio_usd`, `imagen`, `total_vendido`, `updated_at`) VALUES ('16', '4', '011', 'COMBO JARDINERO BRASA', '20407.96', '23.00', 'POLLO ENT C. PAPAS.JPG', '24.00', '2026-10-25 17:51:10');
INSERT INTO `z_top_productos` (`id`, `posicion`, `codigo`, `nombre`, `precio_bs`, `precio_usd`, `imagen`, `total_vendido`, `updated_at`) VALUES ('17', '5', '016', 'COMBO DUO BROASTER', '11534.95', '13.00', 'DUO BROASTER.JPG', '22.00', '2026-10-25 17:51:10');
/*!40000 ALTER TABLE `z_top_productos` ENABLE KEYS */;
UNLOCK TABLES;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Fin del volcado de mijar26273_sistema

```

---

## 🔍 Consulta de Comprobación Rápida en HeidiSQL

Para verificar que todas las tablas y datos se importaron con éxito en el restaurante, ejecuta:

```sql
USE `mijar26273_sistema`;

SELECT 'z_config_sistema' AS tabla, COUNT(*) AS registros FROM z_config_sistema
UNION ALL
SELECT 'z_config_bancos' AS tabla, COUNT(*) AS registros FROM z_config_bancos
UNION ALL
SELECT 'z_mesas_activas' AS tabla, COUNT(*) AS registros FROM z_mesas_activas
UNION ALL
SELECT 'z_status_pago' AS tabla, COUNT(*) AS registros FROM z_status_pago
UNION ALL
SELECT 'z_top_productos' AS tabla, COUNT(*) AS registros FROM z_top_productos;
```