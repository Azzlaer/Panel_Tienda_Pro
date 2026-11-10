# ComercioSocial Enterprise — Integración PR #3 a #10

## Origen
Se han incorporado los cambios funcionales preparados en los PR #3 a #10 sobre la rama `main` sin fusionar ninguno de ellos ni modificar el servidor XAMPP. Las funcionalidades originales, Theme Studio, Commerce Suite, mapas, perfiles, noticias y plantillas permanecen.

## Conflictos resueltos
- `admin/partials/header.php`: combina navegación Nexus, POS, Pedidos, auditoría, Security Center e Integration Center.
- `app/bootstrap.php`: configuración de sesión reforzada y versión 6.9.0.
- `public/login.php` y `public/register.php`: hCaptcha y Security Center. El registro público siempre crea usuarios pendientes, jamás administradores.
- `install/index.php`: instalador v6.3 con inicio de cero explícito y confirmación, configuración hCaptcha opcional, bloqueo ante la existencia de config o lock.
- `public/inventory.php`: ajustes auditados y enlaces a POS/Pedidos.
- `app/pos.php` y `app/orders_pro.php`: integración al servicio transaccional `inventory_core_decrease` y cálculo `inventory_core_total`.
- `.github/workflows/php-lint.yml`: tests de regresión de los ocho PR y comprobación transversal.

## Activación en XAMPP / hosting
1. Respaldar archivos y base de datos. No desplegar esta rama directamente en producción sin validar.
2. Comprobar PHP >=8.2, PDO MySQL, mbstring, cURL para hCaptcha, permisos Apache y `config/config.php`.
3. Tras actualizar código, entrar como administrador en `admin/integration_center.php`.
4. Activar módulos siguiendo el orden indicado, solo si no existen sus tablas. Las migraciones usan `CREATE TABLE IF NOT EXISTS`, pero no sustituyen un sistema robusto de versionado/rollback del esquema.
5. Activar hCaptcha mediante constantes privadas en `config/config.php` y probar login/registro con claves reales.
6. Verificar las páginas `public/pos.php`, `public/orders_pro.php`, `public/kiosk.php`, `admin/security_center.php` y `admin/core_inventory.php`.
7. Probar compras concurrentes, permisos, plantillas y diseño responsive antes de fusionar.

## Advertencias
- Inicio de cero elimina tablas seleccionadas de la base MySQL y exige copia de seguridad.
- Los pedidos descuentan stock al confirmarse, no al solicitarse; las ventas POS descuentan al registrarse. El servicio común impide sobreventa en actualizaciones concurrentes.
- No hay cobros procesados, facturación fiscal ni reembolsos automáticos.
- Las auditorías de ajuste manual no cubren todas las acciones de venta; POS tiene su propio historial.
- La protección hCaptcha queda desactivada por defecto hasta introducir claves; si está activa, falla cerrada.
- Las pruebas de sintaxis/estáticas no reemplazan pruebas reales de Apache/MariaDB ni una auditoría de penetración.
