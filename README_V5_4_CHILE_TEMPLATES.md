# ComercioSocial Enterprise v5.4 — Chile Commerce Collection

Tres nuevos templates originales con CSS propio, inspirados en patrones generales de e-commerce chileno, sin copiar diseño ni código de terceros.

- Chile Vitrina: búsqueda y catálogo protagonista.
- Barrio Market: tiendas de barrio y cercanía.
- Mall Avenida: editorial premium, fotos grandes.

## Instalación

Para una instalación existente: respalda archivos y MySQL. En AdminCP → Plantillas, sube uno de los ZIP individuales en THEMES_INSTALLABLES, inspecciona, instala y activa. No requiere cambios SQL.

Para una instalación nueva: utiliza el ZIP completo, configura XAMPP y ejecuta el instalador que ya trae el proyecto.

## Compatibilidad y corrección

Se corrigió `partials/header.php` para cargar siempre el CSS del tema activo **después** de las hojas generales; antes ciertos temas quedaban sobreescritos por enterprise-blue.css. Los tres CSS nuevos no reutilizan ni importan el CSS de los temas anteriores.

Se mantienen noticias, slider, mapa, comercios, perfiles PRO, catálogo, instalador y templates previos.

## Pruebas

Pendiente la validación funcional y visual con una instalación real de Apache y MySQL en Windows.
