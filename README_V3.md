# ComercioSocial Enterprise v3 — Noticias + Comercios

## Noticias
El editor de noticias BBCode/HTML ya existía y se conserva en `admin/news.php`. Nueva página pública `public/news_list.php`, lista paginada.

## Comercios
Nuevo módulo `admin/commerces.php`: vincular un usuario activo con una tarjeta de nombre, número de local, imagen, descripción, visibilidad, destacado y orden. Directorio público `public/commerces.php`, búsqueda y enlace hacia `public/profile.php?u=...`, donde ya se muestran sus productos. Una ficha por usuario. Los comercios destacados se muestran en la portada.

## Instalación
Hacer copia de seguridad de archivos y base de datos antes de actualizar. Descomprimir en una instalación de pruebas XAMPP. Conservar `config/config.php`, `public/uploads/`, la base MySQL y otros archivos propios. La tabla `commerce_directory` se crea al primer acceso al módulo o puede instalarse con `database/migrations/2026_10_28_000003_commerce_directory.sql` (no ejecutar ambas operaciones a ciegas en entornos con políticas restrictivas). No se requiere reinstalar. Las noticias existentes se conservan. El paquete no incluye config con credenciales.

## Limitaciones
Se pasó el lint sintáctico PHP, no una prueba de extremo a extremo con MySQL en XAMPP. El sanitizador HTML heredado requiere endurecimiento y auditoría antes de permitir contenido de administradores no confiables.
