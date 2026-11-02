<?php
/** Theme Studio PRO: isolated per-template configuration stored in existing settings table. */
function studio_defaults(): array {
 return ['primary'=>'#2563eb','accent'=>'#0d9488','surface'=>'#ffffff','ink'=>'#142235','font'=>'system','radius'=>14,'content_width'=>1240,'card_columns'=>4,'header_style'=>'standard','hero_height'=>370,'hide'=>[],'order'=>['hero','categories','catalog','promo','featured','commerces','news','community']];
}
function studio_sections(): array { return ['hero'=>'Portada y slider','categories'=>'Categorías','catalog'=>'Catálogo','promo'=>'Promoción secundaria','featured'=>'Productos populares','commerces'=>'Comercios','news'=>'Noticias','community'=>'Comunidad']; }
function studio_palette(string $value): string { return preg_match('/^#[0-9a-fA-F]{6}$/D',$value) ? strtolower($value) : '#2563eb'; }
function studio_normalize(array $input): array {
 $d=studio_defaults(); $out=$d;
 foreach(['primary','accent','surface','ink'] as $key) $out[$key]=studio_palette((string)($input[$key]??$d[$key]));
 $out['font']=in_array(($input['font']??''),['system','modern','editorial','rounded'],true)?$input['font']:$d['font'];
 $out['header_style']=in_array(($input['header_style']??''),['standard','compact','centered'],true)?$input['header_style']:'standard';
 foreach(['radius'=>[0,32],'content_width'=>[980,1540],'card_columns'=>[2,5],'hero_height'=>[230,650]] as $key=>$limits) $out[$key]=max($limits[0],min($limits[1],(int)($input[$key]??$d[$key])));
 $sections=array_keys(studio_sections());
 $hide=$input['hide']??[]; $out['hide']=array_values(array_intersect($sections,is_array($hide)?$hide:[]));
 $order=$input['order']??[]; if(!is_array($order))$order=[];
 $out['order']=array_values(array_unique(array_merge(array_values(array_intersect($order,$sections)),$sections)));
 return $out;
}
function studio_config(?string $slug=null): array {
 $slug=$slug??active_template_slug();
 $raw=setting('studio_theme_'.preg_replace('/[^a-z0-9_-]/','',$slug));
 $decoded=json_decode($raw,true);
 return studio_normalize(is_array($decoded)?$decoded:[]);
}
function studio_css(array $c): string {
 $f=['system'=>'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif','modern'=>'Inter,Arial,Helvetica,sans-serif','editorial'=>'Georgia,"Times New Roman",serif','rounded'=>'Trebuchet MS,Verdana,sans-serif'][$c['font']];
 $css='body.enterprise-public{--studio-primary:'.$c['primary'].';--studio-accent:'.$c['accent'].';--studio-surface:'.$c['surface'].';--studio-ink:'.$c['ink'].';--primary:'.$c['primary'].';--secondary:'.$c['accent'].';--button-radius:'.$c['radius'].'px;font-family:'.$f.';color:'.$c['ink'].';}';
 $css.='body.enterprise-public .urban-layout{max-width:'.$c['content_width'].'px;width:calc(100% - 32px);margin-left:auto;margin-right:auto;display:flex;flex-direction:column;}';
 $css.='body.enterprise-public .urban-product-grid{grid-template-columns:repeat('.$c['card_columns'].',minmax(0,1fr));}';
 $css.='body.enterprise-public .urban-product-card,body.enterprise-public .directory-card,body.enterprise-public .news-home-card{border-radius:'.$c['radius'].'px;overflow:hidden;}';
 $css.='body.enterprise-public .urban-hero,body.enterprise-public .cs-slider{min-height:'.$c['hero_height'].'px;}';
 $css.='body.enterprise-public .urban-btn,body.enterprise-public .urban-search button{background:'.$c['primary'].';border-radius:'.$c['radius'].'px;}';
 $css.='body.enterprise-public .urban-section-title span{color:'.$c['accent'].';}';
 if($c['header_style']==='compact') $css.='body.enterprise-public .urban-header{padding-top:9px;padding-bottom:9px;}body.enterprise-public .urban-contactbar{display:none;}';
 if($c['header_style']==='centered') $css.='body.enterprise-public .urban-header{justify-content:center;text-align:center;}body.enterprise-public .urban-brand{margin-inline:auto;}';
 foreach($c['order'] as $i=>$id) $css.='body.enterprise-public .urban-layout>[data-studio-section="'.$id.'"]{order:'.($i+1).';}';
 foreach($c['hide'] as $id) $css.='body.enterprise-public .urban-layout>[data-studio-section="'.$id.'"]{display:none!important;}';
 $css.='@media(max-width:1100px){body.enterprise-public .urban-product-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}@media(max-width:720px){body.enterprise-public .urban-layout{width:calc(100% - 22px)}body.enterprise-public .urban-product-grid{grid-template-columns:repeat(2,minmax(0,1fr));}body.enterprise-public .urban-hero,body.enterprise-public .cs-slider{min-height:260px;}}@media(max-width:420px){body.enterprise-public .urban-product-grid{grid-template-columns:1fr;}}';
 return $css;
}
