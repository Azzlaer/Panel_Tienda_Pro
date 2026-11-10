<?php
declare(strict_types=1);

$root = dirname(__DIR__);
if (!is_file($root . '/config/config.php') || !is_file($root . '/storage/installed.lock')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = preg_replace('~/(?:public|admin)/.*$~', '', $script) ?: '';
    header('Location: ' . $prefix . '/install/');
    exit;
}
// Set safe defaults before config.php starts the session; allow XAMPP HTTP locally.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}
require_once $root . '/config/config.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
if (defined('APP_ENV') && APP_ENV === 'production' && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) { header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
require_once __DIR__.'/public_asset_paths.php';
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path=''): string { return rtrim(APP_URL,'/') . '/' . ltrim($path,'/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Solicitud inválida o sesión vencida (CSRF).'); } }
function flash(string $type, string $message): void { $_SESSION['flash'][]=['type'=>$type,'message'=>$message]; }
function flashes(): array { $f=$_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function user(): ?array {
    static $cached = false;
    if ($cached !== false) return $cached;
    if (empty($_SESSION['uid'])) return $cached=null;
    $q=db()->prepare('SELECT u.*, p.* FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=?');
    $q->execute([(int)$_SESSION['uid']]); return $cached=$q->fetch() ?: null;
}
function require_login(): void { if (!user()) redirect('login.php'); }
function require_admin(): void { require_login(); if (user()['role']!=='admin' || user()['status']!=='active') { http_response_code(403); exit('Acceso denegado'); } }
function setting(string $key, string $default=''): string {
    if (!isset($GLOBALS['__settings_cache']) || !is_array($GLOBALS['__settings_cache'])) {
        $GLOBALS['__settings_cache'] = [];
        try {
            foreach (db()->query('SELECT `key`, value FROM settings')->fetchAll() as $row) {
                $GLOBALS['__settings_cache'][(string)$row['key']] = (string)($row['value'] ?? '');
            }
        } catch (Throwable) {
            // Durante la instalación la tabla settings aún puede no existir.
        }
    }
    return array_key_exists($key, $GLOBALS['__settings_cache']) ? (string)$GLOBALS['__settings_cache'][$key] : $default;
}
function clear_settings_cache(): void { unset($GLOBALS['__settings_cache']); }

function upload_image(string $field, string $folder): ?string {
    if (empty($_FILES[$field]['tmp_name'])) return null;
    $f=$_FILES[$field];
    if ($f['error']!==UPLOAD_ERR_OK || $f['size']>MAX_UPLOAD_BYTES) throw new RuntimeException('Archivo inválido o demasiado grande.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_MIMES,true)) throw new RuntimeException('Formato de imagen no permitido.');
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
    $name=bin2hex(random_bytes(16)).'.'.$ext;
    $dir=__DIR__.'/../public/uploads/'.$folder;
    if(!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('No se pudo guardar la imagen.');
    return 'uploads/'.$folder.'/'.$name;
}
function record_visit(): void {
    try {
        $path=substr($_SERVER['REQUEST_URI'] ?? '/',0,255);
        $ip=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').setting('visit_salt','local-salt'));
        $q=db()->prepare('INSERT INTO visits(path,ip_hash,user_id,user_agent,created_at) VALUES(?,?,?,?,NOW())');
        $q->execute([$path,$ip,user()['id'] ?? null,substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
    } catch (Throwable) { /* Una falla estadística no debe derribar el sitio. */ }
}
function login_is_limited(string $identity): bool {
    $hash=hash('sha256',strtolower($identity).'|'.($_SERVER['REMOTE_ADDR']??''));
    $q=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND success=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $q->execute([$hash]);
    return (int)$q->fetchColumn() >= 8;
}
function record_login_attempt(string $identity, bool $success): void {
    $hash=hash('sha256',strtolower($identity).'|'.($_SERVER['REMOTE_ADDR']??''));
    db()->prepare('INSERT INTO login_attempts(identity_hash,success) VALUES(?,?)')->execute([$hash,$success?1:0]);
    if ($success) db()->prepare('DELETE FROM login_attempts WHERE identity_hash=? AND success=0')->execute([$hash]);
}


/* ==============================
   Núcleo v1.6: modo vitrina, migraciones y auditoría
   ============================== */
function app_version(): string { return '6.9.0'; }
function ensure_core_v16_schema(): void {
    static $done=false; if($done)return; $done=true;
    db()->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
      migration VARCHAR(160) PRIMARY KEY,
      applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      admin_id BIGINT UNSIGNED NULL,
      action VARCHAR(100) NOT NULL,
      entity_type VARCHAR(80) NULL,
      entity_id VARCHAR(80) NULL,
      details_json MEDIUMTEXT NULL,
      ip_hash CHAR(64) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE SET NULL,
      INDEX(created_at), INDEX(action), INDEX(entity_type,entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $defaults=[
      'platform_mode'=>'showcase',
      'showcase_notice_enabled'=>'1',
      'showcase_notice'=>'Esta plataforma funciona como vitrina informativa. No procesa compras, pagos, reservas ni contratos. Cada visitante debe contactar directamente al anunciante para solicitar información.',
      'showcase_price_label'=>'Valor publicado',
      'showcase_contact_label'=>'Consultar al anunciante',
      'showcase_hide_stock'=>'1',
      'showcase_hide_sales_language'=>'1',
      'template_layout_version'=>'urban-2',
      'app_version'=>app_version(),
      'seo_default_description'=>'Explora artículos, servicios y comercios locales publicados en ComercioSocial.'
    ];
    $q=db()->prepare('INSERT IGNORE INTO settings(`key`,value) VALUES(?,?)');
    foreach($defaults as $k=>$v)$q->execute([$k,$v]);
    db()->prepare('INSERT IGNORE INTO schema_migrations(migration) VALUES(?)')->execute(['2026_07_16_000001_vitrine_core_v22']);
    db()->prepare('INSERT IGNORE INTO schema_migrations(migration) VALUES(?)')->execute(['2026_09_01_000002_enterprise_31']);
}
function save_setting(string $key,string $value): void {
    db()->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]);
    if (!isset($GLOBALS['__settings_cache']) || !is_array($GLOBALS['__settings_cache'])) $GLOBALS['__settings_cache']=[];
    $GLOBALS['__settings_cache'][$key]=$value;
}

function audit_log(string $action, ?string $entityType=null, string|int|null $entityId=null, array $details=[]): void {
    try {
        ensure_core_v16_schema();
        $admin=user();
        $salt=setting('visit_salt','local-salt');
        $ip=hash('sha256',($_SERVER['REMOTE_ADDR']??'').$salt);
        db()->prepare('INSERT INTO admin_audit_logs(admin_id,action,entity_type,entity_id,details_json,ip_hash) VALUES(?,?,?,?,?,?)')
          ->execute([$admin['id']??null,$action,$entityType,$entityId===null?null:(string)$entityId,$details?json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,$ip]);
    } catch(Throwable) {}
}
function is_showcase_mode(): bool { return setting('platform_mode','showcase')==='showcase'; }
function showcase_notice_html(string $class='showcase-notice'): string {
    if(!is_showcase_mode() || setting('showcase_notice_enabled','1')!=='1')return '';
    return '<div class="'.e($class).'">ℹ️ '.e(setting('showcase_notice')).'</div>';
}
function current_layout_version(): string { return setting('template_layout_version','urban-2'); }
function template_compatibility(array $manifest): array {
    $required=(string)($manifest['layout_version']??'');
    $current=current_layout_version();
    $supports=is_array($manifest['supports']??null)?$manifest['supports']:[];
    $ok=$required!=='' && $required===$current;
    return ['ok'=>$ok,'required'=>$required,'current'=>$current,'supports'=>$supports];
}
ensure_core_v16_schema();

set_exception_handler(function(Throwable $ex){
    http_response_code(500);
    $message='['.date(DATE_ATOM).'] '.$ex."\n\n";
    @file_put_contents(__DIR__.'/../storage/logs/error.log',$message,FILE_APPEND|LOCK_EX);
    if(APP_ENV==='local') echo '<pre>'.e((string)$ex).'</pre>'; else echo 'Error interno. Revisa storage/logs/error.log.';
});

function slugify(string $text): string {
    $text=trim(mb_strtolower($text));
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$text) ?: $text;
    $ascii=preg_replace('/[^a-z0-9]+/','-',$ascii) ?? '';
    return trim($ascii,'-') ?: bin2hex(random_bytes(4));
}
function unique_product_slug(string $title): string {
    $base=slugify($title); $slug=$base; $i=2;
    $q=db()->prepare('SELECT COUNT(*) FROM products WHERE slug=?');
    while(true){$q->execute([$slug]);if(!(int)$q->fetchColumn())return $slug;$slug=$base.'-'.$i++;}
}
function money_clp(int|float|string $amount): string { return '$'.number_format((float)$amount,0,',','.'); }
function admin_pagination(int $page,int $pages,array $query=[]): string {
    if($pages<=1)return '';
    unset($query['page']);
    $html='<nav class="admin-pagination" aria-label="Paginación">';
    $make=function(int $n,string $label,bool $active=false)use($query){$q=$query;$q['page']=$n;return '<a class="'.($active?'active':'').'" href="?'.e(http_build_query($q)).'">'.$label.'</a>';};
    if($page>1)$html.=$make($page-1,'←');
    $start=max(1,$page-2);$end=min($pages,$page+2);
    if($start>1){$html.=$make(1,'1');if($start>2)$html.='<span>…</span>';}
    for($i=$start;$i<=$end;$i++)$html.=$make($i,(string)$i,$i===$page);
    if($end<$pages){if($end<$pages-1)$html.='<span>…</span>';$html.=$make($pages,(string)$pages);}
    if($page<$pages)$html.=$make($page+1,'→');
    return $html.'</nav>';
}
function seller_stars(int $userId): int {
    $q=db()->prepare("SELECT stars FROM seller_monthly_scores WHERE user_id=? ORDER BY score_month DESC LIMIT 1");$q->execute([$userId]);
    return max(0,min(5,(int)($q->fetchColumn() ?: 0)));
}
function stars_html(int $stars): string { return '<span class="stars" aria-label="'.$stars.' de 5 estrellas">'.str_repeat('⭐',$stars).str_repeat('☆',5-$stars).'</span>'; }
function youtube_embed(?string $url): ?string {
    if(!$url)return null;
    if(preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([A-Za-z0-9_-]{6,})~',$url,$m)) return 'https://www.youtube-nocookie.com/embed/'.$m[1];
    if(preg_match('~vimeo\.com/(\d+)~',$url,$m)) return 'https://player.vimeo.com/video/'.$m[1];
    return null;
}
function current_usd_clp(): ?float {
    $manual=(float)setting('usd_clp_manual','0');
    $cached=(float)setting('usd_clp_cached','0');
    $cachedAt=setting('usd_clp_cached_at','');
    if($manual>0)return $manual;
    if($cached>0 && $cachedAt && strtotime($cachedAt)>time()-21600)return $cached;
    if(!filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOL))return $cached?:null;
    $context=stream_context_create(['http'=>['timeout'=>3,'user_agent'=>'ComercioSocial/1.0']]);
    $json=@file_get_contents('https://mindicador.cl/api/dolar',false,$context);
    if($json){$data=json_decode($json,true);$value=(float)($data['serie'][0]['valor']??0);if($value>0){
        $s=db()->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $s->execute(['usd_clp_cached',(string)$value]);$s->execute(['usd_clp_cached_at',date('Y-m-d H:i:s')]);return $value;
    }}
    return $cached?:null;
}
function adsense(string $slot='article'): string {
    if(setting('adsense_enabled','0')!=='1')return '';
    $client=trim(setting('adsense_client',''));$code=trim(setting('adsense_'.$slot.'_code',''));
    if($code!=='')return '<div class="ad-slot">'.$code.'</div>';
    if($client==='')return '';
    return '<div class="ad-slot"><ins class="adsbygoogle" style="display:block" data-ad-client="'.e($client).'" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script></div>';
}



/* ==============================
   Noticias administrables (BBCode + HTML seguro)
   ============================== */
function ensure_news_schema(): void {
    static $done=false; if($done)return; $done=true;
    db()->exec("CREATE TABLE IF NOT EXISTS news (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      created_by BIGINT UNSIGNED NOT NULL,
      title VARCHAR(190) NOT NULL,
      slug VARCHAR(220) NOT NULL UNIQUE,
      excerpt VARCHAR(600) NULL,
      content MEDIUMTEXT NOT NULL,
      content_format ENUM('bbcode','html','mixed') NOT NULL DEFAULT 'mixed',
      cover_image VARCHAR(255) NULL,
      status ENUM('draft','published','hidden') NOT NULL DEFAULT 'draft',
      featured TINYINT(1) NOT NULL DEFAULT 0,
      show_home TINYINT(1) NOT NULL DEFAULT 1,
      views BIGINT UNSIGNED NOT NULL DEFAULT 0,
      published_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT,
      INDEX(status,show_home,published_at), INDEX(featured,published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function unique_news_slug(string $title, int $ignoreId=0): string {
    $base=slugify($title); $slug=$base; $i=2;
    $q=db()->prepare('SELECT COUNT(*) FROM news WHERE slug=? AND id<>?');
    while(true){$q->execute([$slug,$ignoreId]);if(!(int)$q->fetchColumn())return $slug;$slug=$base.'-'.$i++;}
}
function sanitize_news_html(string $html): string {
    $allowed='<p><br><strong><b><em><i><u><s><blockquote><ul><ol><li><h2><h3><h4><a><img><iframe><div><span><hr><pre><code><table><thead><tbody><tr><th><td>';
    $html=strip_tags($html,$allowed);
    $html=preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i','',$html)??$html;
    $html=preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\')/i','',$html)??$html;
    $html=preg_replace('/(href|src)\s*=\s*(["\'])\s*(?:javascript:|data:text\/html)[^"\']*\2/i','$1="#"',$html)??$html;
    $html=preg_replace_callback('~<iframe\b([^>]*)src=["\']([^"\']+)["\']([^>]*)>\s*</iframe>~i',function($m){
        $src=youtube_embed($m[2]);
        if(!$src && preg_match('~^https://(?:www\.)?youtube-nocookie\.com/embed/[A-Za-z0-9_-]+~',$m[2]))$src=$m[2];
        if(!$src && preg_match('~^https://player\.vimeo\.com/video/\d+~',$m[2]))$src=$m[2];
        return $src?'<div class="news-embed"><iframe src="'.e($src).'" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>':'';
    },$html)??$html;
    return $html;
}
function bbcode_to_html(string $text): string {
    $html=e($text);
    $simple=['b'=>'strong','i'=>'em','u'=>'u','s'=>'s','quote'=>'blockquote','code'=>'code'];
    foreach($simple as $bb=>$tag)$html=preg_replace('~\['.$bb.'\](.*?)\[/'.$bb.'\]~is','<'.$tag.'>$1</'.$tag.'>',$html)??$html;
    $html=preg_replace('~\[h2\](.*?)\[/h2\]~is','<h2>$1</h2>',$html)??$html;
    $html=preg_replace('~\[h3\](.*?)\[/h3\]~is','<h3>$1</h3>',$html)??$html;
    $html=preg_replace('~\[url=(https?://[^\]\s]+)\](.*?)\[/url\]~is','<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>',$html)??$html;
    $html=preg_replace('~\[url\](https?://[^\[]+)\[/url\]~is','<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',$html)??$html;
    $html=preg_replace('~\[img\](https?://[^\[]+)\[/img\]~is','<img src="$1" alt="Imagen de noticia" loading="lazy">',$html)??$html;
    $html=preg_replace_callback('~\[(?:youtube|video)\](.*?)\[/(?:youtube|video)\]~is',function($m){$src=youtube_embed(html_entity_decode(trim($m[1]),ENT_QUOTES,'UTF-8'));return $src?'<div class="news-embed"><iframe src="'.e($src).'" loading="lazy" allowfullscreen></iframe></div>':'';},$html)??$html;
    $html=preg_replace('~\[list\](.*?)\[/list\]~is','<ul>$1</ul>',$html)??$html;
    $html=preg_replace('~\[\*\](.*?)(?=\[\*\]|</ul>)~is','<li>$1</li>',$html)??$html;
    return nl2br($html,false);
}
function render_news_content(string $content,string $format='mixed'): string {
    if($format==='bbcode')return bbcode_to_html($content);
    if($format==='html')return sanitize_news_html($content);
    $mixed=$content;
    $patterns=[
      '~\[b\](.*?)\[/b\]~is'=>'<strong>$1</strong>', '~\[i\](.*?)\[/i\]~is'=>'<em>$1</em>',
      '~\[u\](.*?)\[/u\]~is'=>'<u>$1</u>', '~\[quote\](.*?)\[/quote\]~is'=>'<blockquote>$1</blockquote>',
      '~\[url=(https?://[^\]\s]+)\](.*?)\[/url\]~is'=>'<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>',
      '~\[img\](https?://[^\[]+)\[/img\]~is'=>'<img src="$1" alt="Imagen de noticia" loading="lazy">'
    ];
    foreach($patterns as $p=>$r)$mixed=preg_replace($p,$r,$mixed)??$mixed;
    $mixed=preg_replace_callback('~\[(?:youtube|video)\](.*?)\[/(?:youtube|video)\]~is',function($m){$src=youtube_embed(trim(strip_tags($m[1])));return $src?'<div class="news-embed"><iframe src="'.e($src).'" loading="lazy" allowfullscreen></iframe></div>':'';},$mixed)??$mixed;
    return sanitize_news_html($mixed);
}
function news_excerpt(array $news,int $length=170): string {
    $text=trim((string)($news['excerpt']??''));
    if($text==='')$text=strip_tags(render_news_content((string)$news['content'],(string)$news['content_format']));
    return mb_strimwidth(preg_replace('/\s+/u',' ',$text)??$text,0,$length,'…','UTF-8');
}

/* ==============================
   Gestor seguro de plantillas
   ============================== */
function template_root(): string { return dirname(__DIR__) . '/public/templates'; }
function template_slug_valid(string $slug): bool { return (bool)preg_match('/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/', $slug); }
function template_manifest_path(string $slug): string { return template_root() . '/' . $slug . '/template.json'; }
function read_template_manifest(string $slug): ?array {
    if (!template_slug_valid($slug)) return null;
    $file = template_manifest_path($slug);
    if (!is_file($file)) return null;
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}
function installed_templates(): array {
    $root = template_root();
    if (!is_dir($root)) return [];
    $result = [];
    foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $slug = basename($dir); $manifest = read_template_manifest($slug);
        if ($manifest) { $manifest['slug'] = $slug; $result[] = $manifest; }
    }
    usort($result, fn($a,$b)=>strcasecmp((string)($a['name']??''),(string)($b['name']??'')));
    return $result;
}
function active_template_slug(): string {
    $slug = setting('active_template', 'urban-pro');
    return read_template_manifest($slug) ? $slug : 'urban-pro';
}
function template_public_asset(string $slug, string $relative): string {
    $relative = ltrim(str_replace('\\','/',$relative),'/');
    if (str_contains($relative,'..')) return '';
    return public_asset_url('templates/'.$slug.'/'.$relative);
}
function active_template_styles(): array {
    $slug = active_template_slug(); $m = read_template_manifest($slug);
    $styles = is_array($m['styles'] ?? null) ? $m['styles'] : ['assets/theme.css'];
    return array_values(array_filter(array_map(fn($p)=>template_public_asset($slug,(string)$p),$styles)));
}
function active_template_scripts(): array {
    $slug = active_template_slug(); $m = read_template_manifest($slug);
    $scripts = is_array($m['scripts'] ?? null) ? $m['scripts'] : [];
    return array_values(array_filter(array_map(fn($p)=>template_public_asset($slug,(string)$p),$scripts)));
}
function inspect_template_zip(string $zipFile): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('La extensión PHP zip no está habilitada. Activa extension=zip en php.ini y reinicia Apache.');
    if (!is_file($zipFile) || filesize($zipFile) > 25 * 1024 * 1024) throw new RuntimeException('El ZIP no existe o supera 25 MB.');
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) throw new RuntimeException('No se pudo abrir el archivo ZIP.');
    $files=[]; $manifestRaw=null; $total=0; $rootPrefix='';
    for($i=0;$i<$zip->numFiles;$i++){
        $stat=$zip->statIndex($i); $name=str_replace('\\','/',(string)$stat['name']);
        if ($name==='' || str_starts_with($name,'/') || preg_match('~(^|/)\.\.(/|$)~',$name) || preg_match('~^[A-Za-z]:~',$name)) { $zip->close(); throw new RuntimeException('El ZIP contiene una ruta insegura: '.$name); }
        $total += (int)($stat['size']??0); if($total > 60*1024*1024){$zip->close();throw new RuntimeException('El contenido descomprimido supera 60 MB.');}
        $files[]=['name'=>$name,'size'=>(int)($stat['size']??0)];
        if (basename($name)==='template.json') {
            if ($manifestRaw!==null){$zip->close();throw new RuntimeException('El ZIP contiene más de un template.json.');}
            $manifestRaw=$zip->getFromIndex($i); $rootPrefix=substr($name,0,-strlen('template.json'));
        }
    }
    $zip->close();
    if ($manifestRaw===null) throw new RuntimeException('Falta template.json en el ZIP.');
    $manifest=json_decode($manifestRaw,true);
    if(!is_array($manifest)) throw new RuntimeException('template.json no contiene JSON válido.');
    foreach(['name','slug','version','author','description'] as $required) if(trim((string)($manifest[$required]??''))==='') throw new RuntimeException('Falta el campo obligatorio "'.$required.'" en template.json.');
    $slug=(string)$manifest['slug']; if(!template_slug_valid($slug)) throw new RuntimeException('El slug solo admite letras minúsculas, números y guiones (3 a 60 caracteres).');
    if(!preg_match('/^\d+\.\d+(?:\.\d+)?(?:[-+][A-Za-z0-9.-]+)?$/',(string)$manifest['version'])) throw new RuntimeException('La versión debe usar formato 1.0 o 1.0.0.');
    $compat=(string)($manifest['min_app_version']??'1.6.0');
    if(version_compare(app_version(),$compat,'<')) throw new RuntimeException('Esta plantilla requiere ComercioSocial '.$compat.' o superior.');
    $layout=(string)($manifest['layout_version']??'');
    if($layout==='') throw new RuntimeException('template.json debe declarar "layout_version". Valor requerido: '.current_layout_version());
    if($layout!==current_layout_version()) throw new RuntimeException('Template incompatible: utiliza layout '.$layout.' y el sistema requiere '.current_layout_version().'.');
    $allowed=['css','js','png','jpg','jpeg','webp','gif','svg','json','txt','md','woff','woff2'];
    foreach($files as $f){
        $name=$f['name']; if(str_ends_with($name,'/'))continue;
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if(!in_array($ext,$allowed,true)) throw new RuntimeException('Archivo no permitido en plantillas: '.$name.'. No se admite PHP ni ejecutables.');
    }
    $styles=is_array($manifest['styles']??null)?$manifest['styles']:[];
    if(!$styles) throw new RuntimeException('template.json debe declarar al menos un archivo en "styles".');
    foreach(array_merge($styles,is_array($manifest['scripts']??null)?$manifest['scripts']:[]) as $asset){
        $asset=ltrim(str_replace('\\','/',(string)$asset),'/');
        $found=false; foreach($files as $f) if($f['name']===$rootPrefix.$asset){$found=true;break;}
        if(!$found) throw new RuntimeException('El archivo declarado no existe en el ZIP: '.$asset);
    }
    return ['manifest'=>$manifest,'files'=>$files,'root_prefix'=>$rootPrefix,'total_size'=>$total];
}
function install_template_zip(string $zipFile, array $inspection): string {
    $manifest=$inspection['manifest']; $slug=(string)$manifest['slug']; $prefix=(string)$inspection['root_prefix'];
    $target=template_root().'/'.$slug;
    if(is_dir($target)) throw new RuntimeException('Ya existe una plantilla con slug '.$slug.'. Elimínala primero o cambia el slug/versión del paquete.');
    if(!is_dir(template_root()) && !mkdir(template_root(),0755,true) && !is_dir(template_root())) throw new RuntimeException('No se pudo crear public/templates.');
    $temp=template_root().'/.install-'.bin2hex(random_bytes(6)); mkdir($temp,0755,true);
    $zip=new ZipArchive(); if($zip->open($zipFile)!==true) throw new RuntimeException('No se pudo reabrir el ZIP.');
    try{
        for($i=0;$i<$zip->numFiles;$i++){
            $name=str_replace('\\','/',$zip->getNameIndex($i));
            if($prefix!=='' && !str_starts_with($name,$prefix)) continue;
            $relative=$prefix!==''?substr($name,strlen($prefix)):$name;
            if($relative===''||str_ends_with($relative,'/'))continue;
            $dest=$temp.'/'.$relative; $dir=dirname($dest); if(!is_dir($dir))mkdir($dir,0755,true);
            $stream=$zip->getStream($name); if(!$stream)throw new RuntimeException('No se pudo leer '.$name);
            $out=fopen($dest,'wb'); stream_copy_to_stream($stream,$out); fclose($out); fclose($stream);
        }
    } finally {$zip->close();}
    if(!is_file($temp.'/template.json')){remove_tree($temp);throw new RuntimeException('La extracción no generó template.json.');}
    if(!rename($temp,$target)){remove_tree($temp);throw new RuntimeException('No se pudo completar la instalación.');}
    return $slug;
}
function remove_tree(string $dir): void { if(!is_dir($dir))return; foreach(scandir($dir)?:[] as $f){if($f==='.'||$f==='..')continue;$p=$dir.'/'.$f;if(is_dir($p))remove_tree($p);else@unlink($p);}@rmdir($dir); }


/* ==============================
   Feedback vendedor ↔ administración
   ============================== */
function ensure_feedback_schema(): void {
    static $done=false; if($done)return; $done=true;
    db()->exec("CREATE TABLE IF NOT EXISTS feedback_threads (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      seller_id BIGINT UNSIGNED NOT NULL,
      subject VARCHAR(180) NOT NULL,
      category ENUM('suggestion','problem','design','function','support','other') NOT NULL DEFAULT 'suggestion',
      priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
      status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
      admin_read TINYINT(1) NOT NULL DEFAULT 0,
      seller_read TINYINT(1) NOT NULL DEFAULT 1,
      is_public TINYINT(1) NOT NULL DEFAULT 0,
      seller_rating TINYINT UNSIGNED NULL,
      admin_rating TINYINT UNSIGNED NULL,
      assigned_admin_id BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL,
      INDEX(seller_id,status,created_at), INDEX(admin_read,status), INDEX(is_public,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("CREATE TABLE IF NOT EXISTS feedback_messages (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      thread_id BIGINT UNSIGNED NOT NULL,
      user_id BIGINT UNSIGNED NOT NULL,
      body TEXT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(thread_id) REFERENCES feedback_threads(id) ON DELETE CASCADE,
      FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
      INDEX(thread_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function feedback_category_label(string $category): string {
    return ['suggestion'=>'💡 Sugerencia','problem'=>'🐞 Problema','design'=>'🎨 Diseño','function'=>'⚙️ Función','support'=>'🧭 Ayuda','other'=>'💬 Otro'][$category] ?? '💬 Feedback';
}
function feedback_status_label(string $status): string {
    return ['open'=>'Abierto','answered'=>'Respondido','closed'=>'Cerrado'][$status] ?? $status;
}
function feedback_stars(int $rating, bool $interactive=false, string $name='rating'): string {
    $rating=max(0,min(5,$rating));
    if(!$interactive) return '<span class="feedback-stars" aria-label="'.$rating.' de 5">'.str_repeat('★',$rating).str_repeat('☆',5-$rating).'</span>';
    $html='<div class="star-picker" role="radiogroup" aria-label="Calificación">';
    for($i=5;$i>=1;$i--){$html.='<input type="radio" id="'.e($name).'_'.$i.'" name="'.e($name).'" value="'.$i.'"'.($rating===$i?' checked':'').'><label for="'.e($name).'_'.$i.'" title="'.$i.' estrellas">★</label>';}
    return $html.'</div>';
}

/* ==============================
   Usuarios avanzados y donaciones
   ============================== */
function ensure_donations_schema(): void {
    static $done=false; if($done)return; $done=true;
    db()->exec("CREATE TABLE IF NOT EXISTS donation_methods (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      method_type ENUM('bank','crypto','payment') NOT NULL DEFAULT 'bank',
      title VARCHAR(140) NOT NULL,
      description TEXT NULL,
      bank_name VARCHAR(140) NULL,
      account_holder VARCHAR(160) NULL,
      rut VARCHAR(20) NULL,
      account_type VARCHAR(80) NULL,
      account_number VARCHAR(100) NULL,
      email VARCHAR(190) NULL,
      wallet_address VARCHAR(255) NULL,
      network VARCHAR(100) NULL,
      active TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      INDEX(active,sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("CREATE TABLE IF NOT EXISTS donations (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      seller_id BIGINT UNSIGNED NOT NULL,
      method_id BIGINT UNSIGNED NULL,
      amount_clp BIGINT UNSIGNED NOT NULL DEFAULT 0,
      external_amount DECIMAL(20,8) NULL,
      external_currency VARCHAR(20) NULL,
      reference_code VARCHAR(180) NULL,
      proof_file VARCHAR(255) NULL,
      seller_message VARCHAR(1000) NULL,
      status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
      admin_note VARCHAR(1000) NULL,
      reviewed_by BIGINT UNSIGNED NULL,
      donated_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      reviewed_at DATETIME NULL,
      FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY(method_id) REFERENCES donation_methods(id) ON DELETE SET NULL,
      FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
      INDEX(seller_id,status,donated_at), INDEX(status,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function donation_minimum(): int { return max(0,(int)setting('donation_minimum_clp','50000')); }
function donation_required(): bool { return false; }
function seller_donation_status(int $sellerId, ?string $month=null): array {
    ensure_donations_schema(); $month=$month?:date('Y-m');
    $q=db()->prepare("SELECT COALESCE(SUM(amount_clp),0) total,COUNT(*) qty FROM donations WHERE seller_id=? AND status='verified' AND DATE_FORMAT(donated_at,'%Y-%m')=?");
    $q->execute([$sellerId,$month]); $row=$q->fetch()?:['total'=>0,'qty'=>0];
    $total=(int)$row['total']; $minimum=donation_minimum();
    return ['month'=>$month,'total'=>$total,'qty'=>(int)$row['qty'],'minimum'=>$minimum,'complete'=>$minimum===0||$total>=$minimum,'remaining'=>max(0,$minimum-$total)];
}
function upload_donation_proof(string $field='proof_file'): ?string {
    if(empty($_FILES[$field]['tmp_name']))return null;
    $f=$_FILES[$field]; if($f['error']!==UPLOAD_ERR_OK||$f['size']>8*1024*1024)throw new RuntimeException('El comprobante es inválido o supera 8 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    if(!isset($map[$mime]))throw new RuntimeException('El comprobante debe ser JPG, PNG, WEBP o PDF.');
    $dir=dirname(__DIR__).'/public/uploads/donations'; if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('No se pudo crear la carpeta de comprobantes.');
    $name=bin2hex(random_bytes(18)).'.'.$map[$mime]; if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('No se pudo guardar el comprobante.');
    return 'uploads/donations/'.$name;
}
function safe_upload_unlink(?string $relative): void {
    if(!$relative||!str_starts_with($relative,'uploads/'))return;
    $root=realpath(dirname(__DIR__).'/public/uploads'); $file=realpath(dirname(__DIR__).'/public/'.$relative);
    if($root&&$file&&str_starts_with($file,$root.DIRECTORY_SEPARATOR)&&is_file($file))@unlink($file);
}
function user_content_counts(int $userId): array {
    ensure_feedback_schema(); ensure_donations_schema();
    $queries=[
      'products'=>'SELECT COUNT(*) FROM products WHERE user_id=?','posts'=>'SELECT COUNT(*) FROM posts WHERE user_id=?',
      'comments'=>'SELECT COUNT(*) FROM comments WHERE user_id=?','feedback'=>'SELECT COUNT(*) FROM feedback_threads WHERE seller_id=?',
      'donations'=>'SELECT COUNT(*) FROM donations WHERE seller_id=?','sales'=>'SELECT COUNT(*) FROM seller_sales WHERE seller_id=?'
    ]; $out=[]; foreach($queries as $k=>$sql){$q=db()->prepare($sql);$q->execute([$userId]);$out[$k]=(int)$q->fetchColumn();} return $out;
}
function collect_user_uploads(int $userId, array $sections=['products','posts','profile']): array {
    $files=[];
    if(in_array('products',$sections,true)){
      $q=db()->prepare('SELECT cover_image FROM products WHERE user_id=?');$q->execute([$userId]);foreach($q->fetchAll() as $r)$files[]=$r['cover_image'];
      $q=db()->prepare('SELECT pi.image FROM product_images pi JOIN products p ON p.id=pi.product_id WHERE p.user_id=?');$q->execute([$userId]);foreach($q->fetchAll() as $r)$files[]=$r['image'];
    }
    if(in_array('posts',$sections,true)){$q=db()->prepare('SELECT image FROM posts WHERE user_id=?');$q->execute([$userId]);foreach($q->fetchAll() as $r)$files[]=$r['image'];}
    if(in_array('profile',$sections,true)){$q=db()->prepare('SELECT avatar,cover FROM profiles WHERE user_id=?');$q->execute([$userId]);if($r=$q->fetch()){$files[]=$r['avatar'];$files[]=$r['cover'];}}
    return array_values(array_filter(array_unique($files)));
}
function delete_user_content(int $targetId, array $sections): void {
    ensure_feedback_schema(); ensure_donations_schema(); $files=collect_user_uploads($targetId,$sections);
    db()->beginTransaction(); try{
      if(in_array('products',$sections,true))db()->prepare('DELETE FROM products WHERE user_id=?')->execute([$targetId]);
      if(in_array('posts',$sections,true))db()->prepare('DELETE FROM posts WHERE user_id=?')->execute([$targetId]);
      if(in_array('comments',$sections,true))db()->prepare('DELETE FROM comments WHERE user_id=?')->execute([$targetId]);
      if(in_array('feedback',$sections,true))db()->prepare('DELETE FROM feedback_threads WHERE seller_id=?')->execute([$targetId]);
      if(in_array('donations',$sections,true))db()->prepare('DELETE FROM donations WHERE seller_id=?')->execute([$targetId]);
      if(in_array('sales',$sections,true))db()->prepare('DELETE FROM seller_sales WHERE seller_id=?')->execute([$targetId]);
      db()->commit(); foreach($files as $f)safe_upload_unlink($f);
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function delete_user_account(int $targetId,int $actingAdminId): void {
    if($targetId===$actingAdminId)throw new RuntimeException('No puedes eliminar tu propia cuenta mientras estás conectado.');
    $q=db()->prepare('SELECT role,username FROM users WHERE id=?');$q->execute([$targetId]);$target=$q->fetch();if(!$target)throw new RuntimeException('Usuario no encontrado.');
    if($target['role']==='admin'){$admins=(int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();if($admins<=1)throw new RuntimeException('No se puede eliminar el último administrador activo.');}
    ensure_news_schema();ensure_feedback_schema();ensure_donations_schema();$files=collect_user_uploads($targetId,['products','posts','profile']);
    db()->beginTransaction();try{
      db()->prepare('UPDATE news SET created_by=? WHERE created_by=?')->execute([$actingAdminId,$targetId]);
      db()->prepare('UPDATE seller_sales SET created_by=? WHERE created_by=?')->execute([$actingAdminId,$targetId]);
      db()->prepare('UPDATE feedback_threads SET assigned_admin_id=NULL WHERE assigned_admin_id=?')->execute([$targetId]);
      db()->prepare('UPDATE donations SET reviewed_by=NULL WHERE reviewed_by=?')->execute([$targetId]);
      db()->prepare('DELETE FROM users WHERE id=?')->execute([$targetId]);
      db()->commit();foreach($files as $f)safe_upload_unlink($f);
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}


/** Public directory of curated local storefronts. */
function ensure_commerce_directory_schema(): void {
 static $done=false; if($done)return;
 db()->exec("CREATE TABLE IF NOT EXISTS commerce_directory (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(180) NOT NULL,
 local_number VARCHAR(60) NOT NULL DEFAULT '',
 image VARCHAR(255) NULL,
 description TEXT NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 UNIQUE KEY uq_commerce_user(user_id), INDEX idx_commerce_public(active,featured,sort_order),
 CONSTRAINT fk_directory_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $done=true;
}


/* Enterprise v4: banners rotativos configurables desde administración. */
function ensure_home_slides_schema(): void {
    static $checked=false;
    if ($checked) return;
    db()->exec("CREATE TABLE IF NOT EXISTS home_slides (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(255) NOT NULL,
        title VARCHAR(190) NOT NULL DEFAULT '',
        subtitle VARCHAR(500) NOT NULL DEFAULT '',
        button_text VARCHAR(90) NOT NULL DEFAULT '',
        button_url VARCHAR(500) NOT NULL DEFAULT '',
        active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(active,sort_order,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $checked=true;
}
function slide_destination(string $input): string {
    $input=trim($input);
    if($input==='') return '';
    if (str_starts_with($input,'/') && !str_starts_with($input,'//')) {
        $root=parse_url(APP_URL,PHP_URL_PATH)?:'';
        if ($root!=='' && str_starts_with($input,$root.'/')) return $input;
        throw new RuntimeException('Utiliza una ruta relativa al sitio o una URL HTTPS.');
    }
    if(preg_match('~^https://[^\s/]+(?:/[^\s]*)?$~i',$input)) return $input;
    if(preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[a-zA-Z0-9_./?=&%#-]+$~',$input)) return url($input);
    throw new RuntimeException('Enlace inválido. Usa una ruta del sitio o HTTPS.');
}

// Datos opcionales de la vitrina PRO. Tabla separada para conservar profiles sin ALTER destructivos.
function ensure_profile_pro_schema(): void {
    static $ready=false;
    if ($ready) return;
    db()->exec("CREATE TABLE IF NOT EXISTS profile_pro (
        user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        opening_hours VARCHAR(500) NULL,
        specialties VARCHAR(500) NULL,
        website VARCHAR(500) NULL,
        instagram VARCHAR(500) NULL,
        facebook VARCHAR(500) NULL,
        verified TINYINT(1) NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_profilepro_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready=true;
}
function profile_pro(int $userId): array {
    ensure_profile_pro_schema();
    $s=db()->prepare('SELECT * FROM profile_pro WHERE user_id=?');$s->execute([$userId]);
    return $s->fetch() ?: ['opening_hours'=>'','specialties'=>'','website'=>'','instagram'=>'','facebook'=>'','verified'=>0];
}
function safe_business_link(string $link): string {
    if ($link === '' || strlen($link)>500 || !filter_var($link,FILTER_VALIDATE_URL)) return '';
    $parts=parse_url($link);
    return ($parts && strtolower($parts['scheme']??'')==='https' && !empty($parts['host'])) ? $link : '';
}


/** Enterprise v5.1: store map, non-destructive supplementary tables. */
function ensure_commerce_map_schema(): void {
    static $ready=false;
    if ($ready) return;
    ensure_commerce_directory_schema();
    db()->exec("CREATE TABLE IF NOT EXISTS commerce_map_settings (
        id TINYINT UNSIGNED PRIMARY KEY,
        image VARCHAR(255) NULL,
        title VARCHAR(180) NOT NULL DEFAULT 'Plano de locales',
        description VARCHAR(500) NOT NULL DEFAULT '',
        updated_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("INSERT IGNORE INTO commerce_map_settings (id,title) VALUES (1,'Encuentra tu local')");
    db()->exec("CREATE TABLE IF NOT EXISTS commerce_map_pins (
        commerce_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        x_percent DECIMAL(6,3) NOT NULL,
        y_percent DECIMAL(6,3) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        CONSTRAINT fk_map_commerce FOREIGN KEY (commerce_id) REFERENCES commerce_directory(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready=true;
}

require_once __DIR__.'/theme_studio.php';
