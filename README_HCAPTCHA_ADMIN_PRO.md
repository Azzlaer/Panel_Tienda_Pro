# hCaptcha: configuración desde Security Center PRO

El administrador puede entrar en admin/security_center.php y pulsar Configurar hCaptcha, o abrir admin/hcaptcha_settings.php.
Permite editar Sitekey pública, Secret privada, activar o desactivar el desafío y elegir si proteger login y registro.

## Almacenamiento
La Secret se guarda exclusivamente en config/hcaptcha.local.php, fuera de public/ e ignorado por Git. La aplicación conserva las constantes HCAPTCHA_* de config/config.php como compatibilidad cuando no existe override.
Los campos de clave dejados vacíos conservan sus valores anteriores. El panel no muestra la Secret y guarda de manera atómica.

## Instalación XAMPP
1. Respaldar archivos y revisar/fusionar el PR.
2. Crear Sitekey y Secret en https://dashboard.hcaptcha.com/ para el hostname correcto.
3. Abrir Security Center PRO > Configurar hCaptcha. Completar las claves, marcar Login y Registro, activar y guardar.
4. Comprobar PHP cURL, conexión HTTPS saliente con api.hcaptcha.com y acceso del navegador a js.hcaptcha.com.
5. Probar login y registro en ventana privada. No cerrar la sesión administradora hasta verificarlo: un error de conexión produce rechazo seguro.
6. Si falla el guardado, corregir permisos de config/. No conceder acceso HTTP al archivo ni publicar la Secret.

Las claves no van en MySQL ni en CSS/HTML. No requiere migraciones de base de datos y conserva temas Corporate Blue.

Nota: chmod(0600) no siempre tiene efecto en NTFS; usar permisos Windows apropiados.