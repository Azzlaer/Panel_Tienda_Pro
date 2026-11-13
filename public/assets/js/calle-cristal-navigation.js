/* Calle Cristal Navigation PRO: click and keyboard navigation with native details */
(() => {
 'use strict';
 const selectors='.cc-dropdown, .cc-mobile-menu';
 const dropdowns=Array.from(document.querySelectorAll(selectors));
 dropdowns.forEach(item=>{
  item.addEventListener('toggle',()=>{
   if(!item.open)return;
   dropdowns.forEach(other=>{
    // Keep an ancestor mobile menu open while opening a nested group.
    if(other!==item&&!other.contains(item))other.open=false;
   });
  });
 });
 document.addEventListener('pointerdown',event=>{
  dropdowns.forEach(item=>{if(item.open&&!item.contains(event.target))item.open=false;});
 });
 document.addEventListener('keydown',event=>{
  if(event.key!=='Escape')return;
  const open=dropdowns.filter(item=>item.open);
  if(!open.length)return;
  event.preventDefault();
  const last=open[open.length-1];last.open=false;
  last.querySelector('summary')?.focus();
 });
 const mobile=window.matchMedia('(min-width: 761px)');
 mobile.addEventListener?.('change',()=>{
  if(mobile.matches)document.querySelectorAll('.cc-mobile-menu').forEach(item=>item.open=false);
 });
})();
