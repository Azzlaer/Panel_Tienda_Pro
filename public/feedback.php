<?php
require_once __DIR__.'/../app/bootstrap.php';
require_login(); ensure_feedback_schema();
$me=user();
if(($me['status']??'')!=='active'){http_response_code(403);exit('Tu cuenta debe estar activa.');}
$isAdmin=($me['role']??'')==='admin';

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='create' && !$isAdmin){
            $subject=trim((string)($_POST['subject']??''));
            $body=trim((string)($_POST['body']??''));
            $category=(string)($_POST['category']??'suggestion');
            $priority=(string)($_POST['priority']??'normal');
            $rating=(int)($_POST['seller_rating']??0);
            if(mb_strlen($subject)<6||mb_strlen($subject)>180)throw new RuntimeException('El asunto debe tener entre 6 y 180 caracteres.');
            if(mb_strlen($body)<15||mb_strlen($body)>5000)throw new RuntimeException('El mensaje debe tener entre 15 y 5.000 caracteres.');
            if(!in_array($category,['suggestion','problem','design','function','support','other'],true))$category='suggestion';
            if(!in_array($priority,['low','normal','high'],true))$priority='normal';
            if($rating<1||$rating>5)throw new RuntimeException('Selecciona una calificación de 1 a 5 estrellas para la administración.');
            db()->beginTransaction();
            $q=db()->prepare('INSERT INTO feedback_threads(seller_id,subject,category,priority,seller_rating) VALUES(?,?,?,?,?)');
            $q->execute([(int)$me['id'],$subject,$category,$priority,$rating]);
            $id=(int)db()->lastInsertId();
            db()->prepare('INSERT INTO feedback_messages(thread_id,user_id,body) VALUES(?,?,?)')->execute([$id,(int)$me['id'],$body]);
            db()->commit();
            flash('success','Tu feedback fue enviado a la administración.');
            redirect('feedback.php?id='.$id);
        }
        if($action==='reply'){
            $id=(int)($_POST['id']??0); $body=trim((string)($_POST['body']??''));
            if(mb_strlen($body)<2||mb_strlen($body)>5000)throw new RuntimeException('La respuesta debe tener entre 2 y 5.000 caracteres.');
            $q=db()->prepare('SELECT * FROM feedback_threads WHERE id=?');$q->execute([$id]);$thread=$q->fetch();
            if(!$thread||(!$isAdmin&&(int)$thread['seller_id']!==(int)$me['id']))throw new RuntimeException('Feedback no encontrado.');
            if($thread['status']==='closed'&&!$isAdmin)throw new RuntimeException('Este feedback está cerrado.');
            db()->prepare('INSERT INTO feedback_messages(thread_id,user_id,body) VALUES(?,?,?)')->execute([$id,(int)$me['id'],$body]);
            if($isAdmin) db()->prepare("UPDATE feedback_threads SET status='answered',seller_read=0,admin_read=1,assigned_admin_id=?,updated_at=NOW() WHERE id=?")->execute([(int)$me['id'],$id]);
            else db()->prepare("UPDATE feedback_threads SET status='open',admin_read=0,seller_read=1,updated_at=NOW() WHERE id=?")->execute([$id]);
            flash('success','Respuesta enviada.');redirect('feedback.php?id='.$id);
        }
        if($action==='seller_rate'&&!$isAdmin){
            $id=(int)($_POST['id']??0);$rating=(int)($_POST['seller_rating']??0);
            if($rating<1||$rating>5)throw new RuntimeException('Calificación inválida.');
            $q=db()->prepare('UPDATE feedback_threads SET seller_rating=?,updated_at=NOW() WHERE id=? AND seller_id=?');$q->execute([$rating,$id,(int)$me['id']]);
            flash('success','Calificación actualizada.');redirect('feedback.php?id='.$id);
        }
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());redirect('feedback.php'.(!empty($_POST['id'])?'?id='.(int)$_POST['id']:''));}
}

$id=(int)($_GET['id']??0);$thread=null;$messages=[];
if($id){
    $q=db()->prepare('SELECT ft.*,u.username,p.display_name,p.avatar,au.username admin_username,ap.display_name admin_name FROM feedback_threads ft JOIN users u ON u.id=ft.seller_id LEFT JOIN profiles p ON p.user_id=u.id LEFT JOIN users au ON au.id=ft.assigned_admin_id LEFT JOIN profiles ap ON ap.user_id=au.id WHERE ft.id=?');$q->execute([$id]);$thread=$q->fetch();
    if(!$thread||(!$isAdmin&&(int)$thread['seller_id']!==(int)$me['id'])){http_response_code(404);exit('Feedback no encontrado.');}
    db()->prepare('UPDATE feedback_threads SET '.($isAdmin?'admin_read=1':'seller_read=1').' WHERE id=?')->execute([$id]);
    $q=db()->prepare('SELECT fm.*,u.role,u.username,p.display_name,p.avatar FROM feedback_messages fm JOIN users u ON u.id=fm.user_id LEFT JOIN profiles p ON p.user_id=u.id WHERE fm.thread_id=? ORDER BY fm.created_at');$q->execute([$id]);$messages=$q->fetchAll();
}
$q=db()->prepare('SELECT ft.*, (SELECT COUNT(*) FROM feedback_messages fm WHERE fm.thread_id=ft.id) message_count FROM feedback_threads ft WHERE ft.seller_id=? ORDER BY ft.created_at DESC');$q->execute([(int)$me['id']]);$threads=$q->fetchAll();
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/feedback.css')?>">
<section class="feedback-hero"><div><span class="eyebrow">🤝 Comunicación directa</span><h1>Feedback con la administración</h1><p>Comparte ideas, problemas y mejoras. Cada conversación queda registrada y puede ser respondida por el equipo administrador.</p></div><div class="feedback-hero-score"><b><?=count($threads)?></b><span>conversaciones enviadas</span></div></section>
<div class="feedback-layout">
 <aside class="feedback-sidebar">
  <?php if(!$isAdmin):?><a class="btn wide" href="feedback.php#nuevo">➕ Nuevo feedback</a><?php endif;?>
  <h3>Mis conversaciones</h3>
  <div class="feedback-thread-list"><?php if(!$threads):?><p class="empty-note">Aún no has enviado feedback.</p><?php endif;?><?php foreach($threads as $t):?><a class="<?=$id===$t['id']?'active':''?>" href="feedback.php?id=<?=$t['id']?>"><span><?=feedback_category_label($t['category'])?></span><b><?=e($t['subject'])?></b><small><?=feedback_status_label($t['status'])?> · <?=$t['message_count']?> mensajes</small><?php if(!$t['seller_read']):?><em>Nuevo</em><?php endif;?></a><?php endforeach;?></div>
  <?php if(setting('feedback_public_enabled','0')==='1'):?><a class="feedback-public-link" href="feedback_public.php">🌐 Ver ideas públicas de vendedores</a><?php endif;?>
 </aside>
 <section class="feedback-main">
 <?php if($thread):?>
  <article class="feedback-conversation">
   <header><div><span><?=feedback_category_label($thread['category'])?></span><h2><?=e($thread['subject'])?></h2><p>Creado el <?=date('d/m/Y H:i',strtotime($thread['created_at']))?></p></div><span class="feedback-status <?=$thread['status']?>"><?=feedback_status_label($thread['status'])?></span></header>
   <div class="feedback-rating-summary"><div><small>Valoración del vendedor a la administración</small><?=feedback_stars((int)($thread['seller_rating']??0))?></div><div><small>Valoración de la administración al aporte</small><?=feedback_stars((int)($thread['admin_rating']??0))?></div><?php if($thread['is_public']):?><span class="public-badge">🌐 Público</span><?php endif;?></div>
   <div class="feedback-messages"><?php foreach($messages as $msg):$adminMsg=$msg['role']==='admin';?><article class="feedback-message <?=$adminMsg?'admin':'seller'?>"><img src="<?=url($msg['avatar']?:'assets/img-avatar.svg')?>" alt=""><div><header><b><?=e($msg['display_name']?:$msg['username'])?></b><span><?=$adminMsg?'🛡️ Administración':'🏪 Vendedor'?></span><time><?=date('d/m/Y H:i',strtotime($msg['created_at']))?></time></header><p><?=nl2br(e($msg['body']))?></p></div></article><?php endforeach;?></div>
   <?php if($thread['status']!=='closed'||$isAdmin):?><form method="post" class="feedback-reply"><?=csrf_field()?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?=$thread['id']?>"><label>Escribir respuesta<textarea name="body" rows="5" required maxlength="5000" placeholder="Escribe una respuesta clara y respetuosa..."></textarea></label><button class="btn" type="submit">📨 Enviar respuesta</button></form><?php endif;?>
   <?php if(!$isAdmin):?><form method="post" class="feedback-rate-box"><?=csrf_field()?><input type="hidden" name="action" value="seller_rate"><input type="hidden" name="id" value="<?=$thread['id']?>"><div><b>¿Cómo evalúas la atención administrativa?</b><small>Puedes actualizar tu valoración después de recibir una respuesta.</small></div><?=feedback_stars((int)($thread['seller_rating']??0),true,'seller_rating')?><button class="btn secondary" type="submit">Guardar estrellas</button></form><?php endif;?>
  </article>
 <?php elseif(!$isAdmin):?>
  <article class="feedback-new" id="nuevo"><header><span>💡</span><div><h2>Enviar una idea o comentario</h2><p>Describe qué se puede mejorar en la web o qué necesitas para trabajar mejor como vendedor.</p></div></header><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="create"><div class="feedback-form-grid"><label>Tipo<select name="category"><option value="suggestion">💡 Sugerencia</option><option value="problem">🐞 Problema</option><option value="design">🎨 Diseño</option><option value="function">⚙️ Nueva función</option><option value="support">🧭 Ayuda</option><option value="other">💬 Otro</option></select></label><label>Prioridad<select name="priority"><option value="low">Baja</option><option value="normal" selected>Normal</option><option value="high">Alta</option></select></label></div><label>Asunto<input name="subject" minlength="6" maxlength="180" required placeholder="Ej.: Mejorar el filtro por comuna"></label><label>Detalle<textarea name="body" rows="8" minlength="15" maxlength="5000" required placeholder="Explica la idea, el problema actual y cómo te gustaría que funcionara..."></textarea></label><div class="feedback-initial-rating"><div><b>Califica tu experiencia actual con la administración</b><small>1 estrella = necesita mejorar · 5 estrellas = excelente cercanía</small></div><?=feedback_stars(5,true,'seller_rating')?></div><button class="btn" type="submit">🚀 Enviar feedback</button></form></article>
 <?php endif;?>
 </section>
</div>
<?php require __DIR__.'/../partials/footer.php';?>
