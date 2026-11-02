# ComercioSocial Enterprise v5.3 — Commerce Gallery PRO

## Novedades
Séptimo template original inspirado en el concepto de galería fotográfica: nueva estructura visual de portada, categorías, productos, fichas de comercios, noticias y perfiles PRO, conservando la lógica PHP actual. No incluye archivos de TemplateMonster ni imágenes de terceros.

## Activación en XAMPP
1. Respalda archivos y MySQL.
2. Instala el ZIP completo en una copia de la aplicación, conservando `config/config.php`, `storage/installed.lock`, `public/uploads/` y datos persistentes.
3. Abre `admin/templates.php` y activa `Commerce Gallery PRO`.
4. Alternativamente, instala `THEMES_INSTALLABLES/commerce-gallery-pro.zip` mediante el gestor de temas de tu versión 5.2, sin reemplazar otros archivos.
5. Publica imágenes panorámicas en el slider desde `admin/slides.php`; también puedes establecer una imagen de portada en el configurador para el caso sin slides. Usa fotografías propias con derechos de uso.

## Conservado
Noticias, slider, perfiles PRO, mapa de locales, comercios, catálogo, cuentas, MySQL, instalador y seis plantillas anteriores. No hay migraciones adicionales.

## Notas
El tema está hecho con CSS responsivo y SVG original, sin dependencias externas. Las transiciones respetan reducción de movimiento. Se verifica sintaxis PHP y ZIP de forma estática. Falta prueba end-to-end con XAMPP y una base real.
