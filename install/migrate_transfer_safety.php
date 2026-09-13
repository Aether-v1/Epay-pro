<?php
/**
 * Batch E3-A.2 Transfer Safety Migration
 * 
 * 新增：
 * - resolution 字段：细粒度转账状态 (CREATED/PROCESSING/SUCCESS/REJECTED/UNKNOWN/CANCELLED)
 * - request_hash 字段：请求指纹用于幂等冲突检测
 * - gateway_call_count 字段：上游调用次数追踪
 * - UNIQUE(uid, out_biz_no) 约束：保证业务号幂等
 * 
 * 兼容策略：
 * - 保留原有 status 字段 (0=处理中,1=成功,2=失败,3=待审核)
 * - resolution 与 status 的映射关系在代码中维护
 * - 历史数据的 resolution 根据现有 status 推断
 * 
 * 仅 CLI 可执行，非 CLI 立即拒绝。
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from CLI.');
}

require_once __DIR__ . '/../includes/common.php';

global $DB;

echo "=== Batch E3-A.2 Transfer Safety Migration ===\n";
echo "Database: " . DB_NAME . "\n";
echo "Prefix: " . DB_PREFIX . "\n\n";

// 1. 检查 pre_transfer 表是否存在
$tableExists = $DB->getColumn("SHOW TABLES LIKE '" . DB_PREFIX . "transfer'");
if (!$tableExists) {
    echo "[SKIP] pre_transfer table does not exist.\n";
    exit(0);
}

// 2. 检查是否已有重复数据（迁移前必须检查）
echo "[CHECK] Checking for duplicate (uid, out_biz_no) pairs...\n";
$duplicates = $DB->getAll("SELECT uid, out_biz_no, COUNT(*) as cnt FROM " . DB_PREFIX . "transfer GROUP BY uid, out_biz_no HAVING COUNT(*) > 1");
if (!empty($duplicates)) {
    echo "[FATAL] Found duplicate (uid, out_biz_no) pairs:\n";
    foreach ($duplicates as $dup) {
        echo "  uid={$dup['uid']}, out_biz_no={$dup['out_biz_no']}, count={$dup['cnt']}\n";
    }
    echo "\n[STOP] Migration aborted. Duplicate data must be resolved manually before adding UNIQUE constraint.\n";
    echo "Do NOT delete or merge financial records automatically. Please review and reconcile manually.\n";
    exit(1);
}
echo "[OK] No duplicate (uid, out_biz_no) pairs found.\n\n";

// 3. 新增 resolution 字段
echo "[MIGRATE] Adding resolution column...\n";
$colExists = $DB->getColumn("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . DB_PREFIX . "transfer' AND COLUMN_NAME = 'resolution'");
if (!$colExists) {
    $DB->exec("ALTER TABLE `" . DB_PREFIX . "transfer` ADD COLUMN `resolution` varchar(20) NOT NULL DEFAULT 'CREATED' COMMENT 'E3-A.2: 细粒度转账状态' AFTER `status`");
    echo "[OK] resolution column added.\n";
} else {
    echo "[SKIP] resolution column already exists.\n";
}

// 4. 新增 request_hash 字段
echo "[MIGRATE] Adding request_hash column...\n";
$colExists = $DB->getColumn("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . DB_PREFIX . "transfer' AND COLUMN_NAME = 'request_hash'");
if (!$colExists) {
    $DB->exec("ALTER TABLE `" . DB_PREFIX . "transfer` ADD COLUMN `request_hash` varchar(64) DEFAULT NULL COMMENT 'E3-A.2: 请求指纹用于幂等冲突检测' AFTER `resolution`");
    echo "[OK] request_hash column added.\n";
} else {
    echo "[SKIP] request_hash column already exists.\n";
}

// 5. 新增 gateway_call_count 字段
echo "[MIGRATE] Adding gateway_call_count column...\n";
$colExists = $DB->getColumn("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . DB_PREFIX . "transfer' AND COLUMN_NAME = 'gateway_call_count'");
if (!$colExists) {
    $DB->exec("ALTER TABLE `" . DB_PREFIX . "transfer` ADD COLUMN `gateway_call_count` int(11) NOT NULL DEFAULT 0 COMMENT 'E3-A.2: 上游调用次数' AFTER `request_hash`");
    echo "[OK] gateway_call_count column added.\n";
} else {
    echo "[SKIP] gateway_call_count column already exists.\n";
}

// 6. 迁移历史数据的 resolution
echo "[MIGRATE] Migrating historical resolution based on status...\n";
// status=1 (成功) → SUCCESS
$DB->exec("UPDATE `" . DB_PREFIX . "transfer` SET resolution = 'SUCCESS' WHERE status = 1 AND resolution = 'CREATED'");
// status=2 (失败) → REJECTED
$DB->exec("UPDATE `" . DB_PREFIX . "transfer` SET resolution = 'REJECTED' WHERE status = 2 AND resolution = 'CREATED'");
// status=3 (待审核) → CREATED (保持)
// status=0 (处理中) → UNKNOWN (历史处理中记录标记为需要对账)
$DB->exec("UPDATE `" . DB_PREFIX . "transfer` SET resolution = 'UNKNOWN' WHERE status = 0 AND resolution = 'CREATED' AND addtime < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
echo "[OK] Historical resolution migrated. Status=0 records older than 1 hour marked as UNKNOWN for reconciliation.\n";

// 7. 添加 UNIQUE(uid, out_biz_no) 约束
echo "[MIGRATE] Adding UNIQUE(uid, out_biz_no) constraint...\n";
$indexExists = $DB->getColumn("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . DB_PREFIX . "transfer' AND INDEX_NAME = 'uk_uid_outbizno'");
if (!$indexExists) {
    $DB->exec("ALTER TABLE `" . DB_PREFIX . "transfer` ADD UNIQUE KEY `uk_uid_outbizno` (`uid`, `out_biz_no`)");
    echo "[OK] UNIQUE constraint added.\n";
} else {
    echo "[SKIP] UNIQUE constraint already exists.\n";
}

// 8. 验证迁移结果
echo "\n[VERIFY] Migration verification:\n";
$columns = $DB->getAll("SHOW COLUMNS FROM `" . DB_PREFIX . "transfer` LIKE 'resolution'");
echo "  resolution column: " . (!empty($columns) ? "EXISTS" : "MISSING") . "\n";
$indexes = $DB->getAll("SHOW INDEX FROM `" . DB_PREFIX . "transfer` WHERE Key_name = 'uk_uid_outbizno'");
echo "  uk_uid_outbizno index: " . (!empty($indexes) ? "EXISTS" : "MISSING") . "\n";
$counts = $DB->getRow("SELECT 
    SUM(CASE WHEN resolution = 'CREATED' THEN 1 ELSE 0 END) as created,
    SUM(CASE WHEN resolution = 'PROCESSING' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN resolution = 'SUCCESS' THEN 1 ELSE 0 END) as success,
    SUM(CASE WHEN resolution = 'REJECTED' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN resolution = 'UNKNOWN' THEN 1 ELSE 0 END) as unknown,
    SUM(CASE WHEN resolution = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled,
    COUNT(*) as total
    FROM `" . DB_PREFIX . "transfer`");
echo "  Resolution distribution: CREATED={$counts['created']}, PROCESSING={$counts['processing']}, SUCCESS={$counts['success']}, REJECTED={$counts['rejected']}, UNKNOWN={$counts['unknown']}, CANCELLED={$counts['cancelled']}, TOTAL={$counts['total']}\n";

echo "\n=== Migration Complete ===\n";
echo "NOTE: Historical status=0 records older than 1 hour are marked as UNKNOWN.\n";
echo "Please reconcile these records before releasing frozen funds.\n";
exit(0);
