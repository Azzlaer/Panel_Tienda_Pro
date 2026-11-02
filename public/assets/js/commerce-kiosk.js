(function(){'use strict';
if(typeof QRCode!=='function')return;
document.querySelectorAll('[data-qr]').forEach(function(el){
 const address=el.getAttribute('data-qr');
 if(!address)return;
 new QRCode(el,{text:address,width:el.id==='qrPrint'?260:144,height:el.id==='qrPrint'?260:144,colorDark:'#0c2445',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});
});
document.querySelectorAll('.kiosk-print').forEach(function(btn){
 btn.addEventListener('click',function(){
   const card=btn.closest('.kiosk-card');const qr=card&&card.querySelector('[data-qr]');
   if(!qr)return;
   const target=new URL('commerce_qr.php',window.location.href);const profile=new URL(qr.getAttribute('data-qr'),window.location.href);
   // only use the canonical server-generated destination; no arbitrary URLs
   target.searchParams.set('id',card.dataset.id||'');
   if(!card.dataset.id){window.print();return;}
   window.open(target.href,'_blank','noopener,noreferrer');
 });
});
})();
