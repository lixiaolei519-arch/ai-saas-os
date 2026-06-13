# 宝塔面板部署交付包

适用版本：`v1.4.0`

稳定提交：`Release v1.4.0 queue and scheduler foundation`

本文档用于中国大陆服务器上的宝塔面板部署。仅覆盖部署、配置、权限、初始化、队列、定时任务和上线后 smoke test，不包含任何新业务功能。

## 1. 宝塔环境要求

宝塔软件商店安装：

- Nginx
- PHP 8.2 或更高，推荐 PHP 8.3
- MySQL 8.0
- Composer
- Node.js 可选，仅在服务器上重新构建 React 控制台时需要
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
git checkout v1.4.0
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
```

真实微信/支付宝密钥未准备好时，只允许在测试或预发布环境继续使用模拟 HMAC 回调密钥。生产流量前必须替换。

v1.2.0 默认使用 `PAYMENT_PROVIDER=mock`。如果微信/支付宝真实密钥未配置，订单创建不会崩溃，但支付参数会返回 `wechat_pay_unconfigured` 或 `alipay_unconfigured`，用于提示继续使用 mock 支付或补齐真实密钥。不要在未完成商户、证书、私钥和回调密钥配置前切换真实支付流量。

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

如果不需要种子数据，可跳过 `db:seed`。

部署验收账号必须通过命令创建，不要在文档或代码仓库中保存真实密码：

```bash
php artisan app:create-demo-users
```

命令会在终端输出：

```text
admin email: <admin-email>
admin password: <admin-password>
customer email: <customer-email>
customer password: <customer-password>
```

上线 smoke test 使用这里输出的账号和密码。需要指定邮箱时可传入参数，密码仍会自动生成并输出：

```bash
php artisan app:create-demo-users \
  --admin-email=admin@your-domain.example \
  --customer-email=customer@your-domain.example
```

## 9. 缓存和优化命令

管理员后台访问地址：

```text
https://ai.js3.cn/console/login
```

客户门户访问地址：

```text
https://ai.js3.cn/console/portal/login
```

API 地址：

```text
https://ai.js3.cn/api/v1
```

React 源码位置：

```text
frontend/admin-console
```

构建产物位置：

```text
public/console
```

管理员后台和客户门户共用同一个 React 项目。宝塔服务器如果没有 Node.js，也可以直接使用已提交的 `public/console` 构建产物访问页面。后续修改前端源码后必须重新构建：

```bash
cd /www/wwwroot/ai-saas-os/frontend/admin-console
npm install
npm run build
```

构建完成后确认 `public/console/index.html` 存在。

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

队列状态检查：

```bash
php artisan app:queue-check
```

期望看到：

```text
[PASS] queue connection configured
[PASS] jobs table exists
[PASS] failed_jobs table exists
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

v1.4.0 已注册的定时任务命令：

```bash
php artisan app:renewal-reminders
php artisan app:orders-expire --minutes=30
php artisan app:commissions-settle
```

这些命令只处理系统内部记录，不会真实发送邮件、短信、外部营销内容或付款。

## 12. 上线前检查命令

```bash
cd /www/wwwroot/ai-saas-os
composer audit --no-interaction
php artisan production:check
php artisan security:prelaunch
php artisan app:production-check
```

必须全部通过后再切生产流量。

## 13. 上线后 Smoke Test

优先执行一键部署验收命令：

```bash
cd /www/wwwroot/ai-saas-os
php artisan app:smoke-test
```

该命令会创建或复用 `smoke-test@example.invalid` 测试客户，并创建带 `deployment_smoke_test` 标记的测试订单、License、推广归因和佣金数据。命令不需要真实微信/支付宝密钥，会使用当前配置的 HMAC 回调密钥生成模拟支付回调签名。

成功时必须看到以下关键输出：

```text
[OK] database connected
[OK] demo admin exists
[OK] demo customer exists
[OK] customer login
[OK] customer portal api accessible
[OK] customer license api is isolated
[OK] customer order api is isolated
[OK] admin api accessible
[OK] console build exists
[OK] order created
[OK] mock payment callback
[OK] license provisioned
[OK] license verified
[OK] commission generated
```

如果某一步失败，命令会输出 `Reason:` 和 `Suggested fix:`。先按建议修复数据库、迁移、路由、支付回调密钥或 APP_KEY，再重新运行 `php artisan app:smoke-test`。

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

3. React 控制台入口可访问：

```bash
curl -I https://ai.js3.cn/console/login
curl -I https://ai.js3.cn/console/dashboard
curl -I https://ai.js3.cn/console/portal/login
curl -I https://ai.js3.cn/console/portal/dashboard
```

期望 HTTP 状态为 `200`，并返回 `public/console/index.html`。

4. 管理员登录接口：

`/api/v1/admin/auth/login` 必须使用 `POST`，并且 curl 请求必须带 `Accept: application/json`。

```bash
curl -s -X POST https://your-domain.example/api/v1/admin/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"<admin-email-from-command>","password":"<admin-password-from-command>"}'
```

期望返回 `data.token`。

5. 普通用户登录接口：

`/api/v1/auth/login` 必须使用 `POST`，并且 curl 请求必须带 `Accept: application/json`。

```bash
curl -s -X POST https://your-domain.example/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"<customer-email-from-command>","password":"<customer-password-from-command>"}'
```

期望返回 `data.token`。

6. 后台统计接口：

```bash
curl -s https://your-domain.example/api/v1/admin/stats \
  -H "Authorization: Bearer <admin-token>"
```

期望返回用户数、租户数、订单数等统计字段。

7. 客户门户接口：

```bash
curl -s https://your-domain.example/api/v1/portal/dashboard \
  -H "Authorization: Bearer <customer-token>"

curl -s https://your-domain.example/api/v1/portal/licenses \
  -H "Authorization: Bearer <customer-token>"

curl -s https://your-domain.example/api/v1/portal/orders \
  -H "Authorization: Bearer <customer-token>"
```

期望只返回当前客户自己的首页统计、License 列表和订单列表，不返回其他客户数据。

8. 队列 worker：

```bash
ps aux | grep "queue:work" | grep -v grep
```

期望存在运行中的 worker。

9. 定时任务：

在宝塔计划任务中查看最近一次执行日志，确认没有 PHP 版本或路径错误。

10. Laravel 日志：

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
- `frontend/admin-console`
- `public/console`
- `DEPLOYMENT_PACKAGE.md`
- `PRODUCTION_CHECKLIST.md`
- `ROLLBACK_GUIDE.md`
- `RELEASE_NOTES_v1.0.0.md`

## v1.5.0 Production Hardening Notes

Release: `Release v1.5.0 production hardening`

Run the strengthened production self-check after deployment:

```bash
cd /www/wwwroot/ai-saas-os
php artisan app:production-check
```

The command now checks:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY`
- `APP_URL`
- database connectivity
- `DB_COLLATION`
- writable `storage`
- writable `bootstrap/cache`
- queue configuration
- required `.env` fields
- `public/console/index.html`
- `/health`
- `/console`
- `/api/v1/product-plans` JSON response
- sensitive paths blocked from public access: `/.env`, `/.git/config`, `/composer.json`

Run the strengthened smoke test after the self-check:

```bash
php artisan app:smoke-test
```

The smoke test now also verifies:

- `/console/dashboard` can return the React console entry
- `/api/v1/product-plans` returns JSON with `Accept: application/json`
- sensitive files are not web accessible

New deployment documents:

- `docs/deployment/backup-restore.md`
- `docs/deployment/github-deployment.md`
- `docs/deployment/baota-troubleshooting.md`

Manual deployment script draft:

```bash
bash scripts/deploy-bt.sh
```

Review the script variables before running it on a production server:

- `PROJECT_DIR`
- `BRANCH`
- `PHP_BIN`
- `COMPOSER_BIN`
- `RUN_SEED`
