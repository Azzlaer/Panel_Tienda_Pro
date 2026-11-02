# ComercioSocial Enterprise v5.5 — Theme Studio PRO

## Instalación (XAMPP / PHP 8.2+ / MySQL)

1. Haz copia de seguridad de la carpeta y la base MySQL.
2. En instalaciones existentes, conserva `config/config.php`, `storage/installed.lock`, `public/uploads/` y tus datos en `storage`.
3. Actualiza los archivos del proyecto en un clon de prueba, NO sobrescribas los archivos de configuración.
4. Entra a **AdminCP → Apariencia → Theme Studio PRO** (`admin/theme_studio.php`).
5. Elige una plantilla desde **Plantillas**, luego personaliza sus colores, tipografía, medidas, orden y visibilidad de secciones.
6. Usa la vista previa integrada y pulsa **Publicar diseño** para guardar. **Restablecer** devuelve el tema a sus ajustes de Studio iniciales.

## Compatibilidad

Se utiliza la tabla `settings` ya existente (clave `studio_theme_<slug>` con JSON). No se eliminan ni alteran tablas existentes y no hace falta ejecutar SQL manual. El instalador web original se mantiene. Se conservan las diez plantillas y todos los módulos de la v5.4.

## Seguridad y límites

Sólo los administradores pueden guardar ajustes; se valida CSRF. Los colores y rangos se validan en servidor, y no se acepta CSS libre mediante este editor. El editor funciona sobre bloques existentes; algunos módulos pueden disponer de una estructura específica para su plantilla. La vista previa se limita al origen local y usa un iframe de la portada real; no publica cambios hasta guardar. Para entornos públicos se recomienda mantener al día PHP y revisar el endurecimiento general del aplicativo.
