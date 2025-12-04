<?php

namespace Antmin\Http\Resources;

use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\PermissionRepository;


class RoleResource
{



    public static function formatToList($datas)
    {
        $data = $datas['data'];
        foreach ($data as $k => $v) {
            $rest[$k]                 = $v;
            $rest[$k]['permissions']  = PermissionRepository::getAllPermissionsIdsByRoleIds([$v['id']]);
            $rest[$k]['isShowDelete'] = RoleRepository::getSupperRoleId() == $v['id'] ? 0 : 1;
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest ?? [];
        return $res;
    }

}
