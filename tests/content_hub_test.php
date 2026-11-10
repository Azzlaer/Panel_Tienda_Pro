<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/content_hub.php';
function hub_check(bool $pass,string $message): void {
 echo ($pass?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$pass)exit(1);
}
hub_check(hub_slug('Guías para Comercios!')==='guias-para-comercios','normalized accented slug');
hub_check(hub_status('published')==='published','published status accepted');
try{hub_status('deleted');hub_check(false,'invalid status rejected');}catch(InvalidArgumentException){hub_check(true,'invalid status rejected');}
hub_check(hub_safe_image('assets/cover.svg')==='assets/cover.svg','local image accepted');
hub_check(hub_safe_image('uploads/content/photo.jpg')==='uploads/content/photo.jpg','uploaded image accepted');
hub_check(hub_safe_image('javascript:alert(1)')===null,'script image rejected');
hub_check(hub_safe_image('//evil.example/image.jpg')===null,'protocol relative URL rejected');
hub_check(hub_safe_link('javascript:alert(1)')===null,'script link rejected');
hub_check(hub_safe_link('https://example.org/store')!==null,'HTTPS link accepted');
hub_check(hub_safe_link('content_hub.php?page=1')!==null,'internal link accepted');
$blocks=[
 ['type'=>'hero','title'=>'Portada <script>alert(1)</script>','body'=>"Línea uno\nLínea dos"],
 ['type'=>'image','title'=>'Imagen editorial','image'=>'assets/cover.svg'],
 ['type'=>'cta','title'=>'Ver comercios','body'=>'Más información','link'=>'commerces.php'],
 ['type'=>'divider']
];
$clean=hub_normalize_blocks(json_encode($blocks,JSON_UNESCAPED_UNICODE));
$render=hub_render_blocks($clean,'http://localhost/ComercioSocial/public');
hub_check(str_contains($render,'&lt;script&gt;')&&!str_contains($render,'<script>'),'hero content HTML escaped');
hub_check(str_contains($render,'alt="Imagen editorial"'),'image has safe alt');
hub_check(str_contains($render,'href="http://localhost/ComercioSocial/public/commerces.php"'),'CTA safe local link resolved');
hub_check(hub_paragraphs("<img src=x onerror=alert(1)>")==='<p>&lt;img src=x onerror=alert(1)&gt;</p>','article text HTML escaped');
try{hub_normalize_blocks('[{"type":"script","body":"hello"}]');hub_check(false,'invalid block denied');}
catch(InvalidArgumentException){hub_check(true,'invalid block denied');}
try{hub_normalize_blocks('[{"type":"cta","link":"javascript:alert(1)"}]');hub_check(false,'unsafe CTA denied');}
catch(InvalidArgumentException){hub_check(true,'unsafe CTA denied');}
try{hub_normalize_blocks('[{"type":"image","image":"data:image/svg+xml;base64,PHN2Zz4="}]');hub_check(false,'data URL denied');}
catch(InvalidArgumentException){hub_check(true,'data URL denied');}
try{hub_normalize_blocks(json_encode(array_fill(0,41,['type'=>'divider'])));hub_check(false,'too many blocks denied');}
catch(InvalidArgumentException){hub_check(true,'too many blocks denied');}
$root=dirname(__DIR__);
$admin=(string)file_get_contents($root.'/admin/content_hub.php');
$builder=(string)file_get_contents($root.'/admin/page_builder.php');
$public=(string)file_get_contents($root.'/public/page.php');
$header=(string)file_get_contents($root.'/admin/partials/header.php');
$setup=(string)file_get_contents($root.'/admin/integration_center.php');
hub_check(str_contains($admin,'require_admin()') && str_contains($admin,'verify_csrf()'),'editor protected by admin auth and CSRF');
hub_check(str_contains($builder,'require_admin()') && str_contains($builder,'verify_csrf()'),'builder protected by admin auth and CSRF');
hub_check(str_contains($public,"status='published'"),'public pages require published status');
hub_check(str_contains($header,"'hub' =>") && str_contains($header,"'page_builder' =>"),'admin menu integrated');
hub_check(str_contains($setup,'hub_install(db())'),'non-destructive activation wired to Integration Center');
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_10_000011_content_hub.sql');
hub_check(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===3,'three additive tables');
hub_check(!preg_match('/\b(?:DROP|TRUNCATE|ALTER)\b/i',$sql),'no destructive migrations');
