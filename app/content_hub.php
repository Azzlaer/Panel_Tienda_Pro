<?php
declare(strict_types=1);
/**
 * Content Hub + Page Builder. Public requests must never execute schema changes.
 * Blocks are typed content and rendered with escaping, not arbitrary HTML/PHP.
 */
function hub_ready(PDO $pdo): bool {
    try {
        foreach (['hub_categories','hub_articles','hub_pages'] as $name) {
            $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
            $q->execute([$name]);
            if((int)$q->fetchColumn()!==1)return false;
        }
        return true;
    }catch(Throwable){return false;}
}
function hub_install(PDO $pdo): void {
    $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_10_000011_content_hub.sql');
    $sql=preg_replace('/^\s*--[^\n]*\n/m','',$sql);
    $statements=array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql) ?: []));
    if(count($statements)!==3)throw new RuntimeException('Migración inesperada.');
    foreach($statements as $statement) {
        if(!preg_match('/^CREATE TABLE IF NOT EXISTS\s+hub_(?:categories|articles|pages)\b/i',$statement))throw new RuntimeException('DDL no autorizado.');
        $pdo->exec($statement);
    }
}
function hub_slug(string $value): string {
    $value=trim(mb_strtolower($value,'UTF-8'));
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);
    $slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($ascii===false?$value:$ascii)) ?? '', '-');
    if($slug===''||strlen($slug)>180)throw new InvalidArgumentException('Slug inválido; usa un título corto.');
    return $slug;
}
function hub_status(string $value): string {
    if(!in_array($value,['draft','published','hidden'],true))throw new InvalidArgumentException('Estado inválido.');
    return $value;
}
function hub_safe_image(string $value): ?string {
    $value=trim($value);
    if($value===''||strlen($value)>500||preg_match('/[\x00-\x1f\x7f]/',$value))return null;
    if(preg_match('~^https://[a-z0-9.-]+(?::443)?(?:/[^?#]*)?(?:\?[^#]*)?$~i',$value))return $value;
    if(preg_match('~^(?:uploads|assets)/[a-zA-Z0-9/_-]+\.(?:jpg|jpeg|png|webp|gif|svg)$~i',$value))return $value;
    return null;
}
function hub_safe_link(string $value): ?string {
    $value=trim($value);
    if($value===''||strlen($value)>1000||preg_match('/[\x00-\x1f\x7f]/',$value))return null;
    if(preg_match('~^https://[a-z0-9.-]+(?::443)?(?:/[^\s#]*)?$~i',$value))return $value;
    if(preg_match('~^(?:[a-zA-Z][a-zA-Z0-9_-]*\.php(?:\?[a-zA-Z0-9=&_%.-]*)?|/public/[a-zA-Z0-9/_?=&%.-]+)$~',$value))return $value;
    return null;
}
function hub_normalize_blocks(string $raw): string {
    if(strlen($raw)>65000)throw new InvalidArgumentException('El documento supera el tamaño permitido.');
    try {$blocks=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(Throwable){throw new InvalidArgumentException('JSON de bloques inválido.');}
    if(!is_array($blocks)||!array_is_list($blocks)||count($blocks)>40)throw new InvalidArgumentException('Máximo 40 bloques.');
    $allowed=['hero','heading','text','image','callout','cta','divider'];
    $output=[];
    foreach($blocks as $b){
        if(!is_array($b)||!in_array($b['type']??null,$allowed,true))throw new InvalidArgumentException('Tipo de bloque no permitido.');
        $type=$b['type'];$title=mb_substr(trim((string)($b['title']??'')),0,180);
        $body=mb_substr(trim((string)($b['body']??'')),0,10000);
        $image=hub_safe_image((string)($b['image']??''));
        $link=hub_safe_link((string)($b['link']??''));
        if($type==='image'&&$image===null)throw new InvalidArgumentException('Imagen inválida: usa HTTPS o ruta local de assets/uploads.');
        if($type==='cta'&&$link===null)throw new InvalidArgumentException('Enlace inválido para botón CTA.');
        $output[]=['type'=>$type,'title'=>$title,'body'=>$body,'image'=>$image??'','link'=>$link??''];
    }
    return json_encode($output,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
function hub_escape(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function hub_paragraphs(string $value): string {
    $parts=preg_split('/\R{2,}/u',trim($value)) ?: [];
    $out='';
    foreach($parts as $p)if(trim($p)!=='')$out.='<p>'.nl2br(hub_escape(trim($p)),false).'</p>';
    return $out;
}
function hub_render_blocks(string $json, string $assetBase=''): string {
    try{$blocks=json_decode(hub_normalize_blocks($json),true,32,JSON_THROW_ON_ERROR);}
    catch(Throwable){return '';}
    $out='';
    foreach($blocks as $b){
        $type=$b['type'];$title=hub_escape($b['title']);$body=hub_paragraphs($b['body']);
        if($type==='hero')$out.='<section class="hub-block hub-hero"><span class="hub-kicker">COMERCIO SOCIAL</span><h2>'.$title.'</h2>'.$body.'</section>';
        elseif($type==='heading')$out.='<section class="hub-block hub-heading"><h2>'.$title.'</h2></section>';
        elseif($type==='text')$out.='<section class="hub-block hub-prose">'.$body.'</section>';
        elseif($type==='callout')$out.='<aside class="hub-block hub-callout"><h3>'.$title.'</h3>'.$body.'</aside>';
        elseif($type==='divider')$out.='<hr class="hub-divider">';
        elseif($type==='image'){
            $src=$b['image'];if(!str_starts_with($src,'https://'))$src=rtrim($assetBase,'/').'/'.ltrim($src,'/');
            $out.='<figure class="hub-block hub-figure"><img loading="lazy" src="'.hub_escape($src).'" alt="'.$title.'"><figcaption>'.$title.'</figcaption></figure>';
        }elseif($type==='cta'){
            $href=$b['link'];if(!str_starts_with($href,'https://')&&!str_starts_with($href,'/'))$href=rtrim($assetBase,'/').'/'.$href;
            $out.='<section class="hub-block hub-cta"><h3>'.$title.'</h3>'.$body.'<a class="hub-button" href="'.hub_escape($href).'">Ver más</a></section>';
        }
    }
    return $out;
}
