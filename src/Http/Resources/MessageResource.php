<?php

namespace Antmin\Http\Resources;


class MessageResource
{


    public static function listToArray($datas)
    {
        $temp = [];
        if (empty($datas['data'])) {
            return $datas;
        }
        foreach ($datas['data'] as $k => $v) {
            $temp[$k]['id']         = $v['id'];
            $temp[$k]['title']      = $v['title'];
            $temp[$k]['img']        = url('images/msg.png');
            $temp[$k]['created_at'] = self::formatDate($v['created_at']);
        }
        unset($datas['data']);
        $res         = $datas;
        $res['data'] = $temp;
        return $res;
    }


    public static function formatDate($created_at)
    {
        $_time = time() - strtotime($created_at);
        $d     = intval($_time / 60);
        if ($d < 10) {
            return '刚刚';
        } elseif ($d < 30) {
            return '十分钟前';
        } elseif ($d < 60) {
            return '30分钟前';
        } elseif ($d < 120) {
            return '一小时前';
        } elseif ($d < 180) {
            return '两小时前';
        } elseif ($d < 300) {
            return '三小时前';
        } elseif ($d < 600) {
            return '五小时前';
        } elseif ($d < 1440) {
            return '十小时前';
        } elseif ($d < 2880) {
            return '一天前';
        } elseif ($d < 4320) {
            return '两天前';
        } else {
            return date('m月d日', strtotime($created_at));
        }
    }


}
