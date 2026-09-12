# Antmin

Antmin 是面向 Laravel 12 的后台管理基础组件，提供管理员认证、JWT 令牌、账号管理、角色与权限管理、菜单管理、系统设置、文件上传以及操作和请求日志等通用能力。

## 环境要求

- PHP 8.2 及以上
- Laravel 12
- MySQL 5.7 及以上
- Redis
- Composer

## 安装

在 Laravel 项目中执行：

```bash
composer require zzktz/antmin
```

服务提供者会自动注册。若项目未启用 Laravel 的自动发现，可以在 `config/app.php` 中添加：

```php
Antmin\Providers\ServiceProvider::class,
```

## 数据库初始化

MySQL 项目使用与 Antmin 2.0 兼容的基础表结构。将标准 SQL 文件放置到宿主项目的：

```text
database/schema/antmin.sql
```

然后执行：

```bash
php artisan migrate
```

迁移会补充请求日志表 `app_request_log`、系统设置分组表及必要的兼容字段。SQLite 仅用于轻量测试，生产环境请使用 MySQL。

## 配置

发布配置文件：

```bash
php artisan vendor:publish --tag=antmin-config
```

配置文件为 `config/antmin.php`，常用环境变量如下：

| 环境变量 | 默认值 | 说明 |
| --- | --- | --- |
| `ANTMIN_TOKEN_TTL` | `43200` | JWT 有效期，单位为分钟 |
| `ANTMIN_TOKEN_MAX_DEVICES` | `3` | 单个账号允许的有效终端数 |
| `ANTMIN_TOKEN_REDIS_PREFIX` | `antmin:account_tokens:` | 令牌 Redis 键前缀 |
| `ANTMIN_UPLOAD_URL` | `${APP_URL}/storage` | 上传文件访问地址前缀 |
| `ANTMIN_LOG_DATABASE_CONNECTION` | 空 | 请求日志使用的数据库连接 |
| `ANTMIN_DEFAULT_PASSWORD` | `86662825` | 管理员密码重置默认值 |

## 认证和接口

接口前缀为：

```text
/api/adminconsole/
```

登录：

```text
POST /api/adminconsole/systemLogin
```

前端登录密码按兼容协议提交一次 MD5 摘要，例如密码 `86662825` 应提交 `cc88fc32957d79f96239c010cb3b6a23`。后端数据库保存 Laravel Hash，不保存明文或单纯 MD5 值。

登录成功后，请在后续请求中携带：

```text
Access-Token: <JWT>
```

主要接口包括：

- `systemIndexOperate`：当前用户、菜单、账号、角色、权限和系统设置
- `systemUploadOperate`：图片、文件和视频上传
- `systemLogsOperate`：Laravel 错误日志读取、刷新和清空
- `requestLogOperate`：请求日志查询和清空
- `operateLogOperate`：操作日志查询

## 日志存储

默认请求日志存储在 Redis。需要使用数据库或队列时，可在宿主项目配置中覆盖 `antmin.logStorage`：

```php
'logStorage' => 'database', // redis、database 或 rabbitmq
```

数据库模式使用 `app_request_log` 表；RabbitMQ 模式仍由队列消费者负责写入该表。

## 开发检查

提交前建议执行：

```bash
find src database -name '*.php' -print0 | xargs -0 -n1 php -l
composer validate --strict --no-check-publish
git diff --check
```

后端代码注释使用中文简体，业务数据库表名称使用 `app_` 前缀；Antmin 自带的 `system_*` 表属于兼容基础系统表。
