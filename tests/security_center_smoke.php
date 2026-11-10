<?php
declare(strict_types=1);
require_once __DIR__.'/../app/security_auth.php';
function security_assert(bool $condition,string $message): void {
    echo ($condition?'PASS ':'FAIL ').$message.PHP_EOL;
    if(!$condition)exit(1);
}
security_assert(security_registration_role()==='user','public registration is never admin');
security_assert(security_registration_status()==='pending','all public users require approval');
security_assert(!security_registration_valid_password('shortpass'),'short passwords rejected');
security_assert(security_registration_valid_password(str_repeat('a',12)),'12-character password accepted');
security_assert(!security_registration_valid_password(str_repeat('a',1025)),'oversized password rejected');
security_assert(security_registration_valid_email('test@example.com'),'valid email accepted');
security_assert(!security_registration_valid_email(str_repeat('x',200).'@example.com'),'oversized email rejected');
security_assert(!security_registration_valid_name(str_repeat('x',101)),'oversized display name rejected');
$root=dirname(__DIR__);
$registration=(string)file_get_contents($root.'/public/register.php');
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$panel=(string)file_get_contents($root.'/admin/security_center.php');
$navigation=(string)file_get_contents($root.'/admin/partials/header.php');
security_assert(!str_contains($registration, '$count===0?'), 'no first-user privilege escalation');
security_assert(str_contains($registration,'security_registration_role()') && str_contains($registration,'security_registration_status()'),'registration calls safe policy');
security_assert(str_contains($registration,'verify_csrf()'),'registration retains CSRF');
security_assert(str_contains($bootstrap,'session.use_strict_mode') && str_contains($bootstrap,'session.cookie_httponly'),'session safeguards');
security_assert(str_contains($panel,'require_admin()'),'dashboard restricted to admin');
security_assert(str_contains($navigation,"'security_center' =>"),'dashboard navigation available');
security_assert(str_contains($registration,'Cache-Control: no-store'),'signup page has no-store');
