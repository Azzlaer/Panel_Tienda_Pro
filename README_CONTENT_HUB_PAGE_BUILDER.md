# ComercioSocial Enterprise — Content Hub + Page Builder PRO

## Componentes
- Content Hub: admin/content_hub.php para crear/editar/eliminar artículos; categorías editoriales, imagen destacada, etiquetas, resumen, publicación/borrador/oculto, contador de visitas y metadatos SEO.
- Page Builder: admin/page_builder.php con editor de bloques reordenables (portada, título, texto, imagen, destacado, botón/CTA y separador), JSON validado en servidor, borradores y páginas mostrables en navegación.
- Público: public/content_hub.php catálogo editorial (búsqueda, filtro y paginación); public/content_article.php?slug=... artículos; public/page.php?slug=... páginas.
- Integración: enlaces en AdminCP, navegación pública automática para páginas publicadas y activación desde admin/integration_center.php.
- Corporate Blue: hojas CSS independientes, responsive; no reemplaza Theme Studio ni el sistema existente de noticias y páginas.

## Activación
1. Respalda archivos y MySQL; actualiza código solo después de revisar el PR.
2. Abre admin/integration_center.php y activa Content Hub + Page Builder (o admin/content_hub.php).
3. El sistema crea las tablas hub_categories, hub_articles y hub_pages con CREATE TABLE IF NOT EXISTS. No elimina ni altera tablas existentes.
4. Visita admin/content_hub.php, crea categorías y artículos. Selecciona Publicado para hacerlo visible.
5. Visita admin/page_builder.php, agrega y reordena bloques y publica la página. Opcionalmente activa Mostrar enlace en el menú público.
6. Abre las páginas públicas y comprueba visualmente la compatibilidad de tu tema en XAMPP y móvil.

## Seguridad y límites
- Acciones administrativas requieren require_admin, CSRF y consultas PDO preparadas.
- Las páginas públicas no enseñan borradores ni contenido oculto.
- Los bloques y artículos utilizan texto escapado, no ejecutan HTML/PHP arbitrario. Los enlaces CTA admiten HTTPS o rutas internas permitidas; las imágenes, HTTPS o rutas locales assets/ y uploads/.
- Imágenes destacadas utilizan mecanismo central upload_image() (MIME y tamaño controlados).
- Máximo 40 bloques por página, documento JSON hasta 65 KB. El constructor permite mover, añadir y quitar bloques, no arrastrar y soltar.
- Artículos son texto seguro con párrafos; se conserva editor BBCode/HTML seguro del módulo Noticias sin modificarlo.
- Slugs de artículos y páginas únicos dentro de su sección. Sin versiones de páginas, traducciones ni comentarios en esta primera entrega.
- Mantén activas reglas de public/uploads y HTTPS en producción.

## Compatibilidad y pruebas
PHP 8.2/8.3, PDO MySQL/MariaDB y XAMPP. Pruebas unitarias, lint y MariaDB automatizadas. Validar navegador, servidor y tema personalizado antes de fusionar.
