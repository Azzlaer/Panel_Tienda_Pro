<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin();

$adminPage='content';
$adminTitle='Moderación de comunidad';
$adminSubtitle='Administra publicaciones y comentarios del muro desde una sola pantalla';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $type=($_POST['type']??'')==='comment'?'comment':'post';
    $table=$type==='comment'?'comments':'posts';
    $id=(int)($_POST['id']??0);
    $status=($_POST['status']??'')==='hidden'?'hidden':'published';
    if($id>0){
        db()->prepare("UPDATE {$table} SET status=? WHERE id=?")->execute([$status,$id]);
        audit_log('community.'.$type.'.moderated',$type,$id,['status'=>$status]);
        flash('success',ucfirst($type==='post'?'Publicación':'Comentario').' actualizado correctamente.');
    }
    redirect('../admin/content.php');
}

$filter=in_array($_GET['status']??'', ['published','hidden'],true)?$_GET['status']:'';
$q=trim((string)($_GET['q']??''));
$page=max(1,(int)($_GET['page']??1));
$perPage=25;
$where=[];$args=[];
if($filter!==''){$where[]='p.status=?';$args[]=$filter;}
if($q!==''){$where[]='(p.title LIKE ? OR p.body LIKE ? OR u.username LIKE ? OR pr.display_name LIKE ?)';$like='%'.$q.'%';array_push($args,$like,$like,$like,$like);}
$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
$count=db()->prepare("SELECT COUNT(*) FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN profiles pr ON pr.user_id=u.id{$whereSql}");$count->execute($args);$total=(int)$count->fetchColumn();
$pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
$st=db()->prepare("SELECT p.*,u.username,pr.display_name,(SELECT COUNT(*) FROM comments c WHERE c.post_id=p.id) comment_count FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN profiles pr ON pr.user_id=u.id{$whereSql} ORDER BY p.created_at DESC LIMIT {$perPage} OFFSET {$offset}");$st->execute($args);$posts=$st->fetchAll();

$comments=db()->query("SELECT c.*,u.username,pr.display_name,p.title post_title FROM comments c JOIN users u ON u.id=c.user_id LEFT JOIN profiles pr ON pr.user_id=u.id JOIN posts p ON p.id=c.post_id ORDER BY c.created_at DESC LIMIT 12")->fetchAll();
require __DIR__.'/partials/header.php';
?>
<section class="panel-card mt-0">
 <div class="panel-head"><div><h2>🧑‍🤝‍🧑 Publicaciones del muro</h2><p><?=$total?> resultados · <?=$page?> de <?=$pages?> páginas</p></div></div>
 <form method="get" class="admin-filterbar admin-filterbar-pro">
  <input class="admin-search" name="q" value="<?=e($q)?>" placeholder="Buscar texto, vendedor o publicación...">
  <select name="status"><option value="">Todos los estados</option><option value="published" <?=$filter==='published'?'selected':''?>>Publicadas</option><option value="hidden" <?=$filter==='hidden'?'selected':''?>>Ocultas</option></select>
  <button class="admin-btn">🔎 Filtrar</button><a class="admin-btn secondary" href="content.php">Limpiar</a>
 </form>
 <div class="admin-table-wrap mt-20"><table class="admin-table"><thead><tr><th>Publicación</th><th>Autor</th><th>Comentarios</th><th>Estado</th><th>Moderación</th></tr></thead><tbody>
 <?php foreach($posts as $p):?><tr><td><b><?=e($p['title']?:'Publicación #'.$p['id'])?></b><small><?=e(mb_strimwidth(strip_tags($p['body']),0,135,'…'))?></small><small><?=e(date('d/m/Y H:i',strtotime($p['created_at'])))?></small></td><td><?=e($p['display_name']?:$p['username'])?><small>@<?=e($p['username'])?></small></td><td><b><?=number_format((int)$p['comment_count'])?></b></td><td><span class="status <?=e($p['status'])?>"><?=e($p['status'])?></span></td><td><form method="post" class="inline-admin-form"><?=csrf_field()?><input type="hidden" name="type" value="post"><input type="hidden" name="id" value="<?=$p['id']?>"><select name="status"><option value="published" <?=$p['status']==='published'?'selected':''?>>published</option><option value="hidden" <?=$p['status']==='hidden'?'selected':''?>>hidden</option></select><button class="admin-btn small">Guardar</button></form></td></tr><?php endforeach;?>
 <?php if(!$posts):?><tr><td colspan="5"><div class="empty-admin">No se encontraron publicaciones con esos filtros.</div></td></tr><?php endif;?>
 </tbody></table></div>
 <?=admin_pagination($page,$pages,$_GET)?>
</section>

<section class="panel-card"><div class="panel-head"><div><h2>💬 Comentarios recientes</h2><p>Últimos comentarios publicados en la comunidad</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Comentario</th><th>Autor</th><th>Publicación</th><th>Estado</th><th>Moderación</th></tr></thead><tbody><?php foreach($comments as $c):?><tr><td><b>#<?=$c['id']?></b><small><?=e(mb_strimwidth(strip_tags($c['body']),0,145,'…'))?></small></td><td><?=e($c['display_name']?:$c['username'])?></td><td><?=e($c['post_title']?:'Publicación #'.$c['post_id'])?></td><td><span class="status <?=e($c['status'])?>"><?=e($c['status'])?></span></td><td><form method="post" class="inline-admin-form"><?=csrf_field()?><input type="hidden" name="type" value="comment"><input type="hidden" name="id" value="<?=$c['id']?>"><select name="status"><option value="published" <?=$c['status']==='published'?'selected':''?>>published</option><option value="hidden" <?=$c['status']==='hidden'?'selected':''?>>hidden</option></select><button class="admin-btn small">Guardar</button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/partials/footer.php';?>
