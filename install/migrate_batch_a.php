<?php
/**
 * Batch A 数据库迁移脚本
 * 仅允许 CLI 执行
 * 幂等可重复执行
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

// 安全校验：数据库名必须包含 test 或 security
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

// 检查列是否存在的辅助函数
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}
function indexExists($pdo, $table, $index) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

$migrations = [];
// 1. pay_user 增加 frozen_money
if (!columnExists($pdo, "{$dbPrefix}_user", "frozen_money")) {
    $migrations[] = "ALTER TABLE `{$dbPrefix}_user` ADD COLUMN `frozen_money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '冻结余额（退款中占用）'";
}
// 2. pay_order 增加 refund_reserved
if (!columnExists($pdo, "{$dbPrefix}_order", "refund_reserved")) {
    $migrations[] = "ALTER TABLE `{$dbPrefix}_order` ADD COLUMN `refund_reserved` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款额度预留'";
}
// 3. pay_refundorder 增加新字段
$newColumns = [
    "channel" => "int DEFAULT NULL COMMENT '通道ID'",
    "upstream_refund_no" => "varchar(64) DEFAULT NULL COMMENT '上游退款单号'",
    "reserved_money" => "decimal(10,2) DEFAULT 0.00 COMMENT '预留退款金额'",
    "error_msg" => "varchar(255) DEFAULT NULL COMMENT '错误信息'",
    "audit_info" => "text DEFAULT NULL COMMENT '审计信息JSON'",
    "created_at" => "datetime DEFAULT NULL COMMENT '创建时间'",
    "processing_at" => "datetime DEFAULT NULL COMMENT '处理时间'",
    "settled_at" => "datetime DEFAULT NULL COMMENT '结算时间'",
];
foreach ($newColumns as $col => $def) {
    if (!columnExists($pdo, "{$dbPrefix}_refundorder", $col)) {
        $migrations[] = "ALTER TABLE `{$dbPrefix}_refundorder` ADD COLUMN `{$col}` {$def}";
    }
}
// 4. 唯一约束
if (!indexExists($pdo, "{$dbPrefix}_refundorder", "uk_trade_out")) {
    $migrations[] = "ALTER TABLE `{$dbPrefix}_refundorder` ADD UNIQUE KEY `uk_trade_out` (`trade_no`, `out_refund_no`)";
}

$success = 0;
$skipped = 0;
$failed = 0;

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 80) . "...\n";
        $success++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
            strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "SKIP (already exists): " . substr($sql, 0, 60) . "...\n";
            $skipped++;
        } else {
            echo "FAIL: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
}

// 5. 旧状态值迁移（0→10 CREATED, 1→30 SUCCESS, 2→30 SUCCESS, 3→50 UNKNOWN）
try {
    $pdo->exec("UPDATE `{$dbPrefix}_refundorder` SET status=10 WHERE status=0");
    $pdo->exec("UPDATE `{$dbPrefix}_refundorder` SET status=30 WHERE status=1 OR status=2");
    $pdo->exec("UPDATE `{$dbPrefix}_refundorder` SET status=50 WHERE status=3");
    echo "OK: Old refund status values migrated\n";
    $success++;
} catch (Exception $e) {
    echo "SKIP status migration: " . $e->getMessage() . "\n";
    $skipped++;
}

// 6. 管理员密码迁移（明文→password_hash）
try {
    $adminPwd = $pdo->query("SELECT v FROM `{$dbPrefix}_config` WHERE k='admin_pwd' LIMIT 1")->fetchColumn();
    if ($adminPwd && strpos($adminPwd, '$2y$') !== 0 && strpos($adminPwd, '$2a$') !== 0 && $adminPwd !== '!INSTALL_REQUIRED!') {
        $newHash = password_hash($adminPwd, PASSWORD_DEFAULT);
        $pdo->exec("UPDATE `{$dbPrefix}_config` SET v=" . $pdo->quote($newHash) . " WHERE k='admin_pwd'");
        echo "OK: Admin password migrated from plaintext to password_hash\n";
        $success++;
    } else {
        echo "SKIP: Admin password already hashed or is placeholder\n";
        $skipped++;
    }
} catch (Exception $e) {
    echo "SKIP admin password migration: " . $e->getMessage() . "\n";
    $skipped++;
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
