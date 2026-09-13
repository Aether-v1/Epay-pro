<?php
/**
 * Migration: 新增 USDT-TRC20 结算方式（settle_usdt 配置项）
 * ------------------------------------------------------------------
 * 仅允许 CLI 执行；幂等，可重复执行。
 * 本迁移只新增一条 pre_config 数据（结算开关），不做任何 DDL。
 * pre_user.settle_id / pre_settle.type 复用数字 5 = USDT-TRC20，
 * account 字段（varchar 128）可直接容纳 TRON 地址（34 字符），
 * 单网络默认 TRC20，无需新增 settle_network 字段。
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only');
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbSocket = getenv('DB_SOCKET') ?: '';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: '';
$dbPrefix = getenv('DB_PREFIX') ?: 'pay';

if (empty($dbName)) {
    echo "ERROR: DB_NAME environment variable required\n";
    exit(1);
}

// 安全校验：数据库名必须包含 test 或 security 标记
if (stripos($dbName, 'test') === false && stripos($dbName, 'security') === false) {
    echo "ERROR: DB_NAME must contain 'test' or 'security' marker for safety\n";
    exit(1);
}

try {
    $pdo = new PDO(($dbSocket ? "mysql:unix_socket={$dbSocket};dbname={$dbName};charset=utf8mb4" : "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4"), $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Connected to database: {$dbName}\n";
} catch (Exception $e) {
    echo "ERROR: Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$success = 0;
$skipped = 0;
$failed = 0;
$configTable = "`{$dbPrefix}_config`";

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO {$configTable} (`k`,`v`) VALUES ('settle_usdt','0')");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "OK: inserted settle_usdt=0 (USDT-TRC20 settle switch, default OFF)\n";
        $success++;
    } else {
        $cur = $pdo->query("SELECT v FROM {$configTable} WHERE k='settle_usdt'")->fetchColumn();
        echo "SKIP: settle_usdt already exists (current value: " . var_export($cur, true) . ")\n";
        $skipped++;
    }
} catch (Exception $e) {
    echo "FAIL settle_usdt migration: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Migration Summary ===\n";
echo "Success: {$success}\n";
echo "Skipped: {$skipped}\n";
echo "Failed: {$failed}\n";

if ($failed > 0) {
    echo "\nWARNING: Some migrations failed. Please review above output.\n";
    exit(1);
}

echo "\nMigration completed successfully.\n";
