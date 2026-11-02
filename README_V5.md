# ComercioSocial Enterprise v5.0 — Commerce Hub

## Novedades
- Dashboard AdminCP: indicadores de comercios activos, noticias publicadas, banners y perfiles PRO; ranking de vendedores según artículos publicados y vistas registradas de sus artículos.
- Dashboard vendedor: `public/dashboard.php`, accesible desde **Mi panel**, con métricas de publicaciones, vistas acumuladas de productos, pendientes y ventas marcadas.
- Perfiles PRO: horarios, especialidades, sitio web, Instagram y Facebook HTTPS; insignia de verificación reservada a la administración (el campo existe en base de datos, aún no incluye interfaz para asignarlo).
- Perfil público enriquecido y formulario de edición extendido.
- Tabla independiente `profile_pro` con clave externa a users, compatible con bases anteriores. Se crea automáticamente al abrir el formulario o perfil; SQL opcional en `database/migrations/2026_10_30_000005_profile_pro.sql`.
- Conserva módulo de noticias, comercios, slider, catálogo, instalador y temas de v4.

## Instalación XAMPP
1. Respaldar base de datos y `public/uploads/`.
2. Descomprimir en una carpeta de prueba bajo `C:\xampp\htdocs\`.
3. Si ya existe una instalación, copiar **tu** `config/config.php`, `storage/installed.lock` y `public/uploads/`; no reemplazar la base de datos ni ejecutar instalador otra vez.
4. Abrir el panel administrativo, el perfil del vendedor y `dashboard.php`.
5. Si el usuario SQL carece de permisos CREATE TABLE, aplicar migración `2026_10_30_000005_profile_pro.sql` con usuario de mantenimiento.

## Consideraciones
- Estadísticas basadas en las tablas existentes; vistas de productos no equivalen a visitantes únicos, ventas registradas sólo son productos marcados `sold`.
- La portada y el avatar ya se almacenaban en `profiles`; se reutilizan.
- Enlaces de redes sociales y web requieren URL HTTPS y se escapan al mostrarse.
- Sintaxis PHP validada, sin prueba de integración MySQL/Apache en este entorno.
