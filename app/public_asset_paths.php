<?php
declare(strict_types=1);
/**
 * Create path-only URLs from PHP's deployed SCRIPT_NAME rather than a stale APP_URL.
 * No trust in HTTP_HOST; useful on XAMPP subfolders and public/ document roots.
 */
function public_asset_base(string $scriptName): string {
 $script=parse_url($scriptName,PHP_URL_PATH)?:'';
 $script=str_replace('\\','/',$script);
 if(preg_match('~^(.*?)/(?:public|admin)(?:/|$)~i',$script,$m)){
  return rtrim($m[1],'/').'/public';
 }
 return ''; // VirtualHost with public/ as web root
}
function public_asset_url(string $asset,?string $scriptName=null): string {
 $asset=ltrim($asset,'/');
 if($asset===''||preg_match('~(?:^|/)\.\.(?:/|$)|[?#\\\\\x00-\x1f\x7f]~',$asset)){
  throw new InvalidArgumentException('Ruta local inválida.');
 }
 return public_asset_base($scriptName??(string)($_SERVER['SCRIPT_NAME']??'')).'/'.$asset;
}
