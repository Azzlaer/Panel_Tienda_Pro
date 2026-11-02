(()=>{
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  $$('[data-preview]').forEach(input=>input.addEventListener('change',()=>{
    const file=input.files?.[0], target=document.getElementById(input.dataset.preview); if(!file||!target)return;
    const src=URL.createObjectURL(file); target.src=src;
    if(input.name==='avatar'){const side=$('#sideAvatar');if(side)side.src=src;}
    if(input.name==='cover'){const side=$('#sideCover');if(side)side.style.backgroundImage=`url('${src}')`;}
    if(input.name==='cover_image'){const side=$('#sideProductCover');if(side)side.src=src;}
  }));
  $$('[data-counter]').forEach(el=>{const target=document.getElementById(el.dataset.counter);const draw=()=>target&&(target.textContent=`${el.value.length} / ${el.maxLength||'∞'} caracteres`);el.addEventListener('input',draw);draw();});
  const gallery=$('#galleryInput'), preview=$('#galleryPreview'), status=$('#galleryStatus');
  gallery?.addEventListener('change',()=>{preview.innerHTML='';const files=[...gallery.files];status.textContent=`${files.length} fotografía${files.length===1?'':'s'} seleccionada${files.length===1?'':'s'}`;status.style.color=(files.length>=3&&files.length<=6)?'#198754':'#c62828';files.slice(0,6).forEach(f=>{const img=new Image();img.src=URL.createObjectURL(f);img.alt=f.name;preview.appendChild(img);});if(files.length>6)gallery.setCustomValidity('Selecciona como máximo 6 fotografías.');else if(files.length<3)gallery.setCustomValidity('Selecciona al menos 3 fotografías.');else gallery.setCustomValidity('');});
  const price=$('#priceClp'), formatted=$('#priceFormatted'), sidePrice=$('#sideProductPrice');
  const drawPrice=()=>{if(!price)return;const n=Number(String(price.value).replace(/\D/g,''))||0;const txt=new Intl.NumberFormat('es-CL',{style:'currency',currency:'CLP',maximumFractionDigits:0}).format(n);if(formatted)formatted.textContent=`Se mostrará como ${txt}`;if(sidePrice)sidePrice.textContent=txt;};price?.addEventListener('input',drawPrice);drawPrice();
  const title=$('[name="title"]'), sideTitle=$('#sideProductTitle');title?.addEventListener('input',()=>sideTitle&&(sideTitle.textContent=title.value||'Título de tu artículo'));
})();
