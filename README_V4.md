# ComercioSocial Enterprise v4 · Slider de portada

## Nuevo módulo
- AdminCP → Contenido → Slider de portada (`admin/slides.php`).
- Sube imágenes desde el administrador, añade título, descripción, botón/enlace, estado y orden.
- Configura cada cuántos segundos cambia la imagen (3–60 s; por defecto 6).
- Si hay cero imágenes activas, se muestra la portada anterior. Con una imagen no hay rotación ni controles. Con dos o más, rota automáticamente.
- Flechas, indicadores, pausa al pasar el ratón/foco, navegación con teclado y respeto por la preferencia de movimiento reducido.

## XAMPP
1. Haz copia de seguridad de la carpeta y la base de datos actuales.
2. Copia el proyecto a otra carpeta en `C:\xampp\htdocs\` y conserva tu `config/config.php` y `public/uploads/` desde tu instalación operativa.
3. No vuelvas a ejecutar el instalador sobre una base ya instalada.
4. Accede como administrador a `admin/slides.php`. Se creará la tabla `home_slides` al entrar; opcionalmente ejecútala desde `database/migrations/2026_10_28_000004_home_slider.sql`.
5. Carga al menos una imagen JPG/PNG/WebP (recomendadas 1600x600 px). Para animación, carga dos o más y márcalas activas.

## Seguridad
- Formulario protegido por sesión de administrador y CSRF.
- Imágenes subidas mediante el validador de MIME del proyecto.
- Enlaces limitados a rutas relativas del sitio o HTTPS.
- Las imágenes eliminadas de la lista no se borran del disco automáticamente para evitar eliminar medios aún utilizados.
- No se incluyen configuraciones privadas de la instalación original.

Comprobación local: sintaxis PHP y empaquetado ZIP. No se ha ejecutado contra un servidor MySQL real.
