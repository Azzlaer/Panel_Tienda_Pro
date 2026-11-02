<?php
declare(strict_types=1);
// Static safety regression checks; does not need a configured database.
$installer = file_get_contents(__DIR__ . '/../install/index.php');
if ($installer === false) { fwrite(STDERR, "Cannot read installer\n"); exit(1); }
$checks = [
    'no database deletion' => !preg_match('/\\bDROP\\s+DATABASE\\b/i', $installer),
    'no destructive installer checkbox' => !str_contains($installer, 'name="reset_database"'),
    'reject nonempty database' => str_contains($installer, 'if ($existingTables>0)'),
    'lock creation is checked' => str_contains($installer, 'file_put_contents($lockFile') && str_contains($installer, '===false) throw new RuntimeException'),
    'CSRF validation' => str_contains($installer, 'hash_equals('),
    'password minimum length' => str_contains($installer, 'strlen($adminPass)<12'),
];
$failed = 0;
foreach ($checks as $name => $passed) {
    echo ($passed ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$passed) $failed++;
}
exit($failed ? 1 : 0);
