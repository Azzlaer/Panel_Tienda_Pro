<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/public_menu.php';
$adminPage='public_menu';$adminTitle='Administrador de menús';$adminSubtitle='Personaliza los enlaces del menú público sin modificar código';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 verify_csrf();
 try {
  $action=(string)($_POST['action']??'');
  $items=public_menu_items();
  if($action==='reset'){
   public_menu_save(db(),public_menu_defaults());
  }elseif($action==='add'||$action==='update'){
   $raw=[
    'label'=>$_POST['label']??'','href'=>$_POST['href']??'','group'=>$_POST['group']??'',
    'audience'=>$_POST['audience']??'all','gate'=>$_POST['gate']??'always',
    'enabled'=>isset($_POST['enabled']),'highlight'=>isset($_POST['highlight'])
   ];
   $row=public_menu_validate_item($raw);
   if($action==='add')$items[]=$row;
   else {
    $idx=filter_var($_POST['index']??null,FILTER_VALIDATE_INT);
    if($idx===false||$idx===null||!isset($items[$idx]))throw new InvalidArgumentException('Enlace inexistente.');
    // Gate is preserved on edits: an ordinary editor cannot bypass module availability accidentally.
    $row['gate']=$items[$idx]['gate'];
    $items[$idx]=$row;
   }
   public_menu_save(db(),$items);
  }elseif(in_array($action,['up','down','delete'],true)){
   $idx=filter_var($_POST['index']??null,FILTER_VALIDATE_INT);
   if($idx===false||$idx===null||!isset($items[$idx]))throw new InvalidArgumentException('Enlace inexistente.');
   if($action==='delete')array_splice($items,$idx,1);
   else {
    $next=$idx+($action==='up'?-1:1);
    if(isset($items[$next]))[$items[$idx],$items[$next]]=[$items[$next],$items[$idx]];
   }
   public_menu_save(db(),$items);
  }else throw new InvalidArgumentException('Acción desconocida.');
  audit_log('public_menu.updated','settings','public_menu_config_v1',['action'=>$action]);
  flash('success','Menú público actualizado correctamente.');
 }catch(InvalidArgumentException $e){flash('error',$e->getMessage());}
  catch(Throwable $e){error_log('Public menu manager: '.$e->getMessage());flash('error','No se pudo guardar el menú. Revisa los registros del servidor.');}
 redirect('../admin/public_menu.php');
}
$rows=public_menu_items();
require __DIR__.'/partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/public-menu-admin.css')?>">
<section class="panel-card">
 <div class="panel-head"><div><h2>🧭 Menú público de Calle Cristal</h2><p>Reordena enlaces, cambia títulos, oculta opciones o agrúpalas en secciones desplegables. El menú clásico se conserva si dejas «Sección» vacía.</p></div><a class="admin-btn secondary" href="<?=url('index.php')?>" target="_blank" rel="noopener">Ver sitio público ↗</a></div>
 <p>Las rutas se limitan a páginas internas del sitio para evitar redirecciones inseguras. Las condiciones de Noticias, Contenido y Comunidad respetan su configuración actual.</p>
</section>
<section class="panel-card">
 <h2>Enlaces actuales (<?=count($rows)?>)</h2>
 <div class="pm-admin-list">
 <?php foreach($rows as $i=>$item):?>
  <div class="pm-admin-item">
   <form method="post" class="pm-admin-edit"><?=csrf_field()?>
    <input type="hidden" name="action" value="update"><input type="hidden" name="index" value="<?=$i?>">
    <label>Título<input name="label" maxlength="70" required value="<?=e($item['label'])?>"></label>
    <label>Destino<input name="href" maxlength="300" required value="<?=e($item['href'])?>"></label>
    <label>Sección / desplegable<input name="group" maxlength="40" value="<?=e($item['group'])?>" placeholder="Sin sección (menú clásico)"></label>
    <label>Visible para<select name="audience"><option value="all" <?=$item['audience']==='all'?'selected':''?>>Todos</option><option value="member" <?=$item['audience']==='member'?'selected':''?>>Usuarios registrados</option><option value="guest" <?=$item['audience']==='guest'?'selected':''?>>Visitantes</option></select></label>
    <label class="pm-toggle"><input type="checkbox" name="enabled" value="1" <?=$item['enabled']?'checked':''?>> Activo</label>
    <label class="pm-toggle"><input type="checkbox" name="highlight" value="1" <?=$item['highlight']?'checked':''?>> Destacar</label>
    <button class="admin-btn small" type="submit">Guardar</button>
   </form>
   <div class="pm-admin-actions">
    <span>#<?=$i+1?><?php if($item['gate']!=='always'):?> · <?=e($item['gate'])?><?php endif;?></span>
    <?php foreach(['up'=>'↑ Subir','down'=>'↓ Bajar','delete'=>'Eliminar'] as $action=>$label):?>
    <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="<?=$action?>"><input type="hidden" name="index" value="<?=$i?>">
     <button type="submit" class="admin-btn small secondary" <?=$action==='up'&&$i===0||$action==='down'&&$i===count($rows)-1?'disabled':''?> <?=$action==='delete'?'data-confirm-delete':''?>><?=$label?></button>
    </form>
    <?php endforeach;?>
   </div>
  </div>
 <?php endforeach;?>
 </div>
</section>
<div class="admin-grid cols-2">
<section class="panel-card">
 <h2>➕ Añadir enlace</h2>
 <form method="post" class="admin-form admin-form-stack pm-create"><?=csrf_field()?>
 <input type="hidden" name="action" value="add">
 <label>Nombre visible<input name="label" maxlength="70" placeholder="Ej.: Ofertas especiales" required></label>
 <label>Ruta interna<input name="href" maxlength="300" placeholder="offers.php" required></label>
 <label>Sección (opcional)<input name="group" maxlength="40" placeholder="Ej.: Comprar"></label>
 <label>Visible para<select name="audience"><option value="all">Todos</option><option value="member">Usuarios registrados</option><option value="guest">Visitantes</option></select></label>
 <label class="pm-toggle"><input type="checkbox" name="enabled" value="1" checked> Mostrar enlace</label>
 <label class="pm-toggle"><input type="checkbox" name="highlight" value="1"> Destacar botón</label>
 <button class="admin-btn">Añadir al menú</button>
 </form>
</section>
<section class="panel-card">
 <h2>↩ Restauración</h2>
 <p>Restablece la navegación clásica original de Calle Cristal. Tus productos, usuarios y páginas no se modificarán.</p>
 <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="reset"><button class="admin-btn secondary" type="submit" data-confirm-reset>Restaurar menú predeterminado</button></form>
 <p><small>Consejo: deja vacía la sección para mantener los enlaces horizontales como antes. Si escribes una sección, sus enlaces se agruparán en un desplegable.</small></p>
</section>
</div>
<script src="<?=url('assets/js/public-menu-admin.js')?>" defer></script>
<?php require __DIR__.'/partials/footer.php';?>
