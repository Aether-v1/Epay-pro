<?php
/**
 * Batch C Phase2: RBAC 数据库迁移
 * 仅 CLI 可执行
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only');
}

// 直接初始化数据库连接，不依赖 common.php
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_socket = getenv('DB_SOCKET') ?: '';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'epay';
$db_prefix = getenv('DB_PREFIX') ?: 'pay';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
if ($db_socket) {
    $dsn = "mysql:unix_socket={$db_socket};dbname={$db_name};charset=utf8mb4";
}

try {
    $DB = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== Batch C Phase2: RBAC Migration ===\n";
echo "Database: {$db_name}, Prefix: {$db_prefix}\n\n";

$pre = $db_prefix;

// 1. 创建 admins 表
echo "1. Creating {$pre}_admins table...\n";
$DB->exec("CREATE TABLE IF NOT EXISTS `{$pre}_admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "   Done.\n";

// 2. 创建 roles 表
echo "2. Creating {$pre}_roles table...\n";
$DB->exec("CREATE TABLE IF NOT EXISTS `{$pre}_roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "   Done.\n";

// 3. 创建 permissions 表
echo "3. Creating {$pre}_permissions table...\n";
$DB->exec("CREATE TABLE IF NOT EXISTS `{$pre}_permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_key` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "   Done.\n";

// 4. 创建 admin_roles 表
echo "4. Creating {$pre}_admin_roles table...\n";
$DB->exec("CREATE TABLE IF NOT EXISTS `{$pre}_admin_roles` (
    `admin_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`admin_id`, `role_id`),
    KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "   Done.\n";

// 5. 创建 role_permissions 表
echo "5. Creating {$pre}_role_permissions table...\n";
$DB->exec("CREATE TABLE IF NOT EXISTS `{$pre}_role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_key` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_key`),
    KEY `idx_permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
echo "   Done.\n";

// 6. 插入默认权限
echo "6. Inserting default permissions...\n";
$permissions = [
    'system.config' => '系统配置管理',
    'system.admin' => '管理员管理',
    'payment.channel' => '支付通道管理',
    'payment.type' => '支付方式管理',
    'order.view' => '订单查看',
    'order.edit' => '订单修改',
    'order.refund' => '订单退款',
    'order.delete' => '订单删除',
    'user.view' => '商户查看',
    'user.edit' => '商户修改',
    'user.delete' => '商户删除',
    'finance.view' => '资金查看',
    'finance.transfer' => '转账管理',
    'finance.settle' => '结算管理',
    'log.view' => '日志查看',
    'data.export' => '数据导出',
    'data.clean' => '数据清理',
];
$stmt = $DB->prepare("INSERT IGNORE INTO `{$pre}_permissions` (`permission_key`, `description`) VALUES (?, ?)");
foreach ($permissions as $key => $desc) {
    $stmt->execute([$key, $desc]);
}
echo "   Done. " . count($permissions) . " permissions.\n";

// 7. 插入默认角色
echo "7. Inserting default roles...\n";
$roles = [
    'super_admin' => '超级管理员（所有权限）',
    'admin' => '普通管理员（除管理员管理外）',
    'finance' => '财务管理员（资金/转账/结算）',
    'support' => '客服（订单/商户/日志查看）',
];
$stmt = $DB->prepare("INSERT IGNORE INTO `{$pre}_roles` (`name`, `description`) VALUES (?, ?)");
foreach ($roles as $name => $desc) {
    $stmt->execute([$name, $desc]);
}
echo "   Done. " . count($roles) . " roles.\n";

// 8. 为角色分配权限
echo "8. Assigning permissions to roles...\n";
$role_perms = [
    'super_admin' => array_keys($permissions),
    'admin' => array_values(array_filter(array_keys($permissions), fn($k) => $k !== 'system.admin')),
    'finance' => ['finance.view', 'finance.transfer', 'finance.settle', 'order.view', 'user.view', 'log.view'],
    'support' => ['order.view', 'user.view', 'log.view'],
];
foreach ($role_perms as $role_name => $perm_keys) {
    $role_id = $DB->query("SELECT id FROM `{$pre}_roles` WHERE name = " . $DB->quote($role_name))->fetchColumn();
    if ($role_id) {
        $stmt = $DB->prepare("INSERT IGNORE INTO `{$pre}_role_permissions` (`role_id`, `permission_key`) VALUES (?, ?)");
        foreach ($perm_keys as $perm_key) {
            $stmt->execute([$role_id, $perm_key]);
        }
    }
}
echo "   Done.\n";

// 9. 迁移现有管理员到 admins 表
echo "9. Migrating existing admin to admins table...\n";
try {
    $admin_user = $DB->query("SELECT v FROM `{$pre}_config` WHERE k = 'admin_user'")->fetchColumn();
    $admin_pwd = $DB->query("SELECT v FROM `{$pre}_config` WHERE k = 'admin_pwd'")->fetchColumn();
} catch (Exception $e) {
    $admin_user = null;
    $admin_pwd = null;
}

if ($admin_user && $admin_pwd && $admin_pwd !== '!INSTALL_REQUIRED!') {
    $existing = $DB->query("SELECT id FROM `{$pre}_admins` WHERE username = " . $DB->quote($admin_user))->fetchColumn();
    if (!$existing) {
        $stmt = $DB->prepare("INSERT INTO `{$pre}_admins` (`username`, `password_hash`, `status`) VALUES (?, ?, 1)");
        $stmt->execute([$admin_user, $admin_pwd]);
        $admin_id = $DB->lastInsertId();
        $super_admin_role_id = $DB->query("SELECT id FROM `{$pre}_roles` WHERE name = 'super_admin'")->fetchColumn();
        if ($admin_id && $super_admin_role_id) {
            $stmt = $DB->prepare("INSERT INTO `{$pre}_admin_roles` (`admin_id`, `role_id`) VALUES (?, ?)");
            $stmt->execute([$admin_id, $super_admin_role_id]);
        }
        echo "   Done. Admin ID: $admin_id, Role: super_admin\n";
    } else {
        echo "   Admin already exists, skipping.\n";
    }
} else {
    echo "   No admin configured, skipping migration.\n";
}

echo "\n=== RBAC Migration Complete ===\n";
echo "Tables: {$pre}_admins, {$pre}_roles, {$pre}_permissions, {$pre}_admin_roles, {$pre}_role_permissions\n";
echo "Roles: super_admin, admin, finance, support\n";
echo "Permissions: " . count($permissions) . "\n";
