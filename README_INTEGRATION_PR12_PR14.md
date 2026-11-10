# ComercioSocial Enterprise — Consolidación PR #12, #13 y #14

Esta rama integra íntegramente el Content Hub + Page Builder, Marketplace Enterprise y Merchant Center PRO sobre `main`, conservando los sistemas y temas preexistentes (Corporate Blue, Theme Studio, noticias, Commerce Suite, POS, pedidos, QR, hCaptcha y Security Center).

## Archivos compartidos integrados
- `admin/integration_center.php`: activación independiente, segura y administrativa de **Content Hub**, **Marketplace** y **Merchant Center**; sin ejecutar migraciones desde páginas públicas.
- `admin/partials/header.php`: enlaces de los tres módulos y del Page Builder junto a la navegación existente.
- `partials/header.php`: enlaces para Marketplace, Merchant Center de usuarios registrados y páginas publicadas del Content Hub.
- `partials/footer.php` y `public/product.php`: conserva los enlaces de los módulos.
- `.github/workflows/php-lint.yml`: sintaxis PHP 8.2/8.3, regresiones existentes, pruebas unitarias de los tres módulos, JavaScript y MariaDB 11.4 de todos los módulos con bases de datos de CI aisladas.
- Las tres migraciones son aditivas y crean **nueve tablas** en total, sin borrar datos.

## Instalación en XAMPP
1. **Respalda archivos y MySQL** y revisa el PR antes de desplegar.
2. Actualiza el código después de fusionar.
3. Entra como administrador a `admin/integration_center.php`.
4. Activa Content Hub + Page Builder, Marketplace Enterprise y Merchant Center PRO uno por uno.
5. Verifica que el menú principal conserve sus secciones existentes y que se puedan abrir las nuevas páginas.
6. Prueba registro/login, permisos por comercio, creación y publicación de artículos, Page Builder, favoritos, búsquedas guardadas, CRM, staff y tareas.
7. No utilices la opción destructiva **«Empezar de cero»** para actualizar.

## Precauciones
- Content Hub usa editor de bloques y contenido escapado, no PHP ni HTML arbitrario.
- Marketplace no procesa pagos ni modifica inventarios.
- Empleados Merchant Center no obtienen automáticamente acceso al POS ni al inventario.
- Datos del CRM pueden ser personales y necesitan política de privacidad y retención.
- Las pruebas CI **no** sustituyen pruebas visuales y funcionales con navegador y Apache/XAMPP real.
- Los PR originales quedan abiertos hasta que se verifique la integración y se cierre la redundancia sin volver a fusionar ramas antiguas.
