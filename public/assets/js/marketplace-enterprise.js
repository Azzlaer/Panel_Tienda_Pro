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

/* Small progressive enhancement: autocomplete never changes server-side validation. */
(() => {
 'use strict';
 const search=document.querySelector('[data-marketplace-autocomplete]');
 const list=document.getElementById('mkt-product-suggestions');
 if(!search||!list)return;
 let timer,controller;
 search.addEventListener('input',()=>{
  clearTimeout(timer);if(controller)controller.abort();
  list.replaceChildren();const q=search.value.trim();
  if(q.length<2||q.length>60)return;
  timer=setTimeout(async()=>{
   controller=new AbortController();
   try{
    const url=new URL(search.dataset.suggestUrl,window.location.href);
    url.searchParams.set('q',q);
    const response=await fetch(url.toString(),{signal:controller.signal,credentials:'same-origin',headers:{Accept:'application/json'}});
    if(!response.ok||search.value.trim()!==q)return;
    const data=await response.json();
    if(!Array.isArray(data)||search.value.trim()!==q)return;
    const fragment=document.createDocumentFragment();
    data.slice(0,7).forEach(title=>{
     if(typeof title!=='string')return;
     const option=document.createElement('option');option.value=title;fragment.append(option);
    });
    list.replaceChildren(fragment);
   }catch(error){if(error?.name!=='AbortError')list.replaceChildren();}
  },250);
 });
})();
