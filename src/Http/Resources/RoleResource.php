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

    public static function formatToAccountList($datas)
    {
        $rest = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['name']         = $v['vid'];
            $rest[$k]['title']        = $v['name'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['permissionId'] = PermissionRepository::getAllPermissionsIdsByRoleIds([$v['id']]);
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest;
        return $res;
    }

}
