<?php
/**
 * 操作日志
 */

namespace Antmin\Http\Repositories;

use App\Common\Base;
use App\Exceptions\CommonException;
use Antmin\Models\OperateLog as Model;
use Exception;

class OperateLogRepository extends Model
{

    public static function getList(int $limit, array $search = []): array
    {
        $query = Model::query();
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

    public static function add(array $info): int
    {
        try {
            return Model::create($info)->id;
        } catch (Exception $e) {
            throw new CommonException($e->getMessage());
        }
    }

    public static function getInfo(int $id): array
    {
        $one = Model::where('id', $id)->get()->first();
        return $one ? $one->toArray() : [];
    }


}
