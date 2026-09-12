<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 为 antmin.sql 补齐系统设置分组表及详情关联字段。
     */
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('system_set')) {
            $schema->create('system_set', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->unsignedInteger('listorder')->default(0);
                $table->unsignedTinyInteger('is_show')->default(1);
                $table->timestamps();
            });
        }

        if ($schema->hasTable('system_set_detail') && ! $schema->hasColumn('system_set_detail', 'set_id')) {
            $schema->table('system_set_detail', function (Blueprint $table) {
                $table->unsignedBigInteger('set_id')->nullable()->index();
            });
        }
    }

    /**
     * 回滚兼容结构。
     */
    public function down(): void
    {
        $schema = $this->schema();
        if ($schema->hasTable('system_set_detail') && $schema->hasColumn('system_set_detail', 'set_id')) {
            $schema->table('system_set_detail', function (Blueprint $table) {
                $table->dropColumn('set_id');
            });
        }
        $schema->dropIfExists('system_set');
    }

    /**
     * 使用与系统设置模型一致的数据库连接。
     */
    private function schema()
    {
        $connection = config('antmin.database.connection') ?: config('database.default');
        return Schema::connection($connection);
    }
};
