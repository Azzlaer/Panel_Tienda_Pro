<?php
require dirname(__DIR__).'/config/bootstrap.php';require_admin();
$templates=[];
foreach(glob(BASE_PATH.'/templates/*',GLOB_ONLYDIR)?:[] as $dir){
 $slug=basename($dir);
 if(!preg_match('/^[a-z0-9_-]+$/',$slug)||!is_file($dir.'/index.php'))continue;
 $info=json_decode(@file_get_contents($dir.'/config.json'),true)?:[];
 $templates[$slug]=['name'=>$info['name']??ucfirst($slug),'description'=>$info['description']??''];
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$slug=(string)($_POST['template']??'');
 if(isset($templates[$slug])){
  $file=BASE_PATH.'/config/site.json';
  $payload=json_encode(['template'=>$slug],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  if(file_put_contents($file,$payload,LOCK_EX)!==false){flash('Template activado: '.$slug);}else{flash('No se pudo guardar. Revisa permisos de config/site.json.');}
 }else{flash('Template no válido.');}
 redirect('admin/configuracion.php');
}
require __DIR__.'/_top.php';?>
<div class="panel"><h2>🎨 Apariencia del sitio</h2><p class="muted">Template activo: <strong><?=e(TEMPLATE_ACTIVO)?></strong></p><div class="admin-links">
<?php foreach($templates as $slug=>$tpl):?><div class="panel" style="margin:0"><h3><?=e($tpl['name'])?></h3><p class="muted"><?=e($tpl['description'])?></p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="template" value="<?=e($slug)?>"><button class="btn primary" <?=$slug===TEMPLATE_ACTIVO?'disabled':''?>><?=$slug===TEMPLATE_ACTIVO?'✓ Activo':'Activar template'?></button></form></div><?php endforeach;?></div></div>
<?php require __DIR__.'/_bottom.php';?>
