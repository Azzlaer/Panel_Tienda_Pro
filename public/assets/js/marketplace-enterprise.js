(() => {
 'use strict';
 const form=document.getElementById('marketplaceCompareForm');
 if(!form)return;
 const boxes=Array.from(form.querySelectorAll('.mkt-compare-checkbox'));
 const counter=document.getElementById('mktCompareCount');
 const refresh=()=>{const selected=boxes.filter(x=>x.checked).length;counter.textContent=selected+' de 4 productos seleccionados';};
 boxes.forEach(box=>box.addEventListener('change',()=>{
  if(boxes.filter(x=>x.checked).length>4){box.checked=false;alert('El comparador admite hasta cuatro artículos.');}
  refresh();
 }));
 form.addEventListener('submit',event=>{
  if(!boxes.some(x=>x.checked)){event.preventDefault();alert('Selecciona al menos un artículo para comparar.');}
 });
 refresh();
})();
