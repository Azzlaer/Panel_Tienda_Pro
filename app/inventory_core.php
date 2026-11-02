<?php
declare(strict_types=1);

/**
 * Commerce Enterprise Core: one transactional path for stock adjustments.
 * The caller must own the PDO transaction. Never perform DDL on customer requests.
 */
function inventory_core_decrease(PDO $pdo, int $sellerId, int $productId, int $quantity): int {
    if ($sellerId < 1 || $productId < 1 || $quantity < 1 || $quantity > 10000000) {
        throw new InvalidArgumentException('Identificadores o cantidad inválidos.');
    }
    if (!$pdo->inTransaction()) {
        throw new LogicException('La actualización de inventario exige una transacción activa.');
    }
    $q = $pdo->prepare("UPDATE products SET stock=stock-?,updated_at=NOW()
        WHERE id=? AND user_id=? AND status='published' AND stock IS NOT NULL AND stock>=?");
    $q->execute([$quantity,$productId,$sellerId,$quantity]);
    if ($q->rowCount() !== 1) {
        throw new RuntimeException('Stock insuficiente o producto no autorizado.');
    }
    $get=$pdo->prepare('SELECT stock FROM products WHERE id=? AND user_id=?');
    $get->execute([$productId,$sellerId]);
    return (int)$get->fetchColumn();
}
/**
 * Locked current product snapshot, suitable for price capture in orders/POS.
 */
function inventory_core_lock_product(PDO $pdo,int $sellerId,int $productId): array {
    if (!$pdo->inTransaction()) throw new LogicException('Transacción obligatoria.');
    $q=$pdo->prepare("SELECT id,user_id,title,price_clp,stock FROM products
        WHERE id=? AND user_id=? AND status='published' FOR UPDATE");
    $q->execute([$productId,$sellerId]);
    $p=$q->fetch(PDO::FETCH_ASSOC);
    if(!$p)throw new RuntimeException('Producto no encontrado o sin permisos.');
    return $p;
}
/** Snapshot of price with bounded multiplication. */
function inventory_core_total(int $unitPrice,int $quantity): int {
    if($unitPrice<0||$unitPrice>999999999999||$quantity<1||$quantity>10000||
       $quantity>intdiv(PHP_INT_MAX,max(1,$unitPrice))) {
        throw new InvalidArgumentException('Precio o cantidad fuera de rango.');
    }
    return $unitPrice*$quantity;
}
