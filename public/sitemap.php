<?php
require_once __DIR__.'/../app/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$urls=[[url('index.php'),date('Y-m-d')]];
try{
 foreach(db()->query("SELECT id,COALESCE(updated_at,created_at) changed_at FROM products WHERE status='published' ORDER BY id DESC")->fetchAll() as $r)$urls[]=[url('product.php?id='.$r['id']),date('Y-m-d',strtotime($r['changed_at']))];
 foreach(db()->query("SELECT u.username,u.created_at FROM users u WHERE u.status='active' ORDER BY u.id DESC")->fetchAll() as $r)$urls[]=[url('profile.php?u='.urlencode($r['username'])),date('Y-m-d',strtotime($r['created_at']))];
 foreach(db()->query("SELECT id,COALESCE(updated_at,published_at,created_at) changed_at FROM news WHERE status='published' ORDER BY id DESC")->fetchAll() as $r)$urls[]=[url('news.php?id='.$r['id']),date('Y-m-d',strtotime($r['changed_at']))];
}catch(Throwable){}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><?php foreach($urls as [$loc,$lastmod]):?><url><loc><?=htmlspecialchars($loc,ENT_XML1|ENT_QUOTES,'UTF-8')?></loc><lastmod><?=$lastmod?></lastmod></url><?php endforeach;?></urlset>
