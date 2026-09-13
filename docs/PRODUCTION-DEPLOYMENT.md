# Epay 生产环境部署与安全加固指南

## 1. 系统要求

- OS: Ubuntu 20.04/22.04 LTS 或 CentOS 7/8
- Web Server: Nginx 1.18+ 或 Apache 2.4+
- PHP: 8.1+（推荐 8.2）
- PHP 扩展: pdo_mysql, mysqli, gd, curl, openssl, mbstring, json
- MySQL: 5.7+ 或 8.0+
- 内存: 最低 1GB，推荐 2GB+

## 2. 目录结构

```
/var/www/epay/
├── admin/              # 管理后台
├── assets/             # 静态资源
│   └── files/          # 上传文件（禁止执行 PHP）
├── includes/           # 核心代码
├── install/            # 安装目录（安装后删除）
├── logs/               # 日志目录（禁止 Web 访问）
├── plugins/            # 支付插件
├── template/           # 模板
├── tests/              # 测试目录（生产删除）
├── user/               # 商户中心
├── api.php             # 商户 API
├── config.php          # 数据库配置（权限 640）
├── index.php           # 入口
└── .htaccess           # Apache 安全配置
```

## 3. Nginx 配置

使用 `deploy/nginx/epay-production.conf` 作为模板，修改以下内容：
- `server_name`: 你的域名
- `ssl_certificate` / `ssl_certificate_key`: SSL 证书路径
- `root`: 网站根目录
- `fastcgi_pass`: PHP-FPM socket 路径

## 4. PHP-FPM 配置

编辑 `php.ini` 或 `www.conf`：

```ini
; 安全配置
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Session 安全
session.cookie_httponly = On
session.cookie_secure = On  ; HTTPS 环境
session.cookie_samesite = "Lax"
session.use_strict_mode = On

; 上传限制
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
memory_limit = 256M

; 禁用危险函数（根据业务需要调整）
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

## 5. MySQL 安全配置

```ini
[mysqld]
# 只监听本地
bind-address = 127.0.0.1
skip-networking = 0  ; 如果需要远程连接则设为 0

# 禁用 DNS 解析
skip-name-resolve

# 安全模式
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION

# 字符集
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

创建专用数据库用户（最小权限）：
```sql
CREATE USER 'epay_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON epay_db.* TO 'epay_user'@'localhost';
FLUSH PRIVILEGES;
```

## 6. 目录权限

```bash
# 项目目录
chown -R www-data:www-data /var/www/epay
find /var/www/epay -type d -exec chmod 755 {} \;
find /var/www/epay -type f -exec chmod 644 {} \;

# 敏感配置文件
chmod 640 /var/www/epay/config.php
chown www-data:www-data /var/www/epay/config.php

# 可写目录
chmod 755 /var/www/epay/assets/files
chmod 755 /var/www/epay/logs
chmod 755 /var/www/epay/template  ; 如果需要编译模板

# 安装后删除安装目录
rm -rf /var/www/epay/install

# 生产环境删除测试目录
rm -rf /var/www/epay/tests
```

## 7. 防火墙配置

```bash
# UFW
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable

# 禁止 MySQL 公网访问
ufw deny 3306/tcp
ufw deny 6379/tcp  # Redis
```

## 8. SSL/TLS 配置

使用 Let's Encrypt 或其他可信证书：
```bash
certbot --nginx -d your-domain.com
```

确保：
- TLS 1.2+
- HSTS 启用
- 证书自动续期

## 9. 备份策略

- 数据库：每日全量备份，保留 30 天
- 代码：使用 Git 版本控制
- 备份文件：存储在 Web Root 之外，加密
- 定期恢复测试

## 10. 监控与日志

- Nginx 访问日志和错误日志
- PHP-FPM 慢日志
- MySQL 慢查询日志
- 系统资源监控（CPU、内存、磁盘）
- 安全事件监控（异常登录、失败支付回调）

## 11. 生产环境检查清单

- [ ] Nginx 配置禁止访问 .git、.env、config.php、install、logs、tests
- [ ] 上传目录禁止执行 PHP
- [ ] PHP expose_php = Off
- [ ] PHP display_errors = Off
- [ ] Session Cookie 设置 HttpOnly、Secure、SameSite
- [ ] MySQL 只监听 127.0.0.1
- [ ] MySQL 使用最小权限账号
- [ ] 防火墙只开放 22、80、443
- [ ] HTTPS 启用，TLS 1.2+
- [ ] HSTS 启用
- [ ] 目录权限正确（无 777）
- [ ] config.php 权限 640
- [ ] install 目录已删除
- [ ] tests 目录已删除
- [ ] 备份策略已配置
- [ ] 监控已配置
- [ ] 安全响应头已设置（X-Frame-Options、X-Content-Type-Options 等）
- [ ] 支付回调签名验证已测试
- [ ] 退款资金安全已测试
- [ ] 管理员认证已测试
- [ ] 商户登录安全已测试
- [ ] SQL 注入防护已测试
- [ ] XSS 防护已测试
- [ ] CSRF 防护已测试
- [ ] 并发支付回调已测试
- [ ] 并发退款已测试

## 12. 应急响应

- 定期备份数据库和代码
- 准备回滚方案
- 监控异常支付和退款
- 定期安全审计
- 保持依赖更新
