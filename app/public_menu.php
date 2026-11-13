<?php
declare(strict_types=1);

/** Public menu stored in the existing settings table. No schema migration required. */
function public_menu_defaults(): array {
 $items=[
  ['Inicio','index.php','','all','always'],
  ['Explorar catálogo','index.php#catalogo','','all','always'],
  ['Novedades','index.php#catalogo','','all','always'],
  ['Noticias','news_list.php','','all','news'],
  ['Destacados','index.php?sort=popular#catalogo','','all','always'],
  ['Marketplace','marketplace.php','','all','always'],
  ['Comercios','commerces.php','','all','always'],
  ['Mi negocio PRO','merchant_center.php','','member','always'],
  ['Mapa de locales','map.php','','all','always'],
  ['Campañas','offers.php','','all','always'],
  ['Contenido','content_hub.php','','all','hub'],
  ['Comunidad','feedback_public.php','','all','community'],
  ['Donaciones','donations.php','','member','always'],
  ['+ Publicar aviso','product_create.php','','member','always'],
 ];
 return array_map(static fn(array $a): array=>[
  'label'=>$a[0],'href'=>$a[1],'group'=>$a[2],'audience'=>$a[3],
  'gate'=>$a[4],'enabled'=>true,'highlight'=>$a[0]==='+ Publicar aviso'
 ],$items);
}
function public_menu_href(string $href): string {
 $href=trim($href);
 // Internal public paths only: no external domains, traversal, control chars or encoded evasions.
 if(strlen($href)>300||!preg_match('~^[a-z][a-z0-9_]*\.php(?:\?[a-z0-9_=&%-]{1,180})?(?:\#[a-z0-9_-]{1,60})?$~iD',$href)
    ||str_contains($href,'%')||str_contains($href,'..'))throw new InvalidArgumentException('Enlace inválido. Usa una ruta interna como marketplace.php o index.php#catalogo.');
 return $href;
}
function public_menu_validate_item(array $input): array {
 $label=trim((string)($input['label']??''));
 $group=trim((string)($input['group']??''));
 if(mb_strlen($label)<1||mb_strlen($label)>70||mb_strlen($group)>40
    ||preg_match('/[\x00-\x1f\x7f]/u',$label.$group))throw new InvalidArgumentException('El título o la sección no son válidos.');
 $audience=(string)($input['audience']??'all');
 if(!in_array($audience,['all','member','guest'],true))throw new InvalidArgumentException('Público inválido.');
 $gate=(string)($input['gate']??'always');
 if(!in_array($gate,['always','news','hub','community'],true))throw new InvalidArgumentException('Condición de sección inválida.');
 return [
  'label'=>$label,'href'=>public_menu_href((string)($input['href']??'')),
  'group'=>$group,'audience'=>$audience,'gate'=>$gate,
  'enabled'=>!empty($input['enabled']),'highlight'=>!empty($input['highlight'])
 ];
}
function public_menu_normalize(array $items): array {
 if(count($items)>60)throw new InvalidArgumentException('El menú admite como máximo 60 enlaces.');
 return array_map(static function($item): array {
  if(!is_array($item))throw new InvalidArgumentException('Elemento de menú inválido.');
  return public_menu_validate_item($item);
 },array_values($items));
}
function public_menu_items(?string $stored=null): array {
 if($stored===null)$stored=setting('public_menu_config_v1','');
 if($stored==='')return public_menu_defaults();
 try {
  $decoded=json_decode($stored,true,64,JSON_THROW_ON_ERROR);
  if(!is_array($decoded)||!array_is_list($decoded))return public_menu_defaults();
  return public_menu_normalize($decoded);
 }catch(Throwable $e){error_log('Public menu settings invalid: '.$e->getMessage());return public_menu_defaults();}
}
function public_menu_save(PDO $pdo,array $items): void {
 $normalized=public_menu_normalize($items);
 $json=json_encode($normalized,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 $q=$pdo->prepare("INSERT INTO settings (\`key\`,value) VALUES ('public_menu_config_v1',?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
 $q->execute([$json]);
 if(function_exists('clear_settings_cache'))clear_settings_cache();
}
function public_menu_visible(array $items,bool $loggedIn,bool $hubEnabled): array {
 return array_values(array_filter($items,static function(array $row)use($loggedIn,$hubEnabled): bool {
  if(empty($row['enabled']))return false;
  if($row['audience']==='member'&&!$loggedIn)return false;
  if($row['audience']==='guest'&&$loggedIn)return false;
  return match($row['gate']) {
   'news'=>setting('news_enabled','1')==='1',
   'hub'=>$hubEnabled,
   'community'=>setting('feedback_public_enabled','0')==='1',
   default=>true
  };
 }));
}
function public_menu_sections(array $items): array {
 // Sections appear at the location of their first item, while ungrouped links keep their order.
 $result=[];$indices=[];
 foreach($items as $item){
  $group=$item['group'];
  if($group===''){$result[]=['title'=>'','items'=>[$item]];continue;}
  if(!isset($indices[$group])){$indices[$group]=count($result);$result[]=['title'=>$group,'items'=>[]];}
  $result[$indices[$group]]['items'][]=$item;
 }
 return $result;
}
