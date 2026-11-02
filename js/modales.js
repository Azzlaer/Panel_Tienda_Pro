document.addEventListener('click',e=>{
 const trigger=e.target.closest('[data-modal]');if(trigger){const modal=document.getElementById(trigger.dataset.modal);if(modal){modal.classList.add('open');modal.setAttribute('aria-hidden','false');}}
 if(e.target.matches('[data-close]')||e.target.classList.contains('modal')){const modal=e.target.closest('.modal');if(modal){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');}}
});
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal.open').forEach(m=>{m.classList.remove('open');m.setAttribute('aria-hidden','true');});});
