# Manual Técnico de Arquitectura, Conexión Dual y Sincronización
**Proyecto:** Comandas Móviles v2.0 & Portal Web Mi Jardín  
**Ubicación:** `E:\herd\comandasmoviles\docs\manual_tecnico_arquitectura_y_sincronizacion.md`  
**Última Auditoría Técnica:** Octubre 2026  
**Audiencia:** Programadores, Administradores de Sistemas y Agentes de IA (LLMs)

---

## 1. Visión General del Ecosistema

El ecosistema digital de **Mi Jardín Restaurant** consta de dos componentes integrados mediante peticiones salientes seguras (Outbound HTTPS POST):

1. **`comandasmoviles` (Aplicación Interna POS Móvil):**
   - Sistema web PHP/JS para tablets y teléfonos de mesoneros y cajeros en red local.
   - Interactúa en tiempo real con el servidor de base de datos local del restaurante.
2. **`mijardinrestaurant.com` (Portal Nube Autoservicio - Laravel):**
   - Servidor Cloud VPS en internet (`https://mijardinrestaurant.com`).
   - Permite al comensal consultar su cuenta en vivo escaneando un código QR (`/mesa?t={PIN}`) y reportar transferencias o Pago Móvil sin entrar a la red interna del restaurante.

---

## 2. Mapa y Roles de las Bases de Datos

El sistema maneja tres bases de datos fundamentales:

| Base de Datos | Ubicación | Motor / Puerto | Rol y Restricciones Críticas |
| :--- | :--- | :--- | :--- |
| **`adminbahiap`** | Local (PC Restaurante / PC Desarrollo) | MySQL 5.5 (3306 o 3307) | **BD Fiscal de Premium Soft.** <br>⚠️ **INTOCABLE:** Homologada por el **SENIAT**. Prohibido alterar tablas, crear columnas o llaves foráneas. Solo lecturas e inserciones transaccionales nativas (`operti`, `opermv`, `idconsumos`, etc.). |
| **`mijar26273_sistema` (Local)** | Local (Mismo servidor MySQL) | MySQL 5.5 (3306 o 3307) | **BD Satélite Local.** <br>Almacena tokens PIN temporales (`z_status_pago`), estados de sincronización (`z_mesas_activas`), configuración (`z_config_sistema`) y bancos (`z_config_bancos`). |
| **`mijar26273_sistema` (Cloud)** | Remoto (Hosting VPS / Cloud) | MySQL 8.x / MariaDB | **BD de la Plataforma Web Laravel.** <br>Alimenta la consulta del comensal. Recibe datos vía API saliente desde `sync_daemon.php`. |

---

## 3. Conexión Dual Automática (Desarrollo vs Producción)

El sistema opera de forma transparente tanto en la estación de desarrollo como en el servidor de producción del restaurante **sin necesidad de cambiar archivos de configuración ni tocar switches manuales**.

### Matriz de Credenciales Candidatas:
* **Entorno Desarrollo (PC Casa):** Puerto `3307`, Usuario `root`, Contraseña `root`.
* **Entorno Producción (Restaurante):** Puerto `3306`, Usuario `root`, Contraseña `1234`.

### Funciones Centralizadas en `funciones/config_helper.php`:
* `obtener_config_sistema()`: Lee de `z_config_sistema` probando la matriz de credenciales y cachea la conexión que respondió.
* `conectar_bd_pos($config = null)`: Retorna un objeto `mysqli` conectado a `adminbahiap`, probando 3307 y 3306 automáticamente.
* `conectar_bd_sistema($config = null)`: Retorna un objeto `mysqli` conectado a la BD satélite `mijar26273_sistema`.

> **Regla de Oro:** Ningún controlador o script debe hacer `new mysqli('127.0.0.1', 'root', 'root', ...)` con credenciales fijas. Siempre debe usarse `conectar_bd_pos()` o `conectar_bd_sistema()`.

---

## 4. Lógica Universal de Mesas Reales vs Pedidos en Caja

### El Problema de las Listas Fijas:
Anteriormente se usaba `'mesas_caja' => '001,099'`. Esto fallaba porque los cajeros inventan cualquier código numérico para cuentas para llevar (`001`, `099`, `01`, `020`, `0101010`, etc.).

### La Solución Dinámica (`funciones/mesas_helper.php`):
La única fuente de la verdad para saber si una mesa es física y real es la tabla **`adminbahiap.idconsumos`**:
- Mesas reales registradas: `MESA01` a `MESA16` (y expansiones futuras a `MESA22+`), `JARD01` a `JARD05`, `TASC01`.

```php
// Regla Universal:
$es_mesa_real = es_mesa_oficial($codigo, $mysqli_pos);
$es_caja = es_pedido_caja($codigo, $mysqli_pos);
```

### Comportamiento según el tipo de cuenta:
1. **Mesa Real (en `idconsumos`):**
   - Muestra el botón **Compartir** en Comandas Móviles.
   - Genera código QR y PIN de 6 dígitos.
   - `sync_daemon.php` la detecta en `operti` (`tipodoc = 'ESP'`) y la sube al hosting cloud.
2. **Pedido en Caja / Comida para Llevar (NO está en `idconsumos`):**
   - El botón **Compartir se oculta automáticamente** en JavaScript (`js/pedidos.js`).
   - `controlador_qr.php` rechaza la generación de QR/PIN si se solicita directamente.
   - `sync_daemon.php` **la ignora por completo** y nunca la sube a internet.
   - En la sección **En Tránsito** de Comandas Móviles, se muestra agrupada abajo con fondo verde suave y borde verde bajo la cabecera `PEDIDOS EN CAJA (comida para llevar)`.

---

## 5. Ciclo de Vida de los PINs y Prevención de Duplicados

### Causa del Error del PIN Repetido:
Se descubrió que el código anterior hacía:
```php
$res = $mysqli->query("SELECT token_pin FROM z_status_pago WHERE documento = '{$documento}' LIMIT 1");
if ($row && !empty($row['token_pin'])) {
    $pin = $row['token_pin']; // Reutilizaba el PIN viejo de mesas cerradas hace días
}
```
Si una mesa `E099` se usó la semana pasada y tenía el PIN `437942`, al abrirla de nuevo se le asignaba exactamente el mismo PIN viejo en lugar de uno nuevo.

### Implementación Actual:
1. Solo se reutiliza el PIN existente si la mesa está **`ACTIVA`** y su pago sigue pendiente (`estado === '0'`).
2. Si la mesa estaba **`CERRADA`**, facturada o reabierta:
   - Se purga el registro anterior de `z_status_pago`.
   - Se genera un nuevo PIN criptográfico con `random_int(100000, 999999)`.
   - Se comprueba en bucle que el nuevo PIN **no esté en uso por ninguna otra mesa activa** (`SELECT id FROM z_status_pago WHERE token_pin = '{$pin}' AND estado = '0'`).

---

## 6. Módulo Verificador y Auto-Reparador Satélite

Implementado en `funciones/verificador_bd.php` y expuesto vía AJAX en `controladores/controlador_verificador_bd.php`.

### Esquema Canónico de las 5 Tablas Satélite:
1. **`z_config_sistema`:** Almacena pares clave-valor (`url_hosting`, `url_web_mesa`, `tipo_doc`, `bd_pos`, `bd_sistema`, `permitir_descuentos`, `monedafactor`).
2. **`z_config_bancos`:** Almacena las cuentas bancarias (Banesco, Mercantil, Pago Móvil, Zelle) extraídas de Premium Soft para sincronizar a la nube.
3. **`z_mesas_activas`:** Espejo local de cuentas abiertas con totales en Bs y USD, tasa del día e `items_json`.
4. **`z_status_pago`:** Rastreador de pagos reportados por el cliente (0 = Pendiente, 1 = Confirmado, 2 = Por verificar, 3 = Cerrada en caja, 4 = Rechazado).
5. **`z_top_productos`:** Artículos destacados en base al histórico de ventas recientes de Kardex para sugerir en el portal web.

### Idempotencia y Mantenimiento Automático:
* Utiliza `CREATE DATABASE IF NOT EXISTS` y `CREATE TABLE IF NOT EXISTS`.
* Comprueba columnas con `SHOW COLUMNS` y ejecuta `ALTER TABLE ADD COLUMN` solo para columnas faltantes.
* Inserta defaults con `INSERT IGNORE` respetando las configuraciones preexistentes del restaurante.
* Ejecuta limpieza automática de variables obsoletas: `DELETE FROM z_config_sistema WHERE clave IN ('mesas_caja', 'mesas_registradas')`.

---

## 7. Flujo del Demonio de Sincronización (`sync_daemon.php`)

El script `sync_daemon.php` se ejecuta en segundo plano en el servidor local (a través de `iniciar_sistema.bat`):

1. **Cada 12 segundos:** Consulta mesas abiertas en `adminbahiap.operti` (`tipodoc = 'ESP'`).
2. **Filtro estricto:** Descarta cualquier pedido en caja que no pertenezca a `idconsumos`.
3. **Consulta de platos:** Lee los ítems consumidos directamente de `adminbahiap.opermv`.
4. **Consulta de Tasa:** Lee la cotización de `adminbahiap.monedas`.
5. **Detección de Cierres (Reconciliación):** Compara las mesas activas locales con las que siguen en `operti`. Si una mesa desapareció de `operti` (porque el cajero la facturó en Premium Soft), se marca como `CERRADA` en `z_mesas_activas` y se envía la lista de mesas cerradas a la nube.
6. **Envío HTTPS POST Outbound:** Realiza una petición `cURL` a `https://mijardinrestaurant.com/api/sincronizar-mesas` con el payload de mesas activas, mesas cerradas y productos destacados.
7. **Retorno de Pagos:** La respuesta de la API Cloud devuelve pagos que los comensales hayan reportado desde sus celulares, actualizando `z_status_pago` en la red local.

---

## 8. Modificaciones de Interfaz de Usuario (`index.php`, `pedidos.js`, `pedidos.css`)

1. **Modal de Configuración:**
   - Se removió la sección obsoleta "Modo de Entorno" (reemplazada por auto-detección).
   - Se removió la sección de actualización Git interna.
   - El botón de guardado se renombró a `Guardar Parámetros` para guardar cambios locales del formulario.
2. **Footer Principal:**
   - Botón **Actualizar** con icono `fa-sync-alt` (compatible con Font Awesome 5 Free) que abre el modal de verificación de GitHub.
   - Botón **Enviar Bancos** con icono `fa-save` para transmitir de inmediato las cuentas de Premium al hosting cloud.
   - Botón **Pantalla Completa**.
3. **Pestañas:**
   - Renombrada pestaña `Cuenta` a `Operación Actual`.
   - Renombrada pestaña `Transito` a `En Tránsito`.
4. **Estilos de Tránsito:**
   - Pedidos en caja con fondo verde suave (`#f0fdf4`) y borde izquierdo verde (`#5CB85C`).
   - Botón de editar comanda siempre visible en amarillo cálido (`btn-warning`).

---

## 9. Instrucciones para Nuevos Desarrolladores o Agentes de IA

* **Nunca alterar `adminbahiap`:** Cualquier columna nueva que necesite la aplicación debe ser creada en `mijar26273_sistema`.
* **Para verificar el estado de la BD satélite:** Ejecutar `php scratch/run_verif.php` o invocar `verificar_y_reparar_base_datos_satelite()`.
* **Para verificar sintaxis:** Siempre validar con `php -l [archivo.php]` antes de enviar cambios a producción.
* **Para probar el demonio de sincronización:** Correr `php sync_daemon.php` desde la consola de Windows/Herd y observar la salida con timestamps.
