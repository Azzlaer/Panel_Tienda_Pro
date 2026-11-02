# Commerce QR Connect + Kiosco Digital

## Incluye
- `public/kiosk.php`: directorio táctil, buscador y vista de comercios activos con QR generado localmente.
- `public/commerce_qr.php?id=ID`: cartel imprimible y vínculo directo al perfil de un comercio activo.
- `public/assets/js/vendor/qrcode.min.js`: generador QR local, bajo licencia MIT conservada en `QR_CODE_LICENSE.txt`.
- Estilos independientes `commerce-kiosk.css` y JS `commerce-kiosk.js`.
- Acceso al kiosco desde el directorio de comercios.

## Instalación
Actualiza archivos del repositorio, conserva configuración e imágenes. Sin tablas nuevas ni cambios MySQL.
Abre `/public/kiosk.php` desde XAMPP o cPanel. Imprime códigos desde cada tarjeta o `/public/commerce_qr.php?id=ID`.

## Seguridad y limitaciones
Solo se muestran comercios activos de usuarios activos, consultas preparadas y salida escapada. Los QR usan la URL pública configurada en `APP_URL`, por lo que en un teléfono `localhost` no funcionará fuera del equipo: configura un dominio accesible por la red.
El módulo no edita locales ni genera códigos QR en servidor y no incluye aplicación offline/PWA. Las pruebas automáticas son sintácticas/estáticas; verificar en navegador la impresión, diseño responsive y cámara lectora.
