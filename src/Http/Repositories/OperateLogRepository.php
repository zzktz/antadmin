<?php
/**
 * 操作日志
 */

namespace Antmin\Http\Repositories;

use App\Common\Base;
use Antmin\Models\OperateLog;


class OperateLogRepository
{


    public function __construct(
        OperateLog $operateLog,
    )
    {

    }


    public function getList(int $limit, array $search = []): array
    {
        $query = $this->operateLog->query();
        if (!empty($search)) {
            if (isset($search['operate']) && $search['operate']) {
                $query->where('operate', 'like', '%' . $search['operate'] . '%');
            }
            if (isset($search['action']) && $search['action']) {
                $query->where('action', 'like', '%' . $search['action'] . '%');
            }
            if (isset($search['account_id']) && $search['account_id']) {
                $query->where('account_id', $search['account_id']);
            }
            if (isset($search['start_at']) && $search['start_at']) {
                $query->where('created_at', '>=', $search['start_at']);
                $query->where('created_at', '<=', $search['end_at']);
            }
        }
        $query->orderBy('id', 'desc');
        $query->with('account');
        return Base::listFormat($limit, $query);
    }

    public function add(array $info): int
    {
        return $this->operateLogcreate($info)->id;
    }

    public function getInfo(int $id): array
    {
        $one = $this->operateLog->where('id', $id)->first();
        return $one ? $one->toArray() : [];
    }


}
