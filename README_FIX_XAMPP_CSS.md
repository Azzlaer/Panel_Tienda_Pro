# Reparación del diseño CSS en XAMPP

La captura de la portada sin estilos suele indicar que el navegador recibe **404/403/HTML** al solicitar los CSS, aunque PHP renderice la página. En esta rama los CSS principales y los recursos locales de temas se resuelven con rutas **del mismo sitio** derivadas de `SCRIPT_NAME`, lo que evita que un `APP_URL` anticuado rompa el diseño de portada o AdminCP.

**Importante:** esta corrección no cambia `config/config.php` ni arregla un dominio o puerto incorrecto en los enlaces generales. Revisa `APP_URL` de todas formas.

## Diagnóstico en instalación local

1. Accede a tu página y abre **F12 → Network → CSS**. Recarga usando **Ctrl+F5**.
2. Confirma que `app.css`, `urban.css` y `enterprise-blue.css` devuelven **200**, contenido CSS y no una redirección al instalador.
3. Prueba directamente `http://localhost/ComercioSocial/public/assets/css/app.css` (sustituye carpeta, dominio y puerto por los reales).
4. Corrige `APP_URL` en `config/config.php` para apuntar a la URL pública real, por ejemplo `http://localhost/ComercioSocial/public` si ésa es tu carpeta.
5. Si recibes **403**, revisa la configuración de Apache y permisos del directorio. Si recibes **404**, revisa DocumentRoot, estructura de carpetas y nombre. Si recibes HTML en lugar de CSS, revisa los redirects.
6. No reinstales ni borres MySQL: **esta reparación es solo de rutas de recursos estáticos**.

## Compatibilidad
PHP 8.2/8.3, Apache XAMPP y los temas ya instalados. `public_asset_url` usa rutas locales sin aceptar parámetros de host externo. Los enlaces de negocio siguen usando `url()`, para no cambiar enlaces ni callbacks sin validación de `APP_URL`.
