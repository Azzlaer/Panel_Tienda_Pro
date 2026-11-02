<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin();ensure_commerce_map_schema();
$adminPage='map';$adminTitle='Mapa de locales';$adminSubtitle='Sube un plano y ubica tus comercios con marcadores interactivos';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=(string)($_POST['action']??'');
    if($action==='settings') {
        $title=trim((string)($_POST['title']??''));$description=trim((string)($_POST['description']??''));
        if(mb_strlen($title)>180||mb_strlen($description)>500)throw new RuntimeException('Texto demasiado largo.');
        $img=upload_image('image','commerce_map');
        if($img){db()->prepare('UPDATE commerce_map_settings SET image=?,title=?,description=?,updated_at=NOW() WHERE id=1')->execute([$img,$title?:'Encuentra tu local',$description]);}
        else{db()->prepare('UPDATE commerce_map_settings SET title=?,description=?,updated_at=NOW() WHERE id=1')->execute([$title?:'Encuentra tu local',$description]);}
        flash('success','Configuración del plano guardada.');
    } elseif($action==='pin') {
        $id=(int)($_POST['commerce_id']??0);
        $st=db()->prepare('SELECT id FROM commerce_directory WHERE id=?');$st->execute([$id]);
        if(!$st->fetchColumn())throw new RuntimeException('Comercio no válido.');
        $x=filter_var($_POST['x']??null,FILTER_VALIDATE_FLOAT);
        $y=filter_var($_POST['y']??null,FILTER_VALIDATE_FLOAT);
        if($x===false||$y===false||$x<0||$x>100||$y<0||$y>100)throw new RuntimeException('Posición no válida.');
        db()->prepare('INSERT INTO commerce_map_pins(commerce_id,x_percent,y_percent) VALUES(?,?,?) ON DUPLICATE KEY UPDATE x_percent=VALUES(x_percent),y_percent=VALUES(y_percent),updated_at=NOW()')->execute([$id,round($x,3),round($y,3)]);
        flash('success','Marcador actualizado.');
    } elseif($action==='remove') {
        $id=(int)($_POST['commerce_id']??0);
        db()->prepare('DELETE FROM commerce_map_pins WHERE commerce_id=?')->execute([$id]);
        flash('success','Marcador eliminado.');
    } else {throw new RuntimeException('Acción desconocida.');}
    redirect('map.php');
}
$settings=db()->query('SELECT * FROM commerce_map_settings WHERE id=1')->fetch();
$rows=db()->query("SELECT d.id,d.name,d.local_number,d.active,p.x_percent,p.y_percent FROM commerce_directory d LEFT JOIN commerce_map_pins p ON p.commerce_id=d.id ORDER BY d.name")->fetchAll();
require __DIR__.'/partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-map.css')?>?v=5.1">
<section class="panel-card"><div class="panel-head"><div><h2>Plano del recinto</h2><p>Sube una imagen horizontal JPG, PNG o WEBP. Mantén una proporción coherente con el plano original.</p></div><a class="admin-btn secondary" href="<?=url('map.php')?>" target="_blank" rel="noopener">Ver mapa público</a></div>
<form class="admin-form" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="settings"><label>Título<input name="title" maxlength="180" value="<?=e($settings['title']??'')?>"></label><label>Descripción<input name="description" maxlength="500" value="<?=e($settings['description']??'')?>"></label><label>Imagen del plano<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label><button class="admin-btn mt-20">Guardar plano</button></form></section>
<section class="panel-card"><div class="panel-head"><div><h2>Ubicar comercio</h2><p>Selecciona un local y haz clic en su posición sobre el plano, luego guarda.</p></div></div>
<?php if(empty($settings['image'])):?><p>Primero sube una imagen del plano para activar el editor.</p><?php else:?>
<form method="post" class="admin-form" id="mapPinForm"><?=csrf_field()?><input type="hidden" name="action" value="pin"><label>Comercio<select name="commerce_id" required id="mapCommerceSelect"><option value="">Selecciona un comercio</option><?php foreach($rows as $r):?><option value="<?=(int)$r['id']?>" data-x="<?=e($r['x_percent']===null?'':(string)$r['x_percent'])?>" data-y="<?=e($r['y_percent']===null?'':(string)$r['y_percent'])?>"><?=e($r['name'].' · Local '.$r['local_number'])?></option><?php endforeach;?></select></label>
<div class="commerce-map-surface editor" id="mapEditor" aria-label="Plano editable"><img src="<?=url($settings['image'])?>" alt="Plano del recinto" draggable="false"><div id="mapEditorMarker" class="map-pin map-editor-marker" hidden>📍</div></div>
<div class="map-editor-coords"><label>Horizontal (%)<input type="number" name="x" id="mapX" min="0" max="100" step="0.001" required></label><label>Vertical (%)<input type="number" name="y" id="mapY" min="0" max="100" step="0.001" required></label></div><button class="admin-btn mt-20">Guardar ubicación</button></form>
<?php endif;?></section>
<section class="panel-card"><div class="panel-head"><div><h2>Marcadores configurados</h2><p><?=count($rows)?> locales registrados</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Comercio</th><th>Posición</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['name'])?></td><td><?= $r['x_percent']===null?'Sin ubicación':e($r['x_percent'].'% / '.$r['y_percent'].'%') ?></td><td><?= $r['active']?'Visible':'Oculto' ?></td><td><?php if($r['x_percent']!==null):?><form method="post" onsubmit="return confirm('¿Quitar este marcador?')"><?=csrf_field()?><input type="hidden" name="action" value="remove"><input type="hidden" name="commerce_id" value="<?=(int)$r['id']?>"><button class="admin-btn small danger">Quitar marcador</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section>
<script src="<?=url('assets/js/commerce-map-editor.js')?>?v=5.1" defer></script>
<?php require __DIR__.'/partials/footer.php';
