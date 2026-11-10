<?php
declare(strict_types=1);

/** Security policy for public signups: administrator provisioning belongs to the installer. */
function security_registration_role(): string {
    return 'user';
}
function security_registration_status(): string {
    return 'pending';
}
function security_registration_valid_password(string $password): bool {
    return strlen($password) >= 12 && strlen($password) <= 1024;
}
function security_registration_valid_name(string $name): bool {
    return trim($name) !== '' && mb_strlen(trim($name),'UTF-8') <= 100;
}
function security_registration_valid_email(string $email): bool {
    return strlen($email) <= 190 && filter_var($email,FILTER_VALIDATE_EMAIL) !== false;
}
