# 自动开发路线图

## v0.1.1 安全补丁
目标：
修复 composer audit 中已知 Laravel 安全公告。

任务：
- 升级 laravel/framework 到安全版本
- 运行 composer audit
- 运行 php artisan test
- 更新 CHANGELOG.md
- 标记 v0.1.1 STABLE

## v0.2.0 AI计费基础版
目标：
实现 AI 使用余额和扣费账本。

任务：
- 创建余额账户表
- 创建额度账本表
- 创建AI使用记录表
- 实现额度发放
- 实现额度扣减
- 实现余额不足拦截
- 编写测试

## v0.3.0 插件系统基础版
目标：
实现插件上传、版本和下载授权。

任务：
- 创建插件表
- 创建插件版本表
- 创建插件包表
- 实现插件上传
- 实现插件下载Token
- 实现下载授权校验
- 编写测试

## v0.4.0 工作流系统基础版
目标：
实现事件触发、条件判断、动作执行。

任务：
- 创建工作流表
- 创建规则表
- 创建执行记录表
- 实现事件触发
- 实现条件判断
- 实现动作执行
- 编写测试

## v0.5.0 风控系统基础版
目标：
实现黑名单、异常记录、限流。

任务：
- 创建风险事件表
- 创建黑名单表
- 实现License异常记录
- 实现接口限流
- 实现黑名单拦截
- 编写测试

## v0.6.0 营销和分销基础版
目标：
实现推广链接、佣金、续费提醒。

任务：
- 创建渠道表
- 创建推广链接表
- 创建佣金表
- 实现推广归因
- 实现佣金计算
- 实现续费提醒
- 编写测试

## v0.7.0 后台管理 API 基础版
目标：
实现后台管理员登录、核心业务对象只读管理和基础统计。

任务：
- 实现管理员登录
- 实现管理员查看用户
- 实现管理员查看租户
- 实现管理员查看 License
- 实现管理员查看订单
- 实现管理员查看支付回调
- 实现管理员查看渠道和佣金
- 实现基础后台统计接口
- 编写后台 API 测试

质量门禁：
- composer audit 通过
- php artisan migrate:fresh --env=testing --force 通过
- php artisan test 通过
- 后台 API 有测试覆盖
- CHANGELOG.md 更新
- STABLE_TAG.md 标记 v0.7.0 STABLE
- Git 提交：Release v0.7.0 stable admin foundation

## v0.8.0 客户门户 API 基础版
目标：
实现客户自助查看授权、订单、AI 使用记录、推广链接、佣金和续费申请。

任务：
- 实现客户查看自己的 License
- 实现客户查看自己的订单
- 实现客户查看自己的使用记录
- 实现客户查看自己的推广链接
- 实现客户查看自己的佣金
- 实现客户申请续费
- 实现客户复制 LicenseKey
- 实现客户解绑域名
- 编写客户门户 API 测试

质量门禁：
- composer audit 通过
- php artisan migrate:fresh --env=testing --force 通过
- php artisan test 通过
- 客户门户 API 有测试覆盖
- CHANGELOG.md 更新
- STABLE_TAG.md 标记 v0.8.0 STABLE
- Git 提交：Release v0.8.0 stable customer portal

## v0.9.0 部署准备版
目标：
补齐生产部署文档、健康检查和上线前检查命令。

任务：
- 生成 .env.example
- 生成宝塔部署说明
- 生成队列 worker 启动说明
- 生成定时任务说明
- 生成 Nginx 伪静态说明
- 生成数据库初始化命令说明
- 增加 /health 健康检查接口
- 增加生产环境检查命令
- 增加模拟支付说明
- 增加上线前检查清单
- 编写 /health 测试

质量门禁：
- composer audit 通过
- php artisan migrate:fresh --env=testing --force 通过
- php artisan test 通过
- /health 测试通过
- 部署文档存在
- CHANGELOG.md 更新
- STABLE_TAG.md 标记 v0.9.0 STABLE
- Git 提交：Release v0.9.0 deployment ready

## v1.0.0 最小可上线商业版
目标：
跑通最小商业闭环并补齐上线前材料。

任务：
- 完整跑通注册/登录、创建订单、模拟支付回调、自动开通 License、License 校验、推广归因、佣金生成
- 增加完整端到端测试
- 增加种子数据
- 增加演示账号
- 增加上线前安全检查
- 增加 README.md
- 增加 API 文档草案
- 确保没有进入大型 AI 自动运营、插件市场高级版、复杂工作流高级版

质量门禁：
- composer install --no-interaction 通过
- composer audit --no-interaction 通过
- php artisan migrate:fresh --env=testing --force 通过
- php artisan test 通过
- 核心端到端测试通过
- CHANGELOG.md 更新
- STABLE_TAG.md 标记 v1.0.0 STABLE
- Git 提交：Release v1.0.0 minimum commercial launch
