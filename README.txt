MiTienda - INSTALACIÓN LIMPIA AUTOMÁTICA (PHP 8.1+ / XAMPP)
================================================================
1. Extrae la carpeta tienda dentro de D:\xampp\htdocs\
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Abre http://127.0.0.1/tienda/
4. Se abrirá automáticamente /tienda/install/index.php.
5. Completa host, puerto, base nueva, usuario/clave MySQL y administrador.
6. Pulsa Instalar. El instalador crea las tablas y la cuenta admin.
7. Abre MiTienda, inicia sesión y entra al AdminCP.

NO es necesario importar database.sql manualmente ni editar la conexión.
El instalador genera config/database.php y config/installed.lock.
No vienen incluidos en el ZIP para que la instalación sea realmente limpia.

ATENCIÓN: usa una base de datos NUEVA y VACÍA. El instalador se niega a
instalar sobre una base con tablas existentes para proteger datos previos.
No sobrescribas tu proyecto original sin hacer un respaldo.

La ruta web por defecto es /tienda/; si cambias el nombre de carpeta,
edita BASE_URL en config/config.php antes de abrir el instalador.

REQUISITOS: PHP 8.1+ con mysqli, mbstring y fileinfo; MySQL/MariaDB.
Usuario MySQL con permiso para CREATE DATABASE y CREATE TABLE.
Para producción, elimina install/ después de instalar y configura HTTPS.

Este ZIP es una reconstrucción funcional de prueba, no una copia exacta de
archivos originales del usuario. Algunas funciones avanzadas están pendientes.
