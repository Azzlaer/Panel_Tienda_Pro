<?php
declare(strict_types=1);
/**
 * NameEffects-inspired VIP visual effects for product TITLES.
 * Preset-only. Never accept user-written CSS, HTML, JS or file paths.
 * VIP eligibility comes from explicit administrative grants, not user-submitted values.
 */
function vip_effect_catalog(): array {
 return [
  'none'=>['name'=>'Sin efecto','description'=>'Texto original, sin animación'],
  'gold'=>['name'=>'Oro VIP','description'=>'Resplandor dorado elegante'],
  'neon'=>['name'=>'Neón azul','description'=>'Resplandor azul eléctrico'],
  'emerald'=>['name'=>'Esmeralda','description'=>'Brillo verde comercial'],
  'fire'=>['name'=>'Fuego','description'=>'Destello anaranjado'],
  'rainbow'=>['name'=>'Arcoíris','description'=>'Gradiente de colores animado'],
  'pulse'=>['name'=>'Pulso luminoso','description'=>'Brillo suave animado'],
 ];
}
function vip_effect_validate_key(string $key): string {
 if(!array_key_exists($key,vip_effect_catalog()))throw new InvalidArgumentException('Efecto visual no permitido.');
 return $key;
}
function vip_effect_ready(PDO $pdo): bool {
 try {
  $q=$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('vip_seller_access','vip_product_effects')");
  return (int)$q->fetchColumn()===2;
 }catch(Throwable){return false;}
}
function vip_effect_install(PDO $pdo): void {
 $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_10_000014_vip_product_effects.sql');
 $sql=preg_replace('/^\s*--[^\n]*\n/m','',$sql);
 $parts=array_values(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql)?:[])));
 if(count($parts)!==2)throw new RuntimeException('Migración VIP no reconocida.');
 foreach($parts as $query){
  if(!preg_match('/^CREATE TABLE IF NOT EXISTS\s+vip_(?:seller_access|product_effects)\b/i',$query))throw new RuntimeException('DDL VIP no autorizado.');
  $pdo->exec($query);
 }
}
function vip_seller_active(PDO $pdo,int $seller): bool {
 if($seller<1||!vip_effect_ready($pdo))return false;
 $q=$pdo->prepare("SELECT COUNT(*) FROM vip_seller_access a JOIN users u ON u.id=a.seller_id WHERE a.seller_id=? AND a.enabled=1 AND (a.expires_at IS NULL OR a.expires_at>NOW()) AND u.status='active'");
 $q->execute([$seller]);
 return (int)$q->fetchColumn()===1;
}
function vip_effect_assign(PDO $pdo,int $actor,int $product,string $key,bool $admin=false): void {
 $key=vip_effect_validate_key($key);
 if(!vip_effect_ready($pdo))throw new RuntimeException('Activa primero Efectos VIP en Integration Center.');
 if($actor<1||$product<1)throw new InvalidArgumentException('Artículo inválido.');
 $q=$pdo->prepare('SELECT user_id FROM products WHERE id=? LIMIT 1');$q->execute([$product]);
 $owner=(int)$q->fetchColumn();
 if($owner<1)throw new RuntimeException('Artículo inexistente.');
 if(!$admin && $owner!==$actor)throw new RuntimeException('No puedes modificar artículos de otro vendedor.');
 if(!$admin && !vip_seller_active($pdo,$owner))throw new RuntimeException('Necesitas una membresía VIP activa.');
 if($key==='none'){
  $q=$pdo->prepare('DELETE FROM vip_product_effects WHERE product_id=? AND seller_id=?');$q->execute([$product,$owner]);return;
 }
 $q=$pdo->prepare("INSERT INTO vip_product_effects(product_id,seller_id,effect_key) VALUES(?,?,?) ON DUPLICATE KEY UPDATE seller_id=VALUES(seller_id),effect_key=VALUES(effect_key)");
 $q->execute([$product,$owner,$key]);
}
function vip_grant(PDO $pdo,int $admin,int $seller,bool $enabled,?string $until): void {
 if(!vip_effect_ready($pdo))throw new RuntimeException('Activa Efectos VIP en Integration Center.');
 if($admin<1||$seller<1)throw new InvalidArgumentException('Cuenta inválida.');
 if($until!==null){
  $date=DateTimeImmutable::createFromFormat('!Y-m-d',$until);
  if(!$date||$date->format('Y-m-d')!==$until||$until<date('Y-m-d'))throw new InvalidArgumentException('Fecha de vencimiento inválida.');
  $until.=' 23:59:59';
 }
 $q=$pdo->prepare("SELECT id FROM users WHERE id=? AND status='active'");$q->execute([$seller]);
 if(!$q->fetchColumn())throw new RuntimeException('El vendedor debe tener una cuenta activa.');
 $q=$pdo->prepare("INSERT INTO vip_seller_access(seller_id,enabled,expires_at,granted_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),expires_at=VALUES(expires_at),granted_by=VALUES(granted_by),updated_at=NOW()");
 $q->execute([$seller,$enabled?1:0,$until,$admin]);
}
/** Retrieve active presets for published product listings, with one batch query (no N+1). */
function vip_effect_map(PDO $pdo,array $productIds): array {
 if(!vip_effect_ready($pdo))return [];
 $ids=[];
 foreach($productIds as $v){if(is_numeric($v)&&ctype_digit((string)$v)&&(int)$v>0)$ids[(int)$v]=(int)$v;}
 if(!$ids)return [];
 $ids=array_slice(array_values($ids),0,250);
 $params=implode(',',array_fill(0,count($ids),'?'));
 $q=$pdo->prepare("SELECT e.product_id,e.effect_key FROM vip_product_effects e JOIN products pr ON pr.id=e.product_id AND pr.user_id=e.seller_id JOIN users u ON u.id=e.seller_id JOIN vip_seller_access v ON v.seller_id=e.seller_id WHERE e.product_id IN ($params) AND pr.status='published' AND u.status='active' AND v.enabled=1 AND (v.expires_at IS NULL OR v.expires_at>NOW())");
 $q->execute($ids);$result=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
  $key=(string)$row['effect_key'];
  if(array_key_exists($key,vip_effect_catalog())&&$key!=='none')$result[(int)$row['product_id']]=$key;
 }
 return $result;
}
function vip_effect_class(?string $key): string {
 if($key===null||!array_key_exists($key,vip_effect_catalog())||$key==='none')return '';
 return 'vip-fx vip-fx-'. $key;
}
