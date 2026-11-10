(() => {
 'use strict';
 const form=document.getElementById('hubPageForm');
 if(!form)return;
 const field=document.getElementById('hubBlockJson');
 const wrap=document.getElementById('hubBlocks');
 const preview=document.getElementById('hubPreview');
 const types={hero:'Portada',heading:'Título',text:'Texto',image:'Imagen',callout:'Destacado',cta:'Botón / CTA',divider:'Separador'};
 let blocks=[];
 try {const raw=JSON.parse(field.value);if(Array.isArray(raw))blocks=raw.filter(b=>b && Object.hasOwn(types,b.type)).slice(0,40).map(b=>({type:b.type,title:String(b.title||''),body:String(b.body||''),image:String(b.image||''),link:String(b.link||'')}));}
 catch(e){blocks=[];}
 function el(tag,cls,txt){const n=document.createElement(tag);if(cls)n.className=cls;if(txt!==undefined)n.textContent=txt;return n;}
 function sync(){field.value=JSON.stringify(blocks);preview.replaceChildren();blocks.forEach((b,i)=>{const item=el('div','hub-preview-item');item.append(el('small','',(i+1)+' · '+types[b.type]));if(b.title)item.append(el('strong','',b.title));if(b.body)item.append(el('p','',b.body.slice(0,200)));preview.append(item);});if(!blocks.length)preview.append(el('p','','Todavía no has agregado bloques.'));}
 function render(){
   wrap.replaceChildren();
   blocks.forEach((b,i)=>{
     const card=el('div','hub-editor-card');const top=el('div','hub-editor-top');
     top.append(el('strong','',(i+1)+'. '+types[b.type]));
     const actions=el('div','hub-editor-actions');
     [['↑',()=>{if(i>0){[blocks[i-1],blocks[i]]=[blocks[i],blocks[i-1]];render();}}],['↓',()=>{if(i<blocks.length-1){[blocks[i+1],blocks[i]]=[blocks[i],blocks[i+1]];render();}}],['Eliminar',()=>{blocks.splice(i,1);render();}]].forEach(([label,fn])=>{
       const btn=el('button','',label);btn.type='button';btn.addEventListener('click',fn);actions.append(btn);
     });
     top.append(actions);card.append(top);
     const addField=(label,key,multi=false)=>{
       const l=el('label','',label);const n=el(multi?'textarea':'input');if(!multi)n.type='text';
       n.value=b[key]||'';n.maxLength=(key==='body'?10000:key==='title'?180:1000);
       if(multi)n.rows=4;
       n.addEventListener('input',()=>{b[key]=n.value;sync();});l.append(n);card.append(l);
     };
     if(['hero','heading','callout','cta','image'].includes(b.type))addField(b.type==='image'?'Pie de imagen / texto alternativo':'Título','title');
     if(['hero','text','callout','cta'].includes(b.type))addField('Texto','body',true);
     if(b.type==='image')addField('Ruta HTTPS o assets/ / uploads/','image');
     if(b.type==='cta')addField('Enlace HTTPS o archivo .php local','link');
     wrap.append(card);
   });
   sync();
 }
 document.getElementById('hubAddBlock').addEventListener('click',()=>{
   if(blocks.length>=40){alert('Máximo 40 bloques.');return;}
   blocks.push({type:document.getElementById('hubAddType').value,title:'',body:'',image:'',link:''});render();
 });
 form.addEventListener('submit',()=>sync());
 render();
})();
