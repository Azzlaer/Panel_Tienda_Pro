(() => {
 'use strict';
 const allowed=new Set(['none','gold','neon','emerald','fire','rainbow','pulse']);
 document.querySelectorAll('[data-vip-picker]').forEach(select=>{
  const preview=select.closest('.vip-effect-card')?.querySelector('.vip-effect-preview span');
  if(!preview)return;
  select.addEventListener('change',()=>{
   const effect=allowed.has(select.value)?select.value:'none';
   preview.className=effect==='none'?'':'vip-fx vip-fx-'+effect;
  });
 });
})();
