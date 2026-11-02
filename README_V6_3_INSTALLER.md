# ComercioSocial Enterprise v6.3 — Core & Installer PRO

## Cambios implementados
- El instalador **ya no ofrece ni ejecuta** `DROP DATABASE`: solo permite bases vacías y protege instalaciones anteriores.
- Contraseña administrativa mínima de 12 caracteres.
- URL de aplicación restringida a HTTP(S) sin credenciales.
- Validación de SameSite, Secure y esquema HTTPS para configurar cookies.
- Se verifica el resultado al escribir el archivo de bloqueo.
- Comprobación automatizada `tests/installer_safety.php` agregada al CI PHP 8.2/8.3.
- Versión interna 6.3.0.

## Compatibilidad
Se conserva XAMPP, MySQL/MariaDB, los módulos, temas y rutas anteriores. Sin migraciones SQL destructivas.

## Importante
**Este es un instalador para nuevas instalaciones**, no un migrador de bases ocupadas. Para actualizar una instalación existente, haz un respaldo y conserva `config/config.php`, `storage/installed.lock`, imágenes y base de datos. Los permisos MySQL de creación de base pueden variar por hosting.

## Pruebas restantes
Las pruebas automáticas actuales son de sintaxis y regresiones estáticas. Antes de desplegar deben probarse de extremo a extremo la instalación en base vacía, errores de conectividad, instalación interrumpida, permisos de escritura, sesión, login y administración desde Apache/PHP y MariaDB reales.

## Limitación
Si la instalación falla después de crear parcialmente el esquema, tendrás que recuperar una base vacía o un respaldo antes de reintentar. No se debe borrar automáticamente la base desde el instalador.
