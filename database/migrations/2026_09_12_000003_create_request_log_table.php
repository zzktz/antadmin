<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建请求日志表。Redis 是默认存储，启用数据库/队列日志时使用此表。
     */
    public function up(): void
    {
        $schema = $this->schema();
        if ($schema->hasTable('app_request_log')) {
            return;
        }

        $schema->create('app_request_log', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->nullable()->index();
            $table->string('app_env', 32)->nullable()->index();
            $table->string('app_name', 100)->nullable()->index();
            $table->string('url', 500)->nullable();
            $table->string('client', 50)->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->text('header')->nullable();
            $table->text('params')->nullable();
            $table->text('query_log')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->index();
            $table->text('response_content')->nullable();
            $table->dateTime('request_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * 删除请求日志表。
     */
    public function down(): void
    {
        $this->schema()->dropIfExists('app_request_log');
    }

    /**
     * 日志可使用独立数据库连接，迁移必须与模型保持一致。
     */
    private function schema()
    {
        $connection = config('antmin.database.log_connection');
        return Schema::connection($connection ?: config('database.default'));
    }
};
