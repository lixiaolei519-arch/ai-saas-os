# 宝塔面板部署交付包

适用版本：`v1.0.0`

稳定提交：`c69377b Release v1.0.0 minimum commercial launch`

本文档用于中国大陆服务器上的宝塔面板部署。仅覆盖部署、配置、权限、初始化、队列、定时任务和上线后 smoke test，不包含任何新业务功能。

## 1. 宝塔环境要求

宝塔软件商店安装：

- Nginx
- PHP 8.2 或更高，推荐 PHP 8.3
- MySQL 8.0
- Composer
- Redis 可选
- Supervisor 管理器可选但推荐用于队列 worker

PHP 扩展要求：

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `zip`

PHP 禁用函数检查：

- 不应禁用 `proc_open`
- 不应禁用 `proc_get_status`
- 不应禁用 `shell_exec`，如果需要在服务器上执行 Composer 或 Artisan 维护脚本

## 2. 目录结构

推荐部署目录：

```text
/www/wwwroot/ai-saas-os
```

宝塔站点根目录必须设置为：

```text
/www/wwwroot/ai-saas-os/public
```

不要把站点根目录设置为项目根目录，否则 `.env`、源码和配置文件存在暴露风险。

## 3. 代码上传

上传或拉取代码到：

```bash
cd /www/wwwroot
git clone <your-repository-url> ai-saas-os
cd /www/wwwroot/ai-saas-os
git checkout v1.0.0
```

如果使用压缩包上传，解压后确认 `artisan`、`composer.json`、`public/index.php` 位于项目根目录内。

## 4. 生产环境配置

复制生产示例配置：

```bash
cp .env.production.example .env
```

生成 Laravel APP_KEY：

```bash
php artisan key:generate --force
```

必须修改：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
DB_DATABASE=ai_saas_os
DB_USERNAME=ai_saas_os
DB_PASSWORD=replace-with-strong-password
WECHAT_PAY_WEBHOOK_SECRET=replace-with-real-wechat-webhook-secret
ALIPAY_WEBHOOK_SECRET=replace-with-real-alipay-webhook-secret
ADMIN_DEMO_PASSWORD=replace-with-strong-admin-password
CUSTOMER_DEMO_PASSWORD=replace-with-strong-customer-password
```

真实微信/支付宝密钥未准备好时，只允许在测试或预发布环境继续使用模拟 HMAC 回调密钥。生产流量前必须替换。

## 5. 宝塔 Nginx 伪静态规则

宝塔面板路径：

```text
网站 -> 目标站点 -> 设置 -> 伪静态
```

填入：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\. {
    deny all;
}

location ~* \.(env|log|sql|bak|backup|ini|conf)$ {
    deny all;
}

location = /favicon.ico {
    log_not_found off;
    access_log off;
}

location = /robots.txt {
    log_not_found off;
    access_log off;
}
```

同一份规则已生成在：

```text
BT_NGINX_REWRITE.conf
```

## 6. Laravel 权限设置

在宝塔终端执行：

```bash
cd /www/wwwroot/ai-saas-os
chown -R www:www /www/wwwroot/ai-saas-os
find /www/wwwroot/ai-saas-os -type f -exec chmod 644 {} \;
find /www/wwwroot/ai-saas-os -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
```

确认 Web 用户可写：

```bash
sudo -u www test -w storage && echo "storage writable"
sudo -u www test -w bootstrap/cache && echo "bootstrap cache writable"
```

如果系统没有 `sudo`，可使用：

```bash
su -s /bin/bash www -c "test -w storage && echo storage writable"
su -s /bin/bash www -c "test -w bootstrap/cache && echo bootstrap cache writable"
```

## 7. Composer 安装

生产安装：

```bash
cd /www/wwwroot/ai-saas-os
composer install --no-dev --optimize-autoloader
```

如果宝塔默认 PHP 版本不是 8.2+，使用完整 PHP 路径执行 Composer，例如：

```bash
/www/server/php/83/bin/php /usr/bin/composer install --no-dev --optimize-autoloader
```

## 8. 数据库初始化命令

在宝塔面板创建：

- 数据库：`ai_saas_os`
- 用户：`ai_saas_os`
- 密码：强密码
- 字符集：`utf8mb4`

然后执行：

```bash
cd /www/wwwroot/ai-saas-os
php artisan migrate --force
php artisan db:seed --force
```

如果不需要演示账号，可跳过 `db:seed`。首次商业演示环境建议保留种子数据并立即修改演示密码。

## 9. 缓存和优化命令

```bash
cd /www/wwwroot/ai-saas-os
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

每次修改 `.env` 后必须重新执行：

```bash
php artisan config:cache
php artisan queue:restart
```

## 10. 队列 worker 启动说明

当前版本默认使用 database queue：

```env
QUEUE_CONNECTION=database
```

宝塔推荐使用 Supervisor 管理器，新建进程：

```bash
cd /www/wwwroot/ai-saas-os && php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

建议配置：

- 进程数量：`1`
- 运行用户：`www`
- 自动重启：开启
- 启动目录：`/www/wwwroot/ai-saas-os`

部署后重启队列：

```bash
php artisan queue:restart
```

如后续启用 Redis：

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

对应 worker：

```bash
php artisan queue:work redis --sleep=3 --tries=3 --timeout=90
```

## 11. 定时任务 cron 说明

宝塔面板路径：

```text
计划任务 -> 添加任务 -> Shell 脚本
```

执行周期：

```text
每 1 分钟
```

脚本内容：

```bash
cd /www/wwwroot/ai-saas-os && php artisan schedule:run >> /dev/null 2>&1
```

如果 PHP 命令不是 8.2+，使用：

```bash
cd /www/wwwroot/ai-saas-os && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 12. 上线前检查命令

```bash
cd /www/wwwroot/ai-saas-os
composer audit --no-interaction
php artisan production:check
php artisan security:prelaunch
```

必须全部通过后再切生产流量。

## 13. 上线后 Smoke Test

按顺序检查：

1. 健康检查：

```bash
curl -s https://your-domain.example/health
```

`/health` 是生产上线探活接口，用于确认 Nginx 已正确转发到 Laravel，且应用路由可以正常响应。期望返回：

```json
{
  "status": "ok",
  "app": "AI SaaS OS",
  "environment": "production"
}
```

如果 `/health` 返回 Nginx 404，优先检查：

- 宝塔站点根目录是否设置为 `/www/wwwroot/ai-saas-os/public`
- 宝塔伪静态是否已配置 `try_files $uri $uri/ /index.php?$query_string;`
- 修改路由或 `.env` 后是否执行了 `php artisan route:cache` 和 `php artisan config:cache`

2. 首页可访问：

```bash
curl -I https://your-domain.example/
```

期望 HTTP 状态为 `200` 或 `302`。

3. 管理员登录接口：

```bash
curl -s -X POST https://your-domain.example/api/v1/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@your-domain.example","password":"replace-with-strong-admin-password"}'
```

期望返回 `data.token`。

4. 普通用户登录接口：

```bash
curl -s -X POST https://your-domain.example/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@your-domain.example","password":"replace-with-strong-customer-password"}'
```

期望返回 `data.token`。

5. 后台统计接口：

```bash
curl -s https://your-domain.example/api/v1/admin/stats \
  -H "Authorization: Bearer <admin-token>"
```

期望返回用户数、租户数、订单数等统计字段。

6. 客户门户接口：

```bash
curl -s https://your-domain.example/api/v1/portal/orders \
  -H "Authorization: Bearer <customer-token>"
```

期望返回当前客户租户范围内订单列表。

7. 队列 worker：

```bash
ps aux | grep "queue:work" | grep -v grep
```

期望存在运行中的 worker。

8. 定时任务：

在宝塔计划任务中查看最近一次执行日志，确认没有 PHP 版本或路径错误。

9. Laravel 日志：

```bash
tail -n 100 storage/logs/laravel.log
```

期望没有连续异常。

## 14. 回滚入口

如果上线后 smoke test 失败：

1. 暂停流量或切维护页。
2. 保留数据库备份。
3. 按 `ROLLBACK_GUIDE.md` 执行代码回滚。
4. 不要直接执行生产 `migrate:rollback`，除非已确认数据影响。

## 15. 交付文件清单

- `BT_PANEL_DEPLOY.md`
- `.env.production.example`
- `BT_NGINX_REWRITE.conf`
- `DEPLOYMENT_PACKAGE.md`
- `PRODUCTION_CHECKLIST.md`
- `ROLLBACK_GUIDE.md`
- `RELEASE_NOTES_v1.0.0.md`
