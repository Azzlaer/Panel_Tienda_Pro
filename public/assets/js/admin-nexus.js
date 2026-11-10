(function(){'use strict';
const field=document.getElementById('nexusNavSearch');
if(!field)return;
const groups=Array.from(document.querySelectorAll('.admin-nav-group'));
const empty=document.getElementById('nexusNoResults');
function search(){const q=field.value.toLocaleLowerCase('es').trim();let matches=0;
for(const group of groups){const links=Array.from(group.querySelectorAll('.enterprise-nav-items a'));let found=0;
for(const link of links){const label=(link.textContent||'').toLocaleLowerCase('es');const visible=!q||label.includes(q);link.hidden=!visible;if(visible){found++;matches++;}}
group.hidden=found===0;
if(q&&found){const toggle=group.querySelector('.enterprise-group-toggle');const contents=group.querySelector('.enterprise-nav-items');if(toggle)toggle.setAttribute('aria-expanded','true');if(contents)contents.hidden=false;}
}
if(empty)empty.hidden=matches!==0;
}
field.addEventListener('input',search);
field.addEventListener('keydown',e=>{if(e.key==='Escape'){field.value='';search();field.blur();}});
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();field.focus();field.select();}});
})();
