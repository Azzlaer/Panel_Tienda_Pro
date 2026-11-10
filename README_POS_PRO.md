# ComercioSocial Enterprise — Inventario + Punto de Venta PRO (v6.6)

## Qué incluye
- `public/pos.php`: registro de ventas presenciales ya cobradas por el comerciante, historial de sus 30 ventas recientes y totales del día.
- `admin/pos.php`: activación administrativa de tres tablas y resumen.
- `app/pos.php`: servicio de registro transaccional con SELECT ... FOR UPDATE, validación de propietario, stock conocido y descuento atómico.
- `pos_sales`, `pos_sale_items`, `pos_stock_movements`: registros y auditoría básica de movimientos.
- CSS Corporate Blue independiente.
- Accesos desde inventario y AdminCP.

## Instalación / activación
Respaldar archivos y MySQL. Desplegar los nuevos archivos sin sobrescribir la configuración ni las imágenes. Con sesión de administrador, abrir `/admin/pos.php` y pulsar Activar POS. No elimina tablas ni modifica los módulos anteriores. Los vendedores registrados usan `/public/pos.php`.

## Límites y seguridad
El POS NO procesa tarjetas, NO valida transferencias, NO emite boletas, facturas ni documentos fiscales. El medio de pago es un registro manual de un cobro externo ya completado. No abrir la caja a personal no autorizado sin roles por empleado. Requiere stock numérico (no NULL); no deja vender más unidades que las disponibles. Ventas registradas no ofrecen anulación ni reembolso en esta etapa.
La prueba automática es estática/sintáctica. Probar en MariaDB real, especialmente transacciones y ventas simultáneas, antes de producción.
