<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/marketplace.php';
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Método no permitido.');}
verify_csrf();
$pdo=db();if(!marketplace_ready($pdo)){http_response_code(503);exit('Marketplace sin activar.');}
$uid=(int)user()['id'];$action=(string)($_POST['action']??'');
try {
 if($action==='add_favorite'||$action==='remove_favorite'){
  $pid=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if($pid===false)throw new InvalidArgumentException('Producto inválido.');
  marketplace_favorite($pdo,$uid,$pid,$action==='add_favorite');
  flash('success',$action==='add_favorite'?'Artículo agregado a favoritos.':'Artículo quitado de favoritos.');
  redirect('marketplace_saved.php');
 }
 if($action==='save_search'){
  $name=trim((string)($_POST['name']??''));
  if(mb_strlen($name)<3||mb_strlen($name)>100)throw new InvalidArgumentException('Nombre de búsqueda inválido.');
  $raw=(string)($_POST['filters']??'');
  if(strlen($raw)>2200)throw new InvalidArgumentException('Filtros demasiado largos.');
  $decoded=json_decode($raw,true);
  if(!is_array($decoded))throw new InvalidArgumentException('Filtros inválidos.');
  $filters=marketplace_filters($decoded);
  $json=json_encode($filters,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
  $q=$pdo->prepare('SELECT COUNT(*) FROM marketplace_saved_searches WHERE user_id=?');$q->execute([$uid]);
  if((int)$q->fetchColumn()>=30)throw new RuntimeException('Máximo 30 búsquedas guardadas.');
  $q=$pdo->prepare('INSERT INTO marketplace_saved_searches(user_id,name,filters_json) VALUES(?,?,?)');
  $q->execute([$uid,$name,$json]);flash('success','Búsqueda guardada.');redirect('marketplace_saved.php');
 }
 if($action==='delete_search'){
  $sid=filter_var($_POST['search_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if($sid===false)throw new InvalidArgumentException('Búsqueda inválida.');
  $q=$pdo->prepare('DELETE FROM marketplace_saved_searches WHERE id=? AND user_id=?');
  $q->execute([$sid,$uid]);flash('success','Búsqueda eliminada.');redirect('marketplace_saved.php');
 }
 http_response_code(400);exit('Acción desconocida.');
}catch(Throwable $e){
 if($e instanceof PDOException){error_log('Marketplace action SQL: '.$e->getMessage());flash('error','No se pudo guardar. Revisa si ya existe una búsqueda con ese nombre.');}
 else flash('error',$e->getMessage());
 redirect('marketplace_saved.php');
}
