<?php
declare(strict_types=1);
function core_inventory_audit_ready(PDO $pdo): bool {
    $q=$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='core_inventory_audit'");
    return (int)$q->fetchColumn()===1;
}
function core_inventory_install(PDO $pdo): void {
    $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_02_000010_inventory_core.sql');
    // Single CREATE TABLE statement; no DROP, TRUNCATE or ALTER.
    $sql=preg_replace('/^\s*--[^\n]*\n/m','',$sql);
    $statement=trim($sql);
    if(!preg_match('/^CREATE TABLE IF NOT EXISTS\s+core_inventory_audit\b/i',$statement)) {
        throw new RuntimeException('Migración de inventario no reconocida.');
    }
    $pdo->exec($statement);
}
