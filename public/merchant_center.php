<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();
require_once __DIR__.'/../app/merchant_center.php';
require_once __DIR__.'/../app/pos.php';require_once __DIR__.'/../app/orders_pro.php';
$uid=(int)user()['id'];$pdo=db();
$ready=merchant_ready($pdo);
$owner=$uid;$role='owner';
if($ready){try{$ctx=merchant_context($pdo,$uid,$_GET['owner']??$uid);$owner=$ctx['owner'];$role=$ctx['role'];}catch(Throwable){http_response_code(403);exit('Comercio no autorizado.');}}
$prefix='?owner='.$owner;
$metrics=['products'=>0,'views'=>0,'low_stock'=>0,'sales'=>null,'revenue'=>null,'orders'=>null,'contacts'=>null,'tasks'=>null];
$q=$pdo->prepare("SELECT COUNT(*) products,COALESCE(SUM(views),0) views,COALESCE(SUM(CASE WHEN stock IS NOT NULL AND stock<=3 AND status='published' THEN 1 ELSE 0 END),0) low_stock FROM products WHERE user_id=?");
$q->execute([$owner]);$metrics=array_merge($metrics,$q->fetch());
if(pos_ready($pdo)){$q=$pdo->prepare('SELECT COUNT(*) sales,COALESCE(SUM(total_clp),0) revenue FROM pos_sales WHERE seller_id=?');$q->execute([$owner]);$metrics=array_merge($metrics,$q->fetch());}
if(orders_pro_ready($pdo)){$q=$pdo->prepare('SELECT COUNT(*) FROM pro_orders WHERE seller_id=?');$q->execute([$owner]);$metrics['orders']=(int)$q->fetchColumn();}
if($ready){
 $q=$pdo->prepare('SELECT COUNT(*) FROM merchant_contacts WHERE owner_id=?');$q->execute([$owner]);$metrics['contacts']=(int)$q->fetchColumn();
 $q=$pdo->prepare("SELECT COUNT(*) FROM merchant_tasks WHERE owner_id=? AND status<>'done'");$q->execute([$owner]);$metrics['tasks']=(int)$q->fetchColumn();
}
$q=$pdo->prepare("SELECT id,title,status,views,stock,price_clp FROM products WHERE user_id=? ORDER BY created_at DESC LIMIT 8");$q->execute([$owner]);$products=$q->fetchAll();
$staffShops=[];
if($ready){$q=$pdo->prepare("SELECT s.owner_id,p.display_name,s.role FROM merchant_staff s JOIN profiles p ON p.user_id=s.owner_id WHERE s.member_id=? AND s.active=1 ORDER BY p.display_name");$q->execute([$uid]);$staffShops=$q->fetchAll();}
$pageTitle='Merchant Center PRO';require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/merchant-center.css')?>">
<section class="merchant">
<header class="merchant-hero"><span>COMERCIOSOCIAL · MERCHANT CENTER PRO</span><h1>Tu centro de negocios</h1><p>Resumen comercial, contactos, actividades y colaboradores, sin duplicar las ventas ni productos existentes.</p><div><a href="<?=url('profile_edit.php')?>">Mi perfil comercial</a><a href="<?=url('inventory.php')?>">Inventario propio</a></div></header>
<?php if(!$ready):?><div class="merchant-alert">El módulo CRM y empleados todavía no está activado. Un administrador debe instalarlo desde Integration Center. Las métricas del catálogo siguen disponibles.</div><?php endif;?>
<?php if($staffShops):?><form class="merchant-switch" method="get"><label>Comercio al que colaboras <select name="owner"><option value="<?=$uid?>">Mi cuenta</option><?php foreach($staffShops as $shop):?><option value="<?=(int)$shop['owner_id']?>" <?=$owner===(int)$shop['owner_id']?'selected':''?>><?=e($shop['display_name'])?> · <?=e($shop['role'])?></option><?php endforeach;?></select></label><button>Ver comercio</button></form><?php endif;?>
<?php if($ready):?><div class="merchant-links"><a href="<?=url('merchant_crm.php'.$prefix)?>">👥 Contactos y CRM</a><a href="<?=url('merchant_tasks.php'.$prefix)?>">📋 Tareas y seguimiento</a><?php if($role==='owner'):?><a href="<?=url('merchant_team.php')?>">🛡️ Equipo y permisos</a><?php endif;?></div><?php endif;?>
<div class="merchant-metrics">
<?php foreach([['Publicaciones',$metrics['products']],['Visualizaciones',$metrics['views']],['Stock bajo (≤3)',$metrics['low_stock']],['Ventas POS',$metrics['sales']??'No activado'],['CLP POS',$metrics['revenue']===null?'No activado':'$'.number_format((int)$metrics['revenue'],0,',','.')],['Pedidos',$metrics['orders']??'No activado'],['Contactos CRM',$metrics['contacts']??'No activado'],['Tareas pendientes',$metrics['tasks']??'No activado']] as [$label,$value]):?><div class="merchant-metric"><strong><?=e((string)$value)?></strong><span><?=e($label)?></span></div><?php endforeach;?>
</div><div class="merchant-panel"><h2>Últimos productos del comercio</h2><div class="merchant-table"><table><thead><tr><th>Producto</th><th>Estado</th><th>Vistas</th><th>Stock</th><th>Precio CLP</th></tr></thead><tbody><?php foreach($products as $p):?><tr><td><?=e($p['title'])?></td><td><?=e($p['status'])?></td><td><?=(int)$p['views']?></td><td><?=$p['stock']===null?'No especificado':(int)$p['stock']?></td><td><?=number_format((int)$p['price_clp'],0,',','.')?></td></tr><?php endforeach;?></tbody></table></div></div>
<p class="merchant-note">Los importes POS son ventas presenciales registradas manualmente, no ingresos bancarios verificados. Los pedidos se contabilizan por separado para evitar doble conteo.</p>
</section>
<?php require __DIR__.'/../partials/footer.php';
