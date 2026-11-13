<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/marketplace.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);header('Allow: GET');echo '[]';exit;}
$query=$_GET['q']??'';
if(!is_string($query)||mb_strlen($query)>60){http_response_code(400);echo '[]';exit;}
try {
 echo json_encode(marketplace_suggestions(db(),$query),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){error_log('Marketplace autocomplete failed: '.get_class($e));http_response_code(503);echo '[]';}
