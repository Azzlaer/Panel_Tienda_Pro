<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
require_once __DIR__.'/../app/vip_product_effects.php';
$uid=(int)user()['id'];$pdo=db();$ready=vip_effect_ready($pdo);$vip=vip_seller_active($pdo,$uid);
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  if(!$ready)throw new RuntimeException('El módulo todavía no está activado.');
  $id=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if(!$id)throw new InvalidArgumentException('Artículo inválido.');
  vip_effect_assign($pdo,$uid,(int)$id,(string)($_POST['effect_key']??'none'));
  audit_log('vip.product_effect_changed','product',(int)$id);
  flash('success','Efecto visual del artículo actualizado.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('vip_product_effects.php');
}
$q=$pdo->prepare("SELECT id,title,status FROM products WHERE user_id=? ORDER BY created_at DESC LIMIT 250");$q->execute([$uid]);$items=$q->fetchAll();
$selected=[];
if($ready){
 $q=$pdo->prepare("SELECT e.product_id,e.effect_key FROM vip_product_effects e JOIN products p ON p.id=e.product_id WHERE p.user_id=?");
 $q->execute([$uid]);
 foreach($q->fetchAll() as $r)$selected[(int)$r['product_id']]=(string)$r['effect_key'];
}
$catalog=vip_effect_catalog();$pageTitle='Efectos VIP para publicaciones';require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/vip-product-effects.css')?>">
<section class="vip-effect-panel">
<header class="vip-effect-hero"><span>COMERCIOSOCIAL · VIP NAME EFFECTS</span><h1>✨ Efectos para tus publicaciones</h1><p>Personaliza cada título de artículo con uno de los estilos exclusivos para vendedores VIP.</p></header>
<div class="vip-effect-access"><strong>Estado de membresía: <?=$vip?'VIP activo':'Sin VIP activo'?></strong>
<p><?php if(!$ready):?>El administrador debe activar «Efectos VIP» en Integration Center.<?php elseif(!$vip):?>Tu cuenta no tiene un acceso VIP activo. Puedes consultar los efectos, pero no asignarlos a tus publicaciones.<?php else:?>Elige un efecto diferente para cada artículo; solo se muestra públicamente mientras tu VIP esté vigente y el artículo esté publicado.<?php endif;?></p></div>
<div class="vip-effect-grid">
<?php foreach($items as $item):
 $id=(int)$item['id'];$current=$selected[$id]??'none';?>
<form method="post" class="vip-effect-card">
 <?=csrf_field()?>
 <input type="hidden" name="product_id" value="<?=$id?>">
 <div><small><?=e($item['status'])?> · #<?=$id?></small><h3><?=e($item['title'])?></h3></div>
 <div class="vip-effect-preview"><span class="<?=e(vip_effect_class($vip?$current:null))?>"><?=e($item['title'])?></span></div>
 <label>Efecto visual
 <select name="effect_key" data-vip-picker <?=!$vip?'disabled':''?>>
 <?php foreach($catalog as $key=>$meta):?><option value="<?=e($key)?>" <?=$current===$key?'selected':''?>><?=e($meta['name'])?></option><?php endforeach;?>
 </select></label>
 <button type="submit" <?=!$vip?'disabled':''?>>Guardar efecto</button>
 <a href="<?=url('product.php?id='.$id)?>" target="_blank" rel="noopener">Ver artículo</a>
</form>
<?php endforeach;?></div>
<?php if(!$items):?><p>Todavía no tienes artículos. <a href="<?=url('product_create.php')?>">Publica tu primer artículo</a>.</p><?php endif;?>
</section>
<script src="<?=url('assets/js/vip-product-effects.js')?>" defer></script>
<?php require __DIR__.'/../partials/footer.php';
