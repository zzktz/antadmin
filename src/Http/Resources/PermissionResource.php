<?php

namespace Antmin\Http\Resources;


use Antmin\Http\Repositories\PermissionRepository;


class PermissionResource
{


    public static function listToArray($datas)
    {
        $rest = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['vid']          = $v['vid'];
            $rest[$k]['name']         = $v['action_rule'];
            $rest[$k]['title']        = $v['title'];
            $rest[$k]['pid']          = $v['pid'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['actions']      = PermissionRepository::getChildList($v['id']);
            $rest[$k]['permissionId'] = $v['vid'];
            $rest[$k]['isShowDelete'] = in_array($v['id'], [8, 10, 11]) ? 0 : 1;
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest;
        return $res;
    }


    public static function allToArray($datas)
    {
        $rest = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']     = $v['id'];
            $rest[$k]['action'] = $v['vid'];
            $rest[$k]['name']   = $v['action_rule'];
            $rest[$k]['title']  = $v['title'];
            $rest[$k]['pid']    = $v['pid'];
            $rest[$k]['status'] = $v['status'];
            $rest[$k]['cname']  = $v['title'];
        }
        return $rest;
    }

    public static function listToAccountList($datas)
    {
        $rest = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $rest[$k]['id']           = $v['id'];
            $rest[$k]['action']       = $v['vid'];
            $rest[$k]['name']         = $v['action_rule'];
            $rest[$k]['title']        = $v['title'];
            $rest[$k]['pid']          = $v['pid'];
            $rest[$k]['status']       = $v['status'];
            $rest[$k]['actions']      = self::childToArray(PermissionRepository::getChildList($v['id']));
            $rest[$k]['permissionId'] = $v['vid'];
        }
        $temp['current']   = $datas['pageNo'];
        $temp['pageSize']  = $datas['pageSize'];
        $temp['total']     = $datas['totalCount'];
        $res['pagination'] = $temp;
        $res['data']       = $rest;
        return $res;
    }

    private static function childToArray($data)
    {
        $rest = [];
        if (empty($data)) {
            return [];
        }
        foreach ($data as $k => $v) {
            $rest[$k]['id']     = $v['id'];
            $rest[$k]['action'] = $v['vid'];
            $rest[$k]['name']   = $v['action_rule'];
            $rest[$k]['title']  = $v['title'];
            $rest[$k]['pid']    = $v['pid'];
            $rest[$k]['status'] = $v['status'];
            $rest[$k]['label']  = $v['title'];
            $rest[$k]['value']  = $v['id'];
        }
        return $rest;
    }


}
