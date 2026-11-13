(() => {
 'use strict';
 document.querySelectorAll('[data-confirm-delete]').forEach(button=>button.addEventListener('click',event=>{
  if(!confirm('¿Eliminar este enlace del menú público?'))event.preventDefault();
 }));
 document.querySelectorAll('[data-confirm-reset]').forEach(button=>button.addEventListener('click',event=>{
  if(!confirm('¿Restaurar todos los enlaces originales del menú? Se perderán tus cambios de navegación.'))event.preventDefault();
 }));
})();