# Marketplace Enterprise — ComercioSocial v7

## Estado y alcance
Módulo independiente basado en la tabla products existente. No cambia productos, noticias, Theme Studio, Commerce Suite, POS, Pedidos, QR, Content Hub ni los diseños originales. Estilo Corporate Blue con CSS propio.

## Funcionalidades
- Catálogo público en /public/marketplace.php: búsqueda por título, descripción, categoría y nombre comercial; categoría, vendedor, precio mínimo y máximo, existencias, orden por vistas/precios/nombre/recientes, paginación.
- Comparación pública en /public/marketplace_compare.php: hasta 4 artículos, precios CLP, categoría, vendedor, stock, visitas y descripción; valida que todos sigan publicados.
- Favoritos privados y búsquedas guardadas para usuarios registrados; acciones POST + CSRF en /public/marketplace_actions.php y colección privada en /public/marketplace_saved.php.
- Administración de métricas agregadas en /admin/marketplace.php y activación en /admin/integration_center.php.
- Enlaces desde menú principal, detalle de artículo y menú de AdminCP.
- Pruebas unitarias de filtros y sanitización, MariaDB de migraciones y privacidad, GitHub Actions PHP 8.2 y 8.3.

## Activación en XAMPP
1. Respaldar MySQL y archivos. Actualizar los archivos tras revisar y fusionar este PR.
2. Entrar como administrador a /admin/integration_center.php, activar Marketplace Enterprise.
3. La migración aditiva crea marketplace_favorites y marketplace_saved_searches; no modifica ni borra productos.
4. Verificar catálogo, filtros, comparación, guardado y eliminación con dos cuentas de prueba.
5. Mantener PHP 8.2+, extensiones pdo_mysql/mbstring y mod_rewrite/Apache existentes.

## Consideraciones
La disponibilidad con stock NULL se representa como «consultar», no como stock garantizado. No incluye procesamiento de pagos, facturación, carritos multivendedor ni notificaciones automáticas. El orden «más vistos» refleja visitas, no ventas. Las búsquedas guardadas son privadas (máximo 30 por usuario); no se guardan datos de usuario en URLs públicas. Favoritos y búsquedas exigen login. No se han ejecutado pruebas de interfaz visual en navegador/XAMPP real.
El PR de Content Hub (#12) sigue independiente y no se incluye en esta rama; no fusionar ramas sin revisar conflictos de navegación.
