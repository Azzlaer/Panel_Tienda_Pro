<?php
declare(strict_types=1);

/** Marketplace reuses published products. All filtering comes from a typed allowlist. */
function marketplace_ready(PDO $pdo): bool {
 try {
  $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('marketplace_favorites','marketplace_saved_searches')");
  $q->execute();return (int)$q->fetchColumn()===2;
 }catch(Throwable){return false;}
}
function marketplace_install(PDO $pdo): void {
 $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_10_000012_marketplace.sql');
 $statements=array_values(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',preg_replace('/^\s*--[^\n]*\n/m','',$sql))?:[])));
 if(count($statements)!==2)throw new RuntimeException('Migración Marketplace no reconocida.');
 foreach($statements as $statement) {
  if(!preg_match('/^CREATE TABLE IF NOT EXISTS\s+marketplace_(favorites|saved_searches)\b/i',$statement))throw new RuntimeException('Instrucción SQL no autorizada.');
  $pdo->exec($statement);
 }
}
function marketplace_filters(array $input): array {
 $q=mb_substr(trim((string)($input['q']??'')),0,100);
 $sort=(string)($input['sort']??'recent');
 if(!in_array($sort,['recent','popular','price_asc','price_desc','title'],true))$sort='recent';
 $int=static function(mixed $value,int $max): int {
  if(!is_scalar($value)||!preg_match('/^\d{1,15}$/',(string)$value))return 0;
  return max(0,min($max,(int)$value));
 };
 $minimum=$int($input['min']??'0',999999999999);
 $maximum=$int($input['max']??'0',999999999999);
 if($maximum>0&&$minimum>$maximum)[$minimum,$maximum]=[$maximum,$minimum];
 return [
  'q'=>$q,'category'=>$int($input['category']??'0',2147483647),
  'seller'=>$int($input['seller']??'0',2147483647),
  'min'=>$minimum,'max'=>$maximum,
  'available'=>!empty($input['available'])?1:0,'sort'=>$sort
 ];
}
function marketplace_where(array $f,array &$args): string {
 $where=["pr.status='published'","u.status='active'","c.active=1"];
 if($f['q']!==''){
  $where[]='(pr.title LIKE ? OR pr.description LIKE ? OR c.name LIKE ? OR p.display_name LIKE ?)';
  for($i=0;$i<4;$i++)$args[]='%'.$f['q'].'%';
 }
 if($f['category']>0){$where[]='(c.id=? OR c.parent_id=?)';$args[]=$f['category'];$args[]=$f['category'];}
 if($f['seller']>0){$where[]='pr.user_id=?';$args[]=$f['seller'];}
 if($f['min']>0){$where[]='pr.price_clp>=?';$args[]=$f['min'];}
 if($f['max']>0){$where[]='pr.price_clp<=?';$args[]=$f['max'];}
 if($f['available'])$where[]='(pr.stock IS NULL OR pr.stock>0)';
 return implode(' AND ',$where);
}
function marketplace_order(string $sort): string {
 return match($sort) {
  'popular'=>'pr.views DESC,pr.id DESC',
  'price_asc'=>'pr.price_clp ASC,pr.id DESC',
  'price_desc'=>'pr.price_clp DESC,pr.id DESC',
  'title'=>'pr.title ASC,pr.id DESC',
  default=>'pr.featured DESC,pr.created_at DESC,pr.id DESC'
 };
}
function marketplace_query(PDO $pdo,array $f,int $page=1,int $per=16): array {
 $args=[];$where=marketplace_where($f,$args);
 $base=' FROM products pr JOIN categories c ON c.id=pr.category_id JOIN users u ON u.id=pr.user_id JOIN profiles p ON p.user_id=u.id WHERE '.$where;
 $c=$pdo->prepare('SELECT COUNT(*)'.$base);$c->execute($args);$total=(int)$c->fetchColumn();
 $per=max(1,min(48,$per));$pages=max(1,(int)ceil($total/$per));$page=max(1,min($page,$pages));$offset=($page-1)*$per;
 $order=marketplace_order($f['sort']);
 $stmt=$pdo->prepare('SELECT pr.id,pr.user_id,pr.title,pr.price_clp,pr.cover_image,pr.stock,pr.views,pr.featured,c.name AS category_name,p.display_name AS merchant_name,u.username'.$base.' ORDER BY '.$order.' LIMIT '.$per.' OFFSET '.$offset);
 $stmt->execute($args);
 return ['items'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages];
}
function marketplace_selected_ids(array $input): array {
 $out=[];
 foreach($input as $v) {
  if(!is_scalar($v)||!preg_match('/^[1-9][0-9]{0,9}$/',(string)$v))continue;
  $id=(int)$v;if(!in_array($id,$out,true))$out[]=$id;
  if(count($out)>=4)break;
 }
 return $out;
}
function marketplace_comparison(PDO $pdo,array $ids): array {
 $ids=marketplace_selected_ids($ids);
 if(!$ids)return [];
 $placeholders=implode(',',array_fill(0,count($ids),'?'));
 $s=$pdo->prepare("SELECT pr.id,pr.title,pr.price_clp,pr.stock,pr.cover_image,pr.views,pr.description,c.name category_name,p.display_name merchant_name,u.username FROM products pr JOIN users u ON u.id=pr.user_id JOIN categories c ON c.id=pr.category_id JOIN profiles p ON p.user_id=u.id WHERE pr.status='published' AND u.status='active' AND c.active=1 AND pr.id IN ($placeholders)");
 $s->execute($ids);
 $rows=$s->fetchAll(PDO::FETCH_ASSOC);
 $order=array_flip($ids);
 usort($rows,static fn($a,$b)=>$order[(int)$a['id']]<=>$order[(int)$b['id']]);
 return $rows;
}
function marketplace_favorite(PDO $pdo,int $userId,int $productId,bool $add): void {
 if($userId<1||$productId<1)throw new InvalidArgumentException('IDs inválidos.');
 if($add){
  $q=$pdo->prepare("INSERT IGNORE INTO marketplace_favorites(user_id,product_id) SELECT ?,pr.id FROM products pr JOIN users u ON u.id=pr.user_id WHERE pr.id=? AND pr.status='published' AND u.status='active'");
  $q->execute([$userId,$productId]);
 }else{
  $q=$pdo->prepare('DELETE FROM marketplace_favorites WHERE user_id=? AND product_id=?');
  $q->execute([$userId,$productId]);
 }
}
function marketplace_search_url(array $filters): string {
 return http_build_query(marketplace_filters($filters),'','&',PHP_QUERY_RFC3986);
}
