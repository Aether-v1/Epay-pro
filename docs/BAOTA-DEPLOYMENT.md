# Epay 宝塔面板部署教程（安全加固版）

> 适用版本：security/batch-a-rebuild 分支（HEAD 040223c 及之后）
> 包含 Batch A-E3-A 全部安全修复：管理员认证、商户登录、SQL注入、退款资金安全、XSS、CSRF、SSRF、RBAC、转账资金安全等。

---

## 一、环境准备

### 1.1 服务器要求

| 项目 | 最低配置 | 推荐配置 |
|------|----------|----------|
| CPU | 1 核 | 2 核+ |
| 内存 | 1 GB | 2 GB+ |
| 磁盘 | 20 GB | 40 GB+ SSD |
| 系统 | CentOS 7/8、Ubuntu 20.04/22.04、Debian 10/11 | Ubuntu 22.04 LTS |
| 带宽 | 1 Mbps | 5 Mbps+ |

### 1.2 安装宝塔面板

**CentOS：**
```bash
yum install -y wget && wget -O install.sh https://download.bt.cn/install/install_6.0.sh && sh install.sh ed8484bec
```

**Ubuntu/Debian：**
```bash
wget -O install.sh https://download.bt.cn/install/install-ubuntu_6.0.sh && sudo bash install.sh ed8484bec
```

安装完成后记录：
- 面板地址（http://服务器IP:8888/xxxx）
- 用户名
- 密码

### 1.3 安装 LNMP 环境

登录宝塔面板 → 软件商店 → 安装以下组件：

| 组件 | 版本 | 备注 |
|------|------|------|
| Nginx | 1.22+ | 推荐 1.24 |
| MySQL | 5.7 或 8.0 | 推荐 8.0 |
| PHP | 8.1 | 必须 7.4+，推荐 8.1/8.2 |
| phpMyAdmin | 最新 | 可选，用于数据库管理 |

**PHP 必须安装的扩展：**
- pdo_mysql
- mysqli
- gd
- curl
- openssl
- mbstring
- json
- fileinfo

安装路径：软件商店 → 已安装 → PHP-8.1 → 设置 → 安装扩展，勾选以上扩展并安装。

---

## 二、创建网站和数据库

### 2.1 创建网站

宝塔面板 → 网站 → 添加站点：

| 配置项 | 值 |
|--------|-----|
| 域名 | 你的域名（如 pay.example.com） |
| 根目录 | /www/wwwroot/pay.example.com |
| FTP | 不创建 |
| 数据库 | MySQL，UTF8MB4 |
| 数据库名 | 自动生成或自定义（如 epay_db） |
| 用户名 | 自动生成或自定义（如 epay_user） |
| 密码 | 强密码（记录下来） |
| PHP版本 | PHP-8.1 |

创建后记录数据库信息：
```
数据库名：epay_db
用户名：epay_user
密码：xxxxxxxx
地址：localhost
端口：3306
表前缀：pay_
```

### 2.2 数据库安全配置

宝塔面板 → 数据库 → 对应数据库 → 权限：
- 访问权限：**仅本地服务器**（不要开放远程访问）

phpMyAdmin → SQL，执行以下安全配置（可选）：
```sql
-- 确保使用最小权限用户（宝塔创建的用户默认只有该库权限，无需额外操作）
-- 验证字符集
SHOW VARIABLES LIKE 'character_set_database';
SHOW VARIABLES LIKE 'collation_database';
```

---

## 三、上传代码

### 3.1 获取代码

**方式一：Git 克隆（推荐）**

宝塔面板 → 终端，执行：
```bash
cd /www/wwwroot/
git clone https://github.com/maajiko/Epay.git pay.example.com
cd pay.example.com
git checkout security/batch-a-rebuild
```

**方式二：本地打包上传**

1. 本地执行：`git archive --format=zip --output=epay.zip security/batch-a-rebuild`
2. 宝塔面板 → 文件 → 上传 epay.zip 到 /www/wwwroot/
3. 解压到 /www/wwwroot/pay.example.com/

### 3.2 清理不需要的文件

生产环境必须删除：
```bash
cd /www/wwwroot/pay.example.com

# 删除测试目录
rm -rf tests/

# 删除安装目录（安装完成后再删，见下文）
# rm -rf install/

# 删除文档和部署脚本（可选，建议保留 BAOTA-DEPLOYMENT.md）
rm -rf deploy/ .gitignore .gitattributes

# 删除 Git 目录（如果不通过 Git 更新）
rm -rf .git/

# 删除示例配置（安装向导会自动生成 config.php）
rm -f config.php.example config.php.local .env.testing.example
```

### 3.3 配置目录权限

```bash
cd /www/wwwroot/pay.example.com

# 设置所有者
chown -R www:www .

# 目录权限 755，文件权限 644
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# 可写目录
chmod 755 assets/files/ logs/
```

---

## 四、Web 安装向导（推荐）

安全加固版提供了完整的 Web 安装向导，**自动完成数据库配置、管理员账号设置和安全数据库迁移**。

### 4.1 访问安装页面

浏览器访问：`https://你的域名/install/`

### 4.2 步骤一：环境检测

安装向导会自动检测：
- PHP 版本 >= 7.4
- PDO_MYSQL 组件
- CURL 组件
- password_hash 支持
- 主目录写入权限

全部通过后点击「下一步」。

### 4.3 步骤二：数据库与管理员配置

填写以下信息：

**数据库配置：**
| 字段 | 值 |
|------|-----|
| 数据库地址 | localhost |
| 数据库端口 | 3306 |
| 数据库用户名 | 宝塔创建的数据库用户名 |
| 数据库密码 | 宝塔创建的数据库密码 |
| 数据库名称 | 宝塔创建的数据库名 |
| 数据表前缀 | pay（保持默认，不要修改） |

**管理员账号配置：**
| 字段 | 要求 |
|------|------|
| 管理员用户名 | 建议不要使用 admin |
| 管理员密码 | 至少8位，必须包含大小写字母和数字 |
| 确认密码 | 与上面一致 |

> ⚠️ 安全版已移除默认 admin/123456，必须设置强密码。密码会使用 password_hash() 加密存储。

点击「确认无误，下一步」。

### 4.4 步骤三：数据库配置保存

系统会：
1. 测试数据库连接
2. 自动生成 config.php
3. 保存管理员账号密码到 Session（安全传递）

如果检测到已安装过，会提示选择：
- 跳过安装数据表（保留现有数据）
- 强制全新安装（清空所有数据）

### 4.5 步骤四：安装数据表与安全迁移

系统会自动执行：
1. 执行 install.sql 创建基础表
2. 设置管理员账号和密码（password_hash 加密）
3. **自动执行 Batch A 迁移**（frozen_money、refund_reserved、退款唯一约束）
4. **自动执行 RBAC 迁移**（管理员角色权限表）
5. **自动执行 Transfer Safety 迁移**（转账状态机、唯一约束）
6. 创建 install.lock 锁定安装

所有迁移使用 INFORMATION_SCHEMA 检查，**幂等可重复执行**。

### 4.6 步骤五：安装完成

安装完成页面会显示：
- 成功执行 SQL 语句数量
- 安全加固版已自动执行的迁移列表
- 后台地址 /admin/
- 提示使用安装时设置的管理员账号密码登录

### 4.7 删除安装目录

安装完成后，**必须删除 install 目录**：
```bash
rm -rf /www/wwwroot/pay.example.com/install/
```

> ⚠️ 如果不删除，存在被恶意重新安装的风险。

---

## 五、手动安装（备选）

如果 Web 安装向导无法使用，可以手动安装：

### 5.1 手动配置 config.php

```bash
cd /www/wwwroot/pay.example.com
cp config.php.example config.php
```

编辑 config.php，填写数据库信息。

### 5.2 手动导入数据库

```bash
mysql -u 用户名 -p 数据库名 < install/install.sql
```

### 5.3 手动设置管理员密码

```bash
# 生成密码哈希
php -r "echo password_hash('你的强密码', PASSWORD_DEFAULT), PHP_EOL;"

# 更新数据库
mysql -u 用户名 -p 数据库名 -e "
UPDATE pay_config SET v='管理员用户名' WHERE k='admin_user';
UPDATE pay_config SET v='上面生成的哈希' WHERE k='admin_pwd';
"
```

### 5.4 手动执行迁移脚本

```bash
cd /www/wwwroot/pay.example.com

# 设置环境变量
export DB_HOST=localhost DB_PORT=3306 DB_USER=用户名 DB_PASS=密码 DB_NAME=数据库名 DB_PREFIX=pay

# 执行迁移（仅 CLI）
php install/migrate_batch_a.php
php install/migrate_rbac.php
php install/migrate_transfer_safety.php
```

### 5.5 创建安装锁

```bash
touch install/install.lock
rm -rf install/
```

---

## 五、Nginx 安全配置

### 5.1 宝塔网站配置

宝塔面板 → 网站 → 对应站点 → 设置 → 配置文件，在 `server { ... }` 块内添加以下内容。

**完整配置模板：**

```nginx
server {
    listen 80;
    server_name pay.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pay.example.com;

    # SSL 证书（宝塔自动配置，无需手动修改）
    ssl_certificate /www/server/panel/vhost/cert/pay.example.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/pay.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains" always;

    # 安全响应头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    root /www/wwwroot/pay.example.com;
    index index.php index.html;
    charset utf-8;

    # ========== 安全：禁止访问敏感文件和目录 ==========

    # 禁止访问 .git
    location ~ /\.git { deny all; return 404; }

    # 禁止访问 .env
    location ~ /\.env { deny all; return 404; }

    # 禁止访问配置文件
    location ~ /(config|config\.php|config\.inc\.php)$ { deny all; return 404; }

    # 禁止访问安装目录（已删除，作为双重防护）
    location ~ /install/ { deny all; return 404; }

    # 禁止访问 SQL/备份文件
    location ~* \.(sql|sql\.gz|dump|bak|old|orig)$ { deny all; return 404; }

    # 禁止访问日志目录
    location ~ /logs/ { deny all; return 404; }

    # 禁止访问测试目录
    location ~ /tests/ { deny all; return 404; }

    # 禁止访问备份/临时目录
    location ~ /(backup|backups|tmp|temp)/ { deny all; return 404; }

    # 禁止访问 composer/package 文件
    location ~ /(composer\.json|composer\.lock|package\.json|package-lock\.json)$ { deny all; return 404; }

    # 禁止访问所有隐藏文件
    location ~ /\. { deny all; return 404; }

    # ========== 上传目录安全：禁止执行 PHP ==========
    location ~* /assets/files/.*\.(php|php5|php7|phtml|phar|pht|phps|cgi|pl|py|sh|asp|aspx)$ {
        deny all;
        return 404;
    }

    # ========== PHP 处理 ==========
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/tmp/php-cgi-81.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # ========== 静态文件缓存 ==========
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|eot|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    client_max_body_size 10M;
}
```

> ⚠️ 注意：`fastcgi_pass` 的 socket 路径需要与宝塔实际一致。查看方式：宝塔 → 软件商店 → PHP-8.1 → 设置 → 查看 socket 路径（通常是 `/tmp/php-cgi-81.sock`）。

### 5.2 伪静态设置

宝塔面板 → 网站 → 设置 → 伪静态，保持为空即可（Epay 不需要 URL 重写）。

---

## 六、PHP 安全配置

宝塔面板 → 软件商店 → 已安装 → PHP-8.1 → 设置 → 配置修改，修改以下参数：

```ini
; 安全配置
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /www/wwwlogs/pay.example.com.php.error.log

; Session 安全
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = "Lax"
session.use_strict_mode = On
session.gc_maxlifetime = 1440

; 上传限制
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
max_input_time = 60
memory_limit = 256M

; 禁用危险函数（根据业务需要调整）
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,parse_ini_file,show_source,phpinfo
```

> ⚠️ 注意：`proc_open` 和 `popen` 如果禁用，可能影响部分支付插件的异步处理。如果业务不需要，可以禁用；如果需要，保留但确保没有用户输入直接传入。

修改后点击「保存」→「重启 PHP」。

---

## 七、目录权限设置

宝塔面板 → 终端：

```bash
cd /www/wwwroot/pay.example.com

# 设置所有者（宝塔默认运行用户是 www）
chown -R www:www .

# 目录权限 755
find . -type d -exec chmod 755 {} \;

# 文件权限 644
find . -type f -exec chmod 644 {} \;

# 敏感配置文件 640（只有所有者和组成员可读）
chmod 640 config.php

# 可写目录
chmod 755 assets/files/
chmod 755 logs/
chmod 755 template/  # 如果模板需要编译

# 确保没有 777 权限
find . -type d -perm 777 -exec chmod 755 {} \;
find . -type f -perm 777 -exec chmod 644 {} \;
```

验证：
```bash
ls -la config.php
# 应显示：-rw-r----- 1 www www ... config.php
```

---

## 八、SSL 证书配置

### 8.1 申请 Let's Encrypt 免费证书

宝塔面板 → 网站 → 设置 → SSL → Let's Encrypt：
1. 勾选域名
2. 勾选「自动续期」
3. 点击「申请」

### 8.2 强制 HTTPS

SSL 页面开启「强制 HTTPS」。

### 8.3 验证

浏览器访问 `https://你的域名`，确认：
- 地址栏显示锁标志
- 证书有效
- HTTP 自动跳转到 HTTPS

---

## 九、定时任务配置

Epay 需要定时任务处理订单超时、结算等。

宝塔面板 → 计划任务 → 添加任务：

| 任务名 | 类型 | 执行周期 | 脚本内容 |
|--------|------|----------|----------|
| Epay 订单监控 | Shell 脚本 | 每分钟 | `curl -s https://你的域名/cron.php > /dev/null 2>&1` |
| Epay 数据库备份 | Shell 脚本 | 每天 03:00 | 见下方备份脚本 |
| Epay 日志清理 | Shell 脚本 | 每周日 04:00 | `find /www/wwwlogs/ -name "*.log" -mtime +30 -delete` |

**数据库备份脚本：**
```bash
#!/bin/bash
BACKUP_DIR=/www/backup/epay
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u epay_user -p'你的密码' epay_db | gzip > $BACKUP_DIR/epay_db_$DATE.sql.gz

# 保留最近30天
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

> ⚠️ 备份文件不要放在 Web 根目录下！

---

## 十、后台配置

### 10.1 登录管理后台

访问：`https://你的域名/admin/`

使用安装时创建的管理员账号登录。

### 10.2 系统配置

后台 → 系统设置：

| 配置项 | 建议值 |
|--------|--------|
| 网站名称 | 你的支付平台名称 |
| 网站域名 | https://你的域名（带 https） |
| 管理员邮箱 | 你的邮箱 |
| 订单超时时间 | 600（秒） |
| 提现手续费 | 根据业务设置 |
| 转账手续费 | 根据业务设置 |

### 10.3 支付通道配置

后台 → 支付通道 → 添加通道：

1. 选择支付插件（如支付宝、微信支付等）
2. 填写商户号、密钥等
3. **回调地址（notify_url）**：`https://你的域名/notify.php`（或插件指定的地址）
4. **同步返回地址（return_url）**：`https://你的域名/return.php`
5. 保存后进行沙箱测试

> ⚠️ 安全版已对 appurl 增加 SSRF 防护，确保回调地址使用合法域名。

### 10.4 管理员权限（RBAC）

后台 → 管理员管理 → 角色管理：
- 创建「财务」「运营」「客服」等角色
- 分配对应权限
- 创建管理员账号并分配角色

> 安全版已实现 RBAC，不再使用单管理员模型。

---

## 十一、安全加固检查清单

部署完成后，逐项检查：

### 11.1 Web 安全

- [ ] HTTP 自动跳转 HTTPS
- [ ] SSL 证书有效，TLS 1.2+
- [ ] HSTS 已启用
- [ ] 安全响应头已设置（X-Frame-Options 等）
- [ ] `.git` 目录无法访问（访问 https://域名/.git/ 返回 404）
- [ ] `.env` 文件无法访问
- [ ] `config.php` 无法直接访问
- [ ] `install/` 目录已删除且无法访问
- [ ] `tests/` 目录已删除
- [ ] `logs/` 目录无法访问
- [ ] 上传目录 `assets/files/` 无法执行 PHP

### 11.2 PHP 安全

- [ ] `expose_php = Off`
- [ ] `display_errors = Off`
- [ ] Session Cookie 设置 HttpOnly、Secure、SameSite
- [ ] 危险函数已禁用
- [ ] PHP 版本 7.4+

### 11.3 数据库安全

- [ ] MySQL 只监听 127.0.0.1（宝塔默认）
- [ ] 使用最小权限数据库用户（非 root）
- [ ] 数据库密码为强密码
- [ ] 字符集 utf8mb4
- [ ] 已执行 migrate_batch_a.php
- [ ] 已执行 migrate_rbac.php
- [ ] 已执行 migrate_transfer_safety.php
- [ ] pay_transfer 表有 resolution、request_hash 字段
- [ ] pay_transfer 表有 uk_uid_outbizno 唯一约束
- [ ] pay_user 表有 frozen_money 字段

### 11.4 目录权限

- [ ] 无 777 权限
- [ ] config.php 权限 640
- [ ] 文件所有者为 www
- [ ] 可写目录仅限 assets/files、logs、template

### 11.5 应用安全

- [ ] 管理员账号非 admin，密码为强密码
- [ ] 商户登录有 CSRF 防护
- [ ] 支付回调签名验证正常
- [ ] 退款功能有资金冻结保护
- [ ] 转账功能有资金冻结保护
- [ ] 管理员操作有审计日志

### 11.6 防火墙

- [ ] 宝塔防火墙只开放 22、80、443
- [ ] MySQL 3306 不对外暴露
- [ ] SSH 端口建议修改（非 22）

---

## 十二、功能验证

### 12.1 基础功能

1. **商户注册/登录**：测试商户注册和登录
2. **创建订单**：商户创建支付订单
3. **支付流程**：完成一笔支付（沙箱环境）
4. **支付回调**：验证回调签名和订单状态更新
5. **退款**：测试退款流程，验证资金冻结和释放
6. **转账/提现**：测试转账，验证资金冻结和状态机

### 12.2 安全测试（可选）

1. **SQL 注入**：在搜索框输入 `' OR 1=1 --`，应返回正常结果或空
2. **XSS**：输入 `<script>alert(1)</script>`，应被转义
3. **CSRF**：无 Token 的 POST 请求应被拒绝
4. **越权**：商户 A 尝试访问商户 B 的订单，应返回 403/404
5. **并发回调**：同时发送多个相同回调，资金只入账一次

---

## 十三、备份与恢复

### 13.1 手动备份

```bash
# 数据库备份
mysqldump -u epay_user -p epay_db | gzip > /www/backup/epay_db_$(date +%Y%m%d).sql.gz

# 代码备份（排除日志和上传文件）
tar -czf /www/backup/epay_code_$(date +%Y%m%d).tar.gz \
    --exclude='assets/files/*' \
    --exclude='logs/*' \
    --exclude='.git' \
    /www/wwwroot/pay.example.com/
```

### 13.2 恢复

```bash
# 恢复数据库
gunzip < /www/backup/epay_db_20240101.sql.gz | mysql -u epay_user -p epay_db

# 恢复代码
tar -xzf /www/backup/epay_code_20240101.tar.gz -C /
```

---

## 十四、常见问题

### Q1: 访问网站显示 "Access denied" 或 403

检查：
- Nginx 配置是否正确
- 根目录路径是否正确
- 文件权限是否正确（所有者 www）
- SELinux 是否拦截（CentOS 执行 `setenforce 0` 临时关闭）

### Q2: 数据库连接失败

检查：
- config.php 中的数据库信息是否正确
- 数据库用户是否有该库的权限
- MySQL 是否在运行
- 宝塔面板 → 数据库 → 检查状态

### Q3: 支付回调失败

检查：
- 回调地址是否可公网访问
- 防火墙是否放行 80/443
- 支付通道配置的密钥是否正确
- Nginx 日志：`/www/wwwlogs/pay.example.com.error.log`

### Q4: 上传文件后无法访问

检查：
- `assets/files/` 目录权限是否为 755
- 所有者是否为 www
- Nginx 配置是否正确

### Q5: 转账功能异常

检查：
- 是否执行了 `migrate_transfer_safety.php`
- pay_transfer 表是否有 resolution 字段
- 商户余额是否充足（含冻结资金）
- 日志：`logs/security_error.log`

### Q6: 管理员登录后被踢出

检查：
- PHP session 配置是否正确
- `session.cookie_secure = On` 需要 HTTPS 环境
- 浏览器是否禁用 Cookie

---

## 十五、升级更新

### 15.1 Git 方式更新

```bash
cd /www/wwwroot/pay.example.com
git pull origin security/batch-a-rebuild

# 检查是否有新的迁移脚本
ls install/migrate_*.php

# 执行新的迁移（按文件名顺序）
php install/migrate_xxx.php

# 更新权限
chown -R www:www .
chmod 640 config.php

# 清理缓存（如有）
rm -rf template/*_cache.php
```

### 15.2 手动更新

1. 备份数据库和代码
2. 上传新代码覆盖（保留 config.php）
3. 执行新的迁移脚本
4. 更新目录权限
5. 测试功能

---

## 十六、生产环境注意事项

1. **不要在生产环境运行测试**：`tests/` 目录必须删除
2. **不要在生产环境开启 debug**：`display_errors = Off`
3. **定期更新**：关注安全补丁，及时更新
4. **监控异常**：关注支付回调失败率、异常退款、大额转账
5. **定期审计**：检查管理员操作日志、资金流水
6. **不要用 root 运行 PHP**：宝塔默认使用 www 用户
7. **不要开放数据库远程访问**：保持 localhost
8. **不要使用弱密码**：数据库、管理员、SSH 都使用强密码
9. **不要在 config.php 中提交真实密码**：config.php 已加入 .gitignore
10. **转账/代付功能谨慎启用**：16 个支持转账的插件中，dinpay/zhangyishou 无查询接口，建议禁用直至人工对账流程建立

---

## 附录：关键文件说明

| 文件 | 说明 |
|------|------|
| config.php | 数据库配置（权限 640，不提交 Git） |
| install/install.sql | 初始数据库结构 |
| install/migrate_batch_a.php | Batch A 安全迁移（退款资金冻结） |
| install/migrate_rbac.php | RBAC 迁移（管理员角色权限） |
| install/migrate_transfer_safety.php | 转账安全迁移（状态机、唯一约束） |
| includes/functions.php | 核心安全函数（CSRF、SSRF、资金冻结等） |
| includes/lib/Transfer.php | 转账核心（三阶段模型、六态状态机） |
| includes/lib/Order.php | 退款核心（五态状态机、三阶段事务） |
| admin/ | 管理后台 |
| user/ | 商户中心 |
| plugins/ | 支付插件（63个） |
| assets/files/ | 上传目录（禁止执行 PHP） |
| logs/ | 日志目录（禁止 Web 访问） |

---

**部署完成后，请务必运行安全检查清单，并进行完整的功能测试。如有问题，查看日志文件：**
- Nginx 错误日志：`/www/wwwlogs/pay.example.com.error.log`
- PHP 错误日志：`/www/wwwlogs/pay.example.com.php.error.log`
- 应用安全日志：`logs/security_error.log`
