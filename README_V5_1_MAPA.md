# ComercioSocial Enterprise v5.1 — Mapa interactivo de locales

## Contenido
Partimos de la v5.0 Commerce Hub, conservando perfiles PRO, dashboard, noticias, slider, directorio y el instalador original.

- `admin/map.php`: configuración de plano, posicionamiento porcentual y eliminación de marcadores.
- `public/map.php`: plano público responsivo con marcadores, filtro y enlaces a los perfiles.
- `database/migrations/2026_10_30_000006_commerce_map.sql`: creación no destructiva de tablas `commerce_map_settings` y `commerce_map_pins`.
- CSS/JS locales sin CDN.

## Instalación XAMPP
1. Haz un respaldo de archivos y MySQL. No sobrescribas sin respaldo.
2. Para instalación nueva, coloca la carpeta de la aplicación en `C:\xampp\htdocs\comerciosocial` e inicia Apache y MySQL. Utiliza el instalador existente.
3. Para actualizar, conserva el archivo de configuración, `storage/installed.lock`, `public/uploads/` y la base actual. No vuelvas a ejecutar el instalador.
4. Accede a `/admin/map.php` con tu cuenta administradora. Se crean las tablas automáticamente al abrir esta página (el usuario MySQL necesita permiso CREATE). Alternativamente aplica el SQL de la migración.
5. Sube el plano, selecciona un comercio del directorio, haz clic sobre su ubicación y pulsa Guardar ubicación.
6. Revisa `/map.php` en la vista pública. Si no aparecen comercios, crea primero una ficha visible en `/admin/commerces.php`.

## Notas
El sistema utiliza una imagen del plano, no cartografía GPS; los porcentajes de posicionamiento se mantienen al cambiar el tamaño de pantalla. Los marcadores se vinculan por clave foránea a fichas existentes. El plano se guarda con el validador de imágenes del sistema. Aún no se han probado operaciones reales contra MySQL de tu equipo.
