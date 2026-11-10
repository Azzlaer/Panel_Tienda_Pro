# ComercioSocial Enterprise — AdminCP Nexus 2.0

## Alcance
Nueva capa de estilo independiente `public/assets/css/admin-nexus.css`, integrada sin modificar las plantillas públicas.
Barra de búsqueda de módulos en el encabezado del AdminCP, con atajo `Ctrl+K`/`Cmd+K` y Escape para limpiar.
Centro de operaciones con accesos directos a productos, comercios, Theme Studio y salud del sistema.
Diseño responsive para escritorio y móvil; navegación original y permisos de administradores conservados.

## Pruebas
`php tests/admin_nexus_smoke.php` verifica archivos y enlaces de integración.
GitHub Actions ejecuta sintaxis PHP 8.2 y 8.3 y prueba estática Nexus.
Se requieren pruebas manuales de navegación, móvil y flujos completos en XAMPP/MariaDB.

## Compatibilidad
Sin nuevas tablas, sin cambios de esquema ni credenciales. Se preservan noticias, slider, comercios, mapa, perfiles PRO, Commerce Suite y temas existentes.
