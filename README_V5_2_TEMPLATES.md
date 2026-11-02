# ComercioSocial Enterprise v5.2 · Pack de 6 templates

Basado en la versión v5.1 de Mapa de Locales, preservando los módulos anteriores.

## Instalación completa en XAMPP
1. Exporta un respaldo de MySQL y guarda una copia de tu carpeta web.
2. Descomprime este ZIP en una carpeta de pruebas dentro de `C:\xampp\htdocs\`.
3. Para una actualización, restaura de tu instalación original `config/config.php`, `storage/installed.lock`, las subidas en `public/uploads/` y cualquier configuración persistente. No reemplaces la base de datos.
4. Abre AdminCP > Plantillas (`admin/templates.php`) y activa la que prefieras.
5. Recarga el navegador con Ctrl+F5.

## Solo instalar plantillas
En el ZIP completo hay seis paquetes separados bajo `THEMES_INSTALLABLES/`, que puedes subir desde AdminCP > Plantillas > Inspeccionar > Instalar.

## Temas
- Corporate Nexus: continuidad Corporate Blue.
- Market Navigator: catálogo y navegación por categorías.
- Boutique Minimal: estética editorial minimalista.
- Promo Festival: descuentos y banners destacados.
- Mall Directory Pro: locales y mapa.
- Midnight Premium: tema oscuro.

## Compatibilidad
Manifiestos `layout_version: urban-2`; estilos cargados después del CSS base Enterprise para evitar conflictos de precedencia. Temas exclusivamente CSS y SVG, sin PHP ejecutable. El AdminCP continúa usando su propio tema de administración.

## Limitaciones
Este paquete ha superado verificaciones estáticas (sintaxis PHP, estructura de ZIP); no se ha probado contra una instancia XAMPP+MySQL real. Las plantillas usan el contenido existente: no incorporan checkout, pagos o gestión de stock nuevos. Los estilos son originales, inspirados en patrones de diseño, no copias de plantillas comerciales.
