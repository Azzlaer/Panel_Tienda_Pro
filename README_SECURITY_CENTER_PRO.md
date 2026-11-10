# ComercioSocial Enterprise — Security Center PRO (fase 1)

## Seguridad implementada
- El registro público SIEMPRE crea usuarios con `role=user` y `status=pending`, aunque la tabla `users` no tenga filas.
- Solo el instalador controlado o un administrador autorizado pueden crear administradores.
- Contraseñas nuevas de 12 a 1024 caracteres; nombres visibles de hasta 100 y correos de hasta 190 caracteres.
- La aplicación establece valores seguros de sesión antes de cargar `config/config.php`: `session.use_strict_mode`, `session.use_only_cookies`, `session.cookie_httponly` y cookie Secure si HTTPS está activo.
- El registro e inicio de sesión emiten `Cache-Control: no-store`.
- Panel privado `admin/security_center.php` con estado de seguridad y resumen de intentos fallidos de inicio de sesión, sin revelar credenciales.
- Pruebas automáticas `tests/security_center_smoke.php` agregadas a CI PHP 8.2/8.3.

## Compatibilidad e instalación
Sin migraciones ni cambios en las tablas MySQL, plantillas o módulos comerciales. Compatible con XAMPP HTTP local y despliegues HTTPS. Haz respaldo antes de actualizar.
Si no existen usuarios administradores, utiliza el instalador seguro (sobre base nueva) o un procedimiento administrativo controlado; **el registro público ya no puede elevar al primer usuario**. La protección hCaptcha sigue separada en PR #9 y debe configurarse después de integrarse.

## Riesgos y tareas pendientes
- Las verificaciones del panel son diagnósticas, no pruebas de penetración. La existencia de `.htaccess` no demuestra que Apache aplique sus reglas.
- Aún no existe una CSP global estricta; deben eliminarse scripts inline y evaluar los recursos de temas antes de activarla sin romper páginas.
- Mejorar límites por IP y cuenta, revisión completa de SQL dinámico, permisos y cargas de archivos.
- Revisar y validar el procedimiento destructivo del instalador: cambios en PR #3, no incluidos aquí.
- Pruebas manuales en PHP/Apache/MariaDB, especialmente alta, acceso de administradores y configuración HTTPS, antes de desplegar.
