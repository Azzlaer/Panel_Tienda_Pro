# ComercioSocial Enterprise — Inventario + Pedidos PRO
## Instalación
Respaldar archivos, configuración y MySQL. Se mantiene el proyecto y temas existentes. En AdminCP abrir `admin/orders_pro.php` y activar las dos tablas aditivas. No requiere un pago online ni cambios a las tablas antiguas.
## Flujo
Comprador registrado solicita producto publicado desde `public/orders_pro.php` (ID de producto y cantidad). Vendedor confirma o rechaza; en confirmación se comprueba y descuenta stock numérico con transacción. Comprador puede cancelar mientras esté solicitado. Vendedor puede marcar completado después de confirmar.
## Restricciones
No hay pago online, emisión tributaria, carrito multivendedor, cancelación tras confirmación, reembolso automático ni restauración de stock; las transiciones no permitidas se rechazan. La creación de pedido NO reserva unidades; la confirmación falla si otro pedido consume stock primero.
El precio y nombre se guardan como instantánea. Para la primera etapa solo se admite un producto por pedido. Las consultas previas de `suite_requests.php` siguen siendo independientes y no descuentan stock.
## Verificación pendiente
Las pruebas automatizadas son de sintaxis y estructura. Se requiere un entorno XAMPP/MariaDB real con cuentas y artículos de prueba, especialmente condiciones de carrera y permisos antes de habilitarlo en producción.
