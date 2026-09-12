<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminTables extends Migration
{
    /**
     * 执行迁移。
     */
    public function up()
    {
        $connection = config('antmin.database.connection') ?: config('database.default');
        $schema = Schema::connection($connection);
        # antmin 使用宿主项目提供的 antmin.sql，避免包内再创建不完整的旧表结构。
        if ($schema->hasTable('system_account')) {
            return;
        }
        # 非 MySQL 环境由宿主项目提供轻量测试表结构，避免执行 MySQL 专用 SQL。
        if ($schema->getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        $schemaPath = config('antmin.database.schema_path') ?: base_path('database/schema/antmin.sql');
        if (! is_file($schemaPath)) {
            throw new RuntimeException('缺少 antmin.sql，请将用户提供的 MySQL 基础系统表放到：' . $schemaPath);
        }
        DB::connection($connection)->unprepared((string) file_get_contents($schemaPath));
    }

    /**
     * 回滚迁移。
     */
    public function down()
    {
        # antmin.sql 包含宿主项目的基础系统数据，回滚时不自动删除业务数据。

    }
}
