<?php

namespace Antmin\Traits;

use Antmin\Exceptions\CommonException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

trait SplitTableTrait
{

    protected $dbConnection = ''; //默认连接
    protected $suffix = null;     //后缀参数
    protected $originTable;       //原表
    protected $endTable;          //最终表
    protected $orderByField = 'id';
    protected $orderBy = 'desc';

    public function init(string $dbConnection, string $suffix = null)
    {
        $this->dbConnection = $dbConnection ?? 'mysql';
        $this->originTable  = $this->table;      //默认原表
        $this->endTable     = $this->table;      //默认最终表
        $this->suffix       = $suffix ?? date('Ym'); //分表后缀  默认按月
        $this->setSuffix();
    }

    /**
     * 设置分表 并创建
     */
    public function setSuffix()
    {
        $this->endTable = $this->originTable . '_' . $this->suffix;
        $this->table    = $this->endTable;  //最终表替换模型中声明的表作为分表使用的表
        $this->createTable();
    }

    /**
     * 上一个分表
     * @return string
     */
    public function getPrevTable(): string
    {
        if (strlen($this->suffix) == 8) {
            $prevTable = $this->originTable . '_' . date("Ymd", strtotime("-1 day"));
        } else {
            $prevTable = $this->originTable . '_' . date('Ym', strtotime('-1 month'));
        }
        if (Schema::connection($this->dbConnection)->hasTable($prevTable) == false) {
            $prevTable = $this->originTable;
        }
        return $prevTable;
    }

    /**
     * 开始的自增 ID
     * @param string $table
     * @return int
     */
    public function getStartIncreaseId(string $table): int
    {
        $maxInfo = DB::connection($this->dbConnection)->select("select max(id) as maxId from " . $table);
        $_maxId  = $maxInfo[0]->maxId ?? 0;
        return $_maxId + 1;
    }

    /**
     * 判断表是否存在
     * @param string $endTable
     * @return bool
     */
    public function isHasTable(string $endTable): bool
    {
        return Schema::connection($this->dbConnection)->hasTable($endTable);
    }

    /**
     * 创建分表
     * @return bool
     */
    public function createTable(): bool
    {
        $isHas = Schema::connection($this->dbConnection)->hasTable($this->endTable);
        if ($isHas) {
            return false;
        }
        //上个分表
        $lastTable = $this->getPrevTable();
        //获取上个分表的 自增ID最大值
        $startIncreaseId = $this->getStartIncreaseId($lastTable);
        //创建分表
        DB::connection($this->dbConnection)->update("create table " . $this->endTable . " like " . $this->originTable);
        //设置新表的 自增ID 初始值
        DB::connection($this->dbConnection)->statement("ALTER TABLE " . $this->endTable . " AUTO_INCREMENT=" . $startIncreaseId);
        return true;
    }


    /**
     * 执行 union all对分表的最终扥分页查询
     * @param $queries
     * @param $limit
     * @return array
     */
    public function dealListByUnionAllQuery($queries, $limit): array
    {
        //弹出一张表作为union的开始
        $unionQuery = $queries->shift();
        //循环剩下的表添加union
        $queries->each(function ($item, $key) use ($unionQuery) {
            $unionQuery->unionAll($item);
        });
        //设置临时表的名称，添加临时表，顺序不能反过来，否则用关联约束会找不到表
        $endQuery = DB::connection($this->dbConnection)->table(DB::connection($this->dbConnection)->raw("({$unionQuery->toSql()}) as union_" . $this->originTable))
            //合并查询条件
            ->mergeBindings($unionQuery);
        if ($this->orderByField) {
            $endQuery->orderBy($this->orderByField, $this->orderBy);
        }
        return $endQuery->paginate($limit)->toArray();
    }
}
