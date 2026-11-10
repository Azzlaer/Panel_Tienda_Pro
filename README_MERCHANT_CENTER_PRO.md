# Merchant Center PRO — ComercioSocial Enterprise

## Alcance
Panel privado del comerciante, métricas reales de publicaciones, vistas, inventario bajo, ventas presenciales POS y pedidos registrados cuando los módulos están activados. CRM interno con contactos y notas, tareas asignables y colaboradores con roles gerente, soporte y analista.

El panel reutiliza catálogo, POS y Pedidos, sin reescribir transacciones ni duplicar precios o stock. No ofrece procesamiento de pagos, facturación ni acceso del empleado a POS/Inventario: solo al nuevo Merchant Center.

## Permisos
- Propietario: todas las pantallas, CRM, tareas y administración de colaboradores.
- Gerente y soporte: dashboard, CRM y tareas, con escritura.
- Analista: dashboard, CRM y tareas de solo lectura.
- Empleados de otros comercios: acceso denegado mediante owner_id y validación de rol.
- Colaboradores deben ser usuarios existentes y activos; revocación inmediata, máximo 20 activos.

## Instalación XAMPP
1. Respaldar archivos y MySQL.
2. Tras revisar/fusionar el PR, entrar en admin/integration_center.php y activar Merchant Center PRO.
3. La migración crea merchant_staff, merchant_contacts, merchant_notes y merchant_tasks, sin cambiar tablas actuales.
4. Los comerciantes acceden a public/merchant_center.php. El AdminCP muestra métricas agregadas en admin/merchant_center.php.
5. Validar PHP 8.2/8.3, PDO MySQL, mbstring, Apache, sesión y tema.

## Límites
- Importe POS es registro manual fuera de plataforma, no ingreso bancario verificado; los pedidos no se suman al mismo total.
- Los contactos CRM son privados; definir consentimiento y políticas de retención y eliminación antes de guardar datos personales reales.
- Los colaboradores se agregan por usuario existente, sin flujo de aceptación por correo.
- No hay integración ERP, facturación fiscal ni automatización de envíos.
- Pruebas CI no sustituyen pruebas reales de navegador/XAMPP.
- PR #12 Content Hub y #13 Marketplace siguen independientes; todos modifican navegación e Integration Center y pueden requerir resolución de conflictos.
