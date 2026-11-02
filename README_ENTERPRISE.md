# ComercioSocial Enterprise 2.0 — LatinStyle Corporate Blue

## Qué se corrigió
- **Problema raíz:** faltaba cargar `public/assets/css/urban.css`; la estructura HTML usa clases urban-* y, sin su hoja de estilos, se desarma.
- Reescritura de la capa de estilos Enterprise con una arquitectura visual coherente inspirada en LatinBattle URL Enterprise.
- Menú lateral del AdminCP reparado: las opciones se pliegan independientemente del botón de sección, que permanece visible.
- Distribución responsive del encabezado, búsqueda, filtros, grid de productos, tarjetas y pie de página.
- Sin dependencias CSS externas; mantiene estilos y plantillas existentes, base de datos y rutas.

## Instalación nueva XAMPP
1. Descomprimir en `C:\xampp\htdocs\comerciosocial`.
2. Iniciar Apache y MySQL en XAMPP.
3. Abrir `http://localhost/comerciosocial/` y completar el instalador si se solicita.
4. Si el servidor ya estaba instalado, conservar `config/config.php` y la base de datos existente.

## Actualizar una instalación existente (recomendado)
Primero crear un respaldo. Copiar los archivos desde el ZIP reemplazando los correspondientes, **excepto** la carpeta `config/`, `storage/` y las subidas de `public/uploads/`. Conservar también plantillas personalizadas. No ejecutar el instalador sobre una base existente.

## Qué no se cambió
No se alteran datos SQL, operaciones, registros, usuarios ni lógica de producto. La renovación es visual y de navegación. No se ha probado en un servidor MySQL real; validar login, registro, publicar, editar y AdminCP en la instalación local.

## Archivos principales modificados
- `partials/header.php`
- `admin/partials/header.php`
- `admin/partials/footer.php`
- `public/assets/css/enterprise-blue.css`
- `public/assets/css/enterprise-admin.css`
- `public/assets/js/enterprise-admin.js`
