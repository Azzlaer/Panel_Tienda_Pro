# ComercioSocial Enterprise v6.3 — Core & Installer PRO

## Cambios implementados
- Instalación normal no destructiva en base vacía.
- Opción explícita **Empezar de cero**: borra todas las tablas y vistas de la base seleccionada, reconstruye el esquema y vuelve a crear usuario administrador y roles internos. Requiere confirmación literal `REINICIAR nombre_de_base`. No ejecuta `DROP DATABASE`, no altera privilegios ni usuarios globales MySQL.
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

## Reiniciar instalación existente (procedimiento administrativo)
1. Exporta una copia de seguridad completa de MySQL y de los archivos de la instalación.
2. En una copia de pruebas, después del respaldo, retira manualmente `storage/installed.lock` y `config/config.php` para desbloquear el instalador. Guarda antes las credenciales fuera de la raíz web.
3. Visita `/install/`, selecciona la base específica, marca **Empezar de cero** y escribe exactamente `REINICIAR nombre_de_base`.
4. Introduce credenciales MySQL con permisos de `DROP`, `CREATE`, `ALTER`, `INSERT` para esa base, configura el administrador nuevo y ejecuta la instalación.
5. Recuerda que las imágenes, archivos y plantillas personalizadas del disco no se borran automáticamente; pueden quedar huérfanos.

**Advertencia**: no se recuperan los datos sin un respaldo; las operaciones DDL de MySQL no son transaccionales. Un fallo a mitad del reinicio puede dejar la base parcialmente vacía. No expongas el instalador reactivado públicamente.
