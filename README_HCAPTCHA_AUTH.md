# ComercioSocial Enterprise — hCaptcha para registro e inicio de sesión

## Funcionalidad
- Widget hCaptcha oficial en `public/login.php` y `public/register.php`.
- Verificación **en servidor** con solicitud HTTPS POST `application/x-www-form-urlencoded` a `https://api.hcaptcha.com/siteverify`.
- Envía `secret`, `response`, `sitekey` e IP validada (si existe), con límites de tiempo, verificación TLS y sin seguir redirecciones.
- Solo `success === true` autoriza procesar el formulario; en fallos de red/cURL/JSON la verificación **rechaza**.
- Se mantiene CSRF, contraseñas con hash, límites existentes de login y aprobación de usuarios.
- Sin cambios MySQL ni en las plantillas/temas públicos.

## Activar en una instalación existente (XAMPP/cPanel)
1. Crea las claves en [hCaptcha](https://dashboard.hcaptcha.com/). Copia la **Sitekey** pública y el **Secret** privado.
2. Haz copia de `config/config.php`, que NO debe ser accesible por web ni estar versionado.
3. Añade estas constantes al final de `config/config.php` (fuera de cualquier bloque de comentario):

```php
const HCAPTCHA_ENABLED = true;
const HCAPTCHA_SITEKEY = 'TU_SITEKEY_PUBLICA';
const HCAPTCHA_SECRET = 'TU_SECRET_PRIVADA';
const HCAPTCHA_LOGIN_ENABLED = true;
const HCAPTCHA_REGISTER_ENABLED = true;
```

4. Asegúrate de habilitar la extensión PHP `curl`, conexión HTTPS saliente a `api.hcaptcha.com`, y acceso al script `https://js.hcaptcha.com/1/api.js`.
5. Prueba **registro y login** con tokens válidos e inválidos antes de habilitarlo para usuarios reales. Si hay CSP, permite los dominios necesarios de hCaptcha.

### Nuevas instalaciones
El instalador incluye una sección hCaptcha opcional que escribe esas constantes en la configuración local. No se habilita automáticamente sin claves reales. No uses el instalador para actualizar una base ya instalada.

### Modos
- `HCAPTCHA_ENABLED = false`: desactivado en ambas acciones.
- `HCAPTCHA_LOGIN_ENABLED = false`: desactiva solo el desafío en login.
- `HCAPTCHA_REGISTER_ENABLED = false`: desactiva solo registro.
- Si la protección está habilitada pero las claves faltan o el servidor no puede verificar el token, **no se permite autenticar ni registrar** hasta corregir la configuración.

**Seguridad:** el Secret no debe subirse a GitHub ni compartirse por chat. Es válido usar claves de prueba de hCaptcha solo durante pruebas. Nunca desactivar la comprobación del servidor para evitar problemas de red.

## Validación
`php tests/hcaptcha_auth_test.php` ejecuta pruebas unitarias con transporte simulado para no requerir una cuenta hCaptcha. GitHub Actions comprueba PHP 8.2 y 8.3. Se requieren pruebas manuales con claves reales en XAMPP y navegador. La integración no sustituye los límites de tasa, MFA ni monitoreo antiabuso.
