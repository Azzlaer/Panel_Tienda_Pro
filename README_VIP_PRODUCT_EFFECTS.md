# VIP Product NameEffects — ComercioSocial Enterprise

Adaptación del modelo OmegaMU NameEffects Manager GUI a publicaciones comerciales: catálogo de estilos predefinidos, asignación por producto y vigencia del permiso VIP.

## Componentes
- Tabla vip_seller_access: membresía VIP concedida/revocada desde AdminCP, vencimiento opcional.
- Tabla vip_product_effects: un efecto por artículo, elegido por su propietario.
- Siete estilos: ninguno, oro, neón azul, esmeralda, fuego, arcoíris y pulso.
- Portada, Marketplace y página del producto renderizan títulos con efecto solo si está publicado y el vendedor VIP está activo.
- Admin: admin/vip_product_effects.php; vendedor: public/vip_product_effects.php, enlazado desde Inventario PRO.
- CSS Corporate Blue independiente y preferencia reduced-motion respetada.

## Instalación
1. Respaldar archivos y MySQL, revisar y fusionar el PR.
2. Activar Efectos VIP de artículos en admin/integration_center.php. Crea dos tablas nuevas sin alterar productos actuales.
3. Autorizar el VIP del vendedor en admin/vip_product_effects.php y elegir fecha de expiración opcional.
4. El vendedor selecciona un efecto individual en public/vip_product_effects.php.
5. Verificar títulos en portada, Marketplace y ficha. Probar vencimiento y revocación.

## Seguridad y alcance
El vendedor necesita VIP activo y propiedad del producto; los cambios usan CSRF, PDO preparado y validación estricta de una lista de efectos. No permite CSS, HTML o JavaScript arbitrario. Los títulos se escapan siempre. Al vencer el VIP desaparece el efecto sin borrar la elección, que reaparecerá si se renueva.
Sin pagos automáticos ni conversión de tablas MSSQL de OmegaMU. Los efectos son decorativos, no alteran ranking, promoción ni precio. Falta validación visual en navegador real con XAMPP.