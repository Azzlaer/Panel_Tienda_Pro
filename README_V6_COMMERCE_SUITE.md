# ComercioSocial Enterprise 6.0 — Commerce Suite Integral (primera etapa funcional)

## Base
Copia íntegra de v5.5. Se han conservado los templates, el instalador, el slider, noticias, perfiles PRO, mapa, Theme Studio y el AdminCP.

## Módulos implementados
- `inventory.php`: gestión de precio CLP y stock de productos propios (no crea variantes ni SKU todavía).
- `suite_inbox.php`: consultas privadas ligadas a publicaciones, respuestas y cierre.
- `suite_requests.php`: solicitudes de reserva, aceptación/rechazo y estado finalizado, sin procesar pagos ni descontar stock automáticamente.
- `offers.php`: página pública de campañas vigentes.
- `admin/commerce_suite.php`: migración inicial y administración de campañas con fechas de inicio y fin.
- CSS original y autónomo: `public/assets/css/commerce-suite.css`.

## Instalar o actualizar XAMPP
1. Haz respaldo de archivos y de MySQL. No sobrescribas `config/config.php`, `storage/installed.lock` ni `public/uploads/`.
2. Copia archivos del paquete conservando la instalación anterior. Si es nueva, usa `install/`.
3. Entra como administrador a `admin/commerce_suite.php` y pulsa «Ejecutar migración». La base de datos debe permitir CREATE TABLE.
4. Comprueba `inventory.php`, `suite_inbox.php`, `suite_requests.php` y `offers.php`.
5. Alternativamente ejecuta `database/migrations/2026_10_30_000007_commerce_suite.sql` manualmente, y luego accede al AdminCP.

## Seguridad y límites
- Todas las modificaciones requieren CSRF; inventario y mensajes aplican autorización por propietario.
- Las consultas son privadas para comprador y vendedor; no incluyen correo automático.
- No se implementó checkout, Webpay, facturación, descuentos de inventario, multicarrito, 2FA ni SKU/variantes: requieren una fase posterior y pruebas de integración.
- Revisa en servidor Apache/MySQL real, permisos de subida y configuración de `APP_URL` antes de publicar.
- No incluye credenciales privadas ni datos de usuarios.
