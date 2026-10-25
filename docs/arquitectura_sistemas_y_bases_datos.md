# Arquitectura Integral del Ecosistema: Mi Jardín Restaurant & Comandas Móviles

**Proyecto:** Ecosistema Digital Restaurante Mi Jardín  
**Fecha:** Octubre 2026  
**Documento:** Nota Técnica de Arquitectura de Sistemas y Bases de Datos  
**Referencia Base:** `E:\herd\comandasmoviles\docs\estudio_qr_pedidos_pagos.md`

---

## 1. Visión y Objetivo del Ecosistema

**Mi Jardín Restaurant** y **Comandas Móviles** no son proyectos aislados, sino dos componentes esenciales de un **mismo ecosistema unificado** diseñado para la operación de alta demanda, seguridad perimetral y cumplimiento legal estricto de un restaurante moderno:

1. **`comandasmoviles` (Sistema Interno de Servicio):**
   - Utilizado por mesoneros, supervisores y capitanes de sala desde tablets y teléfonos en la red local interna del restaurante.
   - Gestiona la apertura de mesas, comandas a cocina/barra y precuentas con velocidad instantánea.
2. **`mijardinrestaurant` (Portal Nube & Autoservicio):**
   - Alojará en un **hosting web / VPS en internet** (`mijardinrestaurant.com`).
   - Permite a los clientes consultar su cuenta en vivo mediante códigos QR y PIN de 6 dígitos, dividir gastos con amigos y reportar transferencias / Pago Móvil sin ingresar a la red Wi-Fi privada del restaurante.

---

## 2. Mapa y Roles de las Bases de Datos

El sistema opera bajo un modelo de **alta seguridad y aislamiento perimetral** con 3 bases de datos bien diferenciadas entre dos entornos:

### 2.1. Entorno Local (Restaurante Físico - MySQL Puerto 3307)
Ambas bases de datos corren localmente en el servidor del restaurante en el **puerto 3307**:

1. **`adminbahiap` (Software Administrativo Premium Soft):**
   - **MÁXIMA PRIORIDAD DE CUMPLIMIENTO:** Es la base de datos fiscal del sistema Premium Soft.
   - **ESTRUCTURA INTOCABLE:** **No se debe modificar su estructura, alterar tablas ni añadir campos bajo ningún motivo**.
   - **HOMOLOGACIÓN SENIAT:** Este sistema está homologado y fiscalizado por el **SENIAT** (Servicio Nacional Integrado de Administración Aduanera y Tributaria de Venezuela). Está sujeto a auditorías impositivas permanentes. Cualquier alteración a su esquema nativo invalida la certificación fiscal y genera sanciones legales graves.
   - **Acceso:** `comandasmoviles` solo realiza lecturas y registros estrictamente necesarios en tablas estándar (`operti`, `opermv`, etc.).

2. **`mijar26273_sistema` (Base de Datos Satélite Local - Puerto 3307):**
   - Base de datos auxiliar creada específicamente para la innovación digital del restaurante sin tocar `adminbahiap`.
   - Almacena:
     - Tabla `z_status_pago`: Registro de pagos notificados y seguimiento de PIN por mesa.
     - Tabla `z_mesas_activas`: Copia local de comandas para sincronización rápida.
     - Tabla `z_config_sistema`: Parámetros operativos, credenciales locales y endpoint del hosting (reemplaza archivos `.json` inseguros).

---

### 2.2. Entorno Remoto (hosting web / VPS Cloud - MySQL Puerto 3306)
Correrá en el hosting web de producción (en entorno de desarrollo local se prueba en el puerto 3306):

3. **`mijar26273_sistema` (Base de Datos Cloud / Producción):**
   - **Única conexión de Laravel:** `mijardinrestaurant` solo tiene configurada y solo se conecta a esta única base de datos (`mijar26273_sistema`). No tiene acceso directo ni conexiones secundarias al puerto 3307 ni a `adminbahiap`.
   - Alimentada exclusivamente por peticiones salientes seguras (**Outbound HTTPS POST**) emitidas por el demonio local `sync_daemon.php` o por el botón manual "Actualizar BD remota" en `comandasmoviles` hacia la API de Laravel (`/api/sincronizar-mesas` y `/api/sincronizar/bancos`).
   - Sirve la información a los clientes del restaurant (`/mesa?t={PIN}`) y almacena en `z_config_bancos` las formas de pago oficiales.
   - **Aislamiento Total:** El cliente y la aplicación web pública nunca tienen conexión con el puerto 3307 ni con la red física del restaurante.

---

## 3. Diagrama de Flujo y Comunicación

```
[ MESONERO ]
     │ (1) Toma pedido / Genera PIN
     ▼
[ comandasmoviles (Local :8080) ]
     │
     ├───► [ adminbahiap :3307 ] (BD Fiscal Premium Soft - Intocable / SENIAT)
     └───► [ mijar26273_sistema :3307 ] (BD Satélite Local: PIN, Tokens, Config)
                 │
                 │ (2) sync_daemon.php lee cada 12 segundos
                 ▼
[ HTTPS POST Outbound Seguro ] ──► [ Hosting web / VPS ]
                                            │
                                            ▼
                               [ API Laravel: /api/sincronizar-mesas ]
                                            │
                                            ▼
                               [ BD Cloud: mijar26273_sistema :3306 ]
                                            ▲
                                            │ (3) Consulta en tiempo real
                                            │
                                  [ COMENSAL EN LA MESA ]
                              (QR / PIN: mijardinrestaurant.com/mesa)
```

---

## 4. Conclusión Técnica

La unión armónica de ambos sistemas es la clave del éxito del restaurante:
- Protege la integridad tributaria ante el **SENIAT** en `adminbahiap`.
- Protege los servidores locales de ciberataques externos.
- Otorga a los clientes una experiencia moderna y fluida de consulta y pago en `mijardinrestaurant`.
