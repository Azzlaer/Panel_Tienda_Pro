<?php
declare(strict_types=1);
/** Merchant Center PRO — explicit tenant boundary by owner_id on EVERY write. */
function merchant_ready(PDO $pdo): bool {
 try {$s=$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('merchant_staff','merchant_contacts','merchant_notes','merchant_tasks')");return (int)$s->fetchColumn()===4;}catch(Throwable){return false;}
}
function merchant_install(PDO $pdo): void {
 $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_10_000013_merchant_center.sql');
 $parts=array_values(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',preg_replace('/^\s*--[^\n]*\n/m','',$sql))?:[])));
 if(count($parts)!==4)throw new RuntimeException('Migración Merchant Center no reconocida.');
 foreach($parts as $s){if(!preg_match('/^CREATE TABLE IF NOT EXISTS\s+merchant_(?:staff|contacts|notes|tasks)\b/i',$s))throw new RuntimeException('SQL de migración no autorizado.');$pdo->exec($s);}
}
function merchant_role(PDO $pdo,int $actor,int $owner): ?string {
 if($actor<1||$owner<1)return null;
 if($actor===$owner)return 'owner';
 $q=$pdo->prepare("SELECT s.role FROM merchant_staff s JOIN users u ON u.id=s.member_id WHERE s.owner_id=? AND s.member_id=? AND s.active=1 AND u.status='active'");
 $q->execute([$owner,$actor]);$role=$q->fetchColumn();return $role===false?null:(string)$role;
}
function merchant_require(PDO $pdo,int $actor,int $owner,string $action): string {
 $role=merchant_role($pdo,$actor,$owner);
 $allowed=match($action){
  'view'=>['owner','manager','support','analyst'],
  'crm','tasks'=>['owner','manager','support'],
  'team'=>['owner'],
  default=>[]
 };
 if(!in_array($role,$allowed,true))throw new RuntimeException('Acceso denegado al comercio.');
 return (string)$role;
}
function merchant_contact_save(PDO $pdo,int $actor,int $owner,int $id,array $data): int {
 merchant_require($pdo,$actor,$owner,'crm');
 $name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$phone=trim((string)($data['phone']??''));$company=trim((string)($data['company']??''));$stage=(string)($data['stage']??'lead');
 if(mb_strlen($name)<2||mb_strlen($name)>120||mb_strlen($company)>120||mb_strlen($phone)>40||($email!==''&&(strlen($email)>190||!filter_var($email,FILTER_VALIDATE_EMAIL)))||!in_array($stage,['lead','contacted','customer','archived'],true))throw new InvalidArgumentException('Datos del contacto inválidos.');
 if($id>0) {
  $q=$pdo->prepare('UPDATE merchant_contacts SET name=?,email=?,phone=?,company=?,stage=?,updated_at=NOW() WHERE id=? AND owner_id=?');
  $q->execute([$name,$email?:null,$phone?:null,$company?:null,$stage,$id,$owner]);
  $e=$pdo->prepare('SELECT id FROM merchant_contacts WHERE id=? AND owner_id=?');$e->execute([$id,$owner]);
  if(!$e->fetchColumn())throw new RuntimeException('Contacto no encontrado.');
  return $id;
 }
 $q=$pdo->prepare('INSERT INTO merchant_contacts(owner_id,created_by,name,email,phone,company,stage) VALUES(?,?,?,?,?,?,?)');
 $q->execute([$owner,$actor,$name,$email?:null,$phone?:null,$company?:null,$stage]);
 return (int)$pdo->lastInsertId();
}
function merchant_note_add(PDO $pdo,int $actor,int $owner,int $contactId,string $note): void {
 merchant_require($pdo,$actor,$owner,'crm');$note=trim($note);
 if(mb_strlen($note)<2||mb_strlen($note)>2000)throw new InvalidArgumentException('Nota inválida.');
 $s=$pdo->prepare('INSERT INTO merchant_notes(owner_id,contact_id,author_id,note) SELECT ?,id,?,? FROM merchant_contacts WHERE id=? AND owner_id=?');
 $s->execute([$owner,$actor,$note,$contactId,$owner]);
 if($s->rowCount()!==1)throw new RuntimeException('Contacto no autorizado.');
}
function merchant_task_save(PDO $pdo,int $actor,int $owner,string $title,?int $assigned,?int $contact,?string $due): int {
 merchant_require($pdo,$actor,$owner,'tasks');$title=trim($title);
 if(mb_strlen($title)<3||mb_strlen($title)>180)throw new InvalidArgumentException('Título de tarea inválido.');
 if($due!==null && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$due))throw new InvalidArgumentException('Fecha inválida.');
 if($contact!==null){$q=$pdo->prepare('SELECT id FROM merchant_contacts WHERE id=? AND owner_id=?');$q->execute([$contact,$owner]);if(!$q->fetchColumn())throw new RuntimeException('Contacto ajeno.');}
 if($assigned!==null && merchant_role($pdo,$assigned,$owner)===null)throw new RuntimeException('Empleado ajeno al comercio.');
 $s=$pdo->prepare('INSERT INTO merchant_tasks(owner_id,created_by,assigned_to,contact_id,title,due_date) VALUES(?,?,?,?,?,?)');$s->execute([$owner,$actor,$assigned,$contact,$title,$due]);return (int)$pdo->lastInsertId();
}
function merchant_task_transition(PDO $pdo,int $actor,int $owner,int $id,string $to): void {
 merchant_require($pdo,$actor,$owner,'tasks');
 if(!in_array($to,['todo','doing','done'],true))throw new InvalidArgumentException('Estado inválido.');
 $q=$pdo->prepare('UPDATE merchant_tasks SET status=?,updated_at=NOW() WHERE id=? AND owner_id=?');
 $q->execute([$to,$id,$owner]);
 if($q->rowCount()===0){$q=$pdo->prepare('SELECT id FROM merchant_tasks WHERE id=? AND owner_id=?');$q->execute([$id,$owner]);if(!$q->fetchColumn())throw new RuntimeException('Tarea no encontrada.');}
}
function merchant_staff_set(PDO $pdo,int $actor,int $owner,int $member,string $role,bool $active): void {
 merchant_require($pdo,$actor,$owner,'team');
 if($member===$owner||$member<1||!in_array($role,['manager','support','analyst'],true))throw new InvalidArgumentException('Empleado o rol inválido.');
 $q=$pdo->prepare("SELECT id FROM users WHERE id=? AND status='active'");$q->execute([$member]);if(!$q->fetchColumn())throw new RuntimeException('La cuenta no está activa.');
 $q=$pdo->prepare('INSERT INTO merchant_staff(owner_id,member_id,role,active) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE role=VALUES(role),active=VALUES(active)');
 $q->execute([$owner,$member,$role,$active?1:0]);
}
function merchant_context(PDO $pdo,int $actor,mixed $requested): array {
 $owner=filter_var($requested,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:$actor;
 $role=merchant_require($pdo,$actor,$owner,'view');return ['owner'=>$owner,'role'=>$role];
}
