<?php

namespace Antmin\Http\Resources;


use Antmin\Common\Base;
use Antmin\Http\Repositories\RoleRepository;
use Antmin\Http\Repositories\PermissionRepository;
use Antmin\Http\Repositories\AccountRepository;

class AccountResource
{


    public static function listToArray($datas)
    {
        $rest = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['name']         = $v['name'];
            $rest[$k]['username']     = $v['nickname'];
            $rest[$k]['mobile']       = $v['mobile'];
            $rest[$k]['email']        = $v['email'];
            $rest[$k]['birthday']     = $v['birthday'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['rolesData']    = RoleRepository::getRolesByAccountId($v['id'], ['id', 'name']);
            $rest[$k]['avatar']       = $v['avatar'] ? Base::fillUrl($v['avatar']) : '';
            $rest[$k]['roles']        = RoleRepository::getRolesIdsByAccountId($v['id']);
            $rest[$k]['rules']        = PermissionRepository::getAllPermissionsIdsByAccountId($v['id']);
            $rest[$k]['isShowDelete'] = AccountRepository::isSuperAdmin($v['id']) ? 0 : 1;
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest;
        return $res;
    }
}
