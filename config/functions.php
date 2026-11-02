<?php
function e($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function logged():bool{return isset($_SESSION['id_usuario']);}
function admin():bool{return logged()&&($_SESSION['rol']??'')==='admin';}
function require_admin():void{
 if(!admin()){http_response_code(403);exit('Acceso denegado.');}
}
function csrf():string{
 if(empty($_SESSION['csrf'])){$_SESSION['csrf']=bin2hex(random_bytes(32));}
 return $_SESSION['csrf'];
}
function check_csrf():void{
 if(!hash_equals(csrf(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Solicitud no válida (CSRF).');}
}
function redirect(string $to):void{header('Location: '.BASE_URL.$to);exit;}
function flash(string $message):void{$_SESSION['flash']=$message;}
function take_flash():string{$m=$_SESSION['flash']??'';unset($_SESSION['flash']);return $m;}
function image_url(?string $path):string{
 $path=trim((string)$path);
 if(preg_match('~^https://~i',$path))return $path;
 if(str_starts_with($path,'uploads/')&&!str_contains($path,'..'))return BASE_URL.$path;
 return BASE_URL.'img/placeholder.svg';
}
