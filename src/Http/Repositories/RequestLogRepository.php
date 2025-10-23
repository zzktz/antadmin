<?php

namespace Antmin\Http\Repositories;


use Antmin\Tool\LogRequestTool;

class RequestLogRepository
{

    public static function getList($limit, $search = []): array
    {
        # 获取缓存数据并排序
        $arrData           = LogRequestTool::getLogData($limit, $search);
        $arrData['memory'] = LogRequestTool::getUsageSize();
        return $arrData;
//        $collect = collect($data);
        # 过滤数据
//        $filtered = $collect->filter(function ($record) use ($search) {
//            if (!empty($search['id']) && $record['uuid'] != $search['id']) {
//                return false;
//            }
//            if (!empty($search['app_env']) && $record['app_env'] != $search['app_env']) {
//                return false;
//            }
//            if (!empty($search['client']) && $record['client'] != $search['client']) {
//                return false;
//            }
//            if (!empty($search['response_status']) && $record['response_status'] != $search['response_status']) {
//                return false;
//            }
//            if (!empty($search['url']) && stripos($record['url'], $search['url']) === false) {
//                return false;
//            }
//            return true; # 默认情况下包含所有未删除记录
//        });
//        return Base::listFormatCollect($limit, $filtered);
    }

    public static function clearData()
    {
        return LogRequestTool::delRedisStorege();
    }



}
