# ComercioSocial Enterprise v6.8 — Commerce Enterprise Core

## Alcance implementado
- `app/inventory_core.php`: API compartida de inventario para futuros POS y Pedidos PRO. Contiene función de disminución de stock **con transacción activa**, validación de propietario/stock y función de captura de producto con `FOR UPDATE`.
- `public/inventory.php`: los ajustes manuales de precios y stock ahora se ejecutan en una transacción y, si la auditoría está activada, registran valores anteriores y nuevos.
- `admin/core_inventory.php`: activación de auditoría y consulta de los últimos 40 ajustes.
- `database/migrations/2026_11_02_000010_inventory_core.sql`: tabla aditiva `core_inventory_audit`.
- Tests en PHP 8.2/8.3, sin dependencias de base de datos para smoke tests.

## Activación
Respaldar archivos y base MySQL. Actualizar desde la rama una vez revisada. Abrir `/admin/core_inventory.php` como administrador y activar auditoría. La migración no elimina ni cambia otras tablas.

## Compatibilidad y límites
Los PR #3 a #7 siguen independientes y sin fusionar. Por tanto, POS y Pedidos PRO **todavía no invocan** el servicio compartido de v6.8; esto requerirá integración explícita al fusionarlos o actualizar sus respectivas ramas. No afirmar que este PR aporta una integración funcional completa entre ellos.
El registro de auditoría es para cambios realizados por `public/inventory.php`, no para modificaciones hechas desde otras rutas administrativas o mediante SQL directo.
La función común no crea transacciones por su cuenta y no aplica reglas contables ni de reembolsos. No hay pruebas de concurrencia sobre MariaDB real en este PR.
