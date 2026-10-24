# Guía de Estilos e Identidad Visual Oficial: Mi Jardín Restaurant

**Objetivo:** Garantizar que tanto la aplicación local de mesoneros (`comandasmoviles`) como el portal web para comensales (`mijardinrestaurant`) compartan exactamente la misma línea gráfica, tipografía y paleta de colores.

---

## 1. Paleta de Colores Corporativa (Brand Palette)

| Nombre | Código HEX | RGB | Uso Principal |
|---|---|---|---|
| **Azul Mi Jardín (Primario)** | `#0880C5` | `rgb(8, 128, 197)` | Cabecera de modales, títulos, categorías de productos, botón "Configurar", enlaces |
| **Azul Oscuro (Hover / Énfasis)** | `#06669E` | `rgb(6, 102, 158)` | Estados hover y bordes de componentes primarios |
| **Verde Aprobado / Procesar** | `#5CB85C` | `rgb(92, 184, 92)` | Botón "Procesar", botón "Entrar", confirmación de pago (VERDE), estados activos |
| **Verde Oscuro (Hover)** | `#449D44` | `rgb(68, 157, 68)` | Hover de botones de acción afirmativa |
| **Naranja / Mostaza (Comanda)** | `#F0AD4E` | `rgb(240, 173, 78)` | Botón "Comanda", avisos de mesas en espera, tasa del día |
| **Rojo Peligro / Cancelar** | `#D9534F` | `rgb(217, 83, 79)` | Botón "Cancelar", botón de apagado/salida, errores |
| **Negro Display (Totales)** | `#000000` | `rgb(0, 0, 0)` | Caja de totales en pantalla (Bs. y Ref. USD estilo pantalla POS) |
| **Fondo General Claro** | `#F4F6F9` | `rgb(244, 246, 249)` | Fondo general de la aplicación |
| **Blanco Tarjetas** | `#FFFFFF` | `rgb(255, 255, 255)` | Contenedores, modales, tarjetas de productos y mesas |
| **Gris Bordes** | `#DCE4EC` | `rgb(220, 228, 236)` | Bordes sutiles de inputs, divisores y tablas |
| **Texto Principal** | `#2B303A` | `rgb(43, 48, 58)` | Títulos, nombres de platos y textos principales |
| **Texto Secundario** | `#6C757D` | `rgb(108, 117, 125)` | Etiquetas, horas de pedido, subtítulos |

---

## 2. Variables CSS Listas para Usar

```css
:root {
    --mj-primary: #0880C5;
    --mj-primary-hover: #06669E;
    --mj-success: #5CB85C;
    --mj-success-hover: #449D44;
    --mj-warning: #F0AD4E;
    --mj-warning-hover: #EC971F;
    --mj-danger: #D9534F;
    --mj-danger-hover: #C9302C;
    --mj-display-bg: #000000;
    --mj-bg: #F4F6F9;
    --mj-card-bg: #FFFFFF;
    --mj-border: #DCE4EC;
    --mj-text: #2B303A;
    --mj-text-muted: #6C757D;
}
```

---

## 3. Estados de Mesa y Códigos de Color Compartidos

En ambos sistemas (`comandasmoviles` y `mijardinrestaurant`), el estado de la cuenta/pago se representará con los mismos tonos:

- ⚪ **Estado 0 (Consumo Activo):** Fondo blanco/gris claro con borde `#DCE4EC`. Indica comensales comiendo normalmente.
- 🔵 **Estado 2 (Pago Reportado por Cliente):** Color **Azul `#0880C5`** con texto blanco o fondo azul pastel suave (`#EBF5FB`). Indica que el cliente envió el reporte de Pago Móvil y espera verificación en caja.
- 🟢 **Estado 1 (Pago Confirmado y Aprobado):** Color **Verde `#5CB85C`** con texto blanco o fondo verde pastel suave (`#EAFAF1`). Indica pago aprobado por la administradora/caja.

---

## 4. Elementos de Identidad

1. **Logo Corporativo:** Banner horizontal fotográfico del restaurante con letrero de madera "Mi Jardín" (`imagenes/mi jardin.webp`).
2. **Display Financiero:** Dos barras negras consecutivas (`background-color: #000`) con números alineados a la derecha en tamaño destacado (`font-size: 20px`), texto blanco para bolívares y divisa.
3. **Tipografía:** Sistema sans-serif limpio (`Plus Jakarta Sans` / `Helvetica Neue` / `Segoe UI`) con alto contraste y legibilidad para teléfonos móviles bajo cualquier iluminación.
