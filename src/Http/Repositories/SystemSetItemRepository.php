<?php

namespace Antmin\Http\Repositories;


class SystemSetItemRepository
{

    public static function getTree(): array
    {
        $data = self::getTreeArr();
        $res  = [];
        $i    = 0;
        foreach ($data as $k => $v) {
            $res[$i]['id']    = $i + 1;
            $res[$i]['type']  = $k;
            $res[$i]['title'] = $v;
            $i++;
        }
        return $res;
    }

    /**
     * 获取类型名
     * @param string $key
     * @return string
     */
    public static function getItemTitle(string $key): string
    {
        $data = self::getTreeArr();
        return empty($data[$key]) ? '' : $data[$key];
    }


    /**
     * 设置类型
     * @return string[]
     */
    private static function getTreeArr(): array
    {
        return [
            'input_mini'    => '输入框',
            'input_more'    => '多行输入框',
            'choose_single' => '单选',
            'choose_more'   => '多选',
            'time_single'   => '单时间',
            'time_more'     => '时间段',
            'pull_menu'     => '下拉菜单',
            'image_single'  => '单图上传',
            'image_more'    => '多图上传',
            'rich_text'     => '富文本',
            'switch'        => '开关'
        ];
    }


}
