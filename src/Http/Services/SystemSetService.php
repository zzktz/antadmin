<?php
/**
 * 系统配置
 */

namespace Antmin\Http\Services;


use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\SystemSetDetailRepository;
use Antmin\Http\Repositories\SystemSetItemRepository;
use Antmin\Http\Repositories\SystemSetRepository;


class SystemSetService
{

    /**
     * 内容详情 GET
     * @return array
     */
    public static function oneDetailContentGet(): array
    {
        $search['listorder'] = 'asc';
        $datas               = SystemSetDetailRepository::getList(99, $search);
        $data                = $datas['data'] ?? [];
        if (empty($data)) {
            throw new CommonException('setId不正确');
        }
        foreach ($data as $k => $v) {
            $itemType     = $v['item_type'];
            $optionValue  = is_numeric($v['option_value']) ? intval($v['option_value']) : $v['option_value'];
            $temptabdatas = !empty($v['option']) ? json_decode($v['option'], true) : '';
            if (is_array($temptabdatas)) {
                foreach ($temptabdatas as $j => $h) {
                    $temp[$j]               = $h;
                    $temp[$j]['is_checked'] = false;
                }
            }
            $answerValue = $v['value'];
            if ($itemType == 'choose_single') {      # 单选
                $tabdatas    = $temp ?? [];
                $answerValue = $optionValue;
            } elseif ($itemType == 'choose_more') {  # 多选
                $tabdatas    = $temp ?? [];
                $answerValue = !empty($optionValue) ? json_decode($optionValue, true) : [];
            } elseif ($itemType == 'pull_menu') {    # 下拉
                $tabdatas    = $temp ?? [];
                $answerValue = $optionValue;
            } elseif ($itemType == 'time_more') {    # 时间段
                $tabdatas    = $temp ?? [];
                $answerValue = !empty($optionValue) ? json_decode($optionValue, true) : [];
            } elseif ($itemType == 'image_single') { # 单图
                $tabdatas    = [];
                $answerValue = !empty($optionValue) ? Base::fillUrl($optionValue) : '';
            } elseif ($itemType == 'image_more') {   # 多图
                $tabdatas    = [];
                $answerValue = !empty($optionValue) ? Base::fillUrl(json_decode($optionValue, true)) : [];
            }

            $res[$k]['tabdatas']    = $tabdatas ?? [];
            $res[$k]['answerValue'] = $answerValue ?? $optionValue;
            $res[$k]['id']          = $v['id'];
            $res[$k]['title']       = '（' . $v['listorder'] . '）' . $v['title'];
            $res[$k]['itemType']    = $v['item_type'];
            $res[$k]['tip']         = $v['tip'];
            $res[$k]['is_required'] = $v['is_required'];
            unset($answerValue);
            unset($tabdatas);
            unset($temp);
        }
        return $res ?? [];
    }

    /**
     * 内容详情 GET
     * @param array $data
     * @return bool
     */
    public static function oneDetailContentPost(array $data): bool
    {
        if (empty($data)) {
            throw new CommonException('infoData数据不可以为空');
        }
        foreach ($data as $v) {
            $detailId        = $v['id'];
            $itemType        = $v['itemType'];
            $tempoptionValue = $v['answerValue'];
            $tempValue       = $v['answerValue'];

            if ($itemType == 'choose_single') {     # 单选
                $arr             = SystemSetService::getFormatRadio($v);
                $tempoptionValue = $arr['op_value'];
                $tempValue       = $arr['value'];
            } elseif ($itemType == 'choose_more') { # 多选
                $arr             = SystemSetService::getFormatCheckBox($v);
                $tempoptionValue = $arr['op_value'];
                $tempValue       = $arr['value'];
            } elseif ($itemType == 'pull_menu') {   # 下拉
                $arr             = SystemSetService::getFormatRadio($v);
                $tempoptionValue = $arr['op_value'];
                $tempValue       = $arr['value'];
            } elseif ($itemType == 'time_more') {   # 时间段
                $arr             = SystemSetService::getFormatTimeArr($v);
                $tempoptionValue = $arr['op_value'];
                $tempValue       = $arr['value'];
            } elseif ($itemType == 'image_single') { # 单图
                $value           = $v['answerValue'] ? Base::unFillUrl($v['answerValue']) : '';
                $tempoptionValue = $value;
                $tempValue       = $value;
            } elseif ($itemType == 'image_more') {   # 多图
                $arr             = SystemSetService::getFormatImgArr($v);
                $tempoptionValue = $arr['op_value'];
                $tempValue       = $arr['value'];
            }

            $upInfo['option_value'] = $tempoptionValue;
            $upInfo['value']        = $tempValue;
            SystemSetDetailRepository::edit($upInfo, $detailId);
        }
        return true;
    }


    /**
     * 配置 列表
     * @param array $search
     * @param int $limit
     * @return array
     */
    public static function oneDetailConfigList(array $search, int $limit): array
    {
        $res = SystemSetDetailRepository::getList($limit, $search);
        if (empty($res['data'])) {
            return $res;
        }
        $rest = [];
        foreach ($res['data'] as $k => $v) {
            $rest[$k]               = $v;
            $rest[$k]['item_title'] = SystemSetItemRepository::getItemTitle($v['item_type']);
        }
        unset($res['data']);
        $res['data'] = $rest;
        return $res;
    }

    public static function oneDetailConfigAdd(array $info): int
    {
        $flag = $info['flag'];
        $one  = SystemSetDetailRepository::where('flag', $flag)->get()->first();
        if (!empty($one)) {
            throw new CommonException('标识已存在');
        }
        return SystemSetDetailRepository::add($info);
    }

    public static function oneDetailConfigEdit(array $info, int $id): bool
    {
        return SystemSetDetailRepository::edit($info, $id);
    }

    public static function oneDetailConfigDel(int $id): int
    {
        return SystemSetDetailRepository::del($id);
    }

    public static function oneDetailConfigGetInfo(int $id): array
    {
        return SystemSetDetailRepository::getInfo($id);
    }

    public static function getOneDetailValueByFlag(string $flag)
    {
        return SystemSetDetailRepository::getValueByFlag($flag);
    }

    /**
     * 项树
     * @return array
     */
    public static function systemSetItemTree(): array
    {
        return SystemSetItemRepository::getTree();
    }

    /**
     * 设置管理 列表
     * @param array $search
     * @param int $limit
     * @return array
     */
    public static function systemSetList(array $search, int $limit): array
    {
        return SystemSetRepository::getList($search, $limit);
    }

    public static function systemSetAdd(string $title): int
    {
        $one = SystemSetRepository::getInfoByTitle($title);
        if (!empty($one)) {
            throw new CommonException('标题已存在');
        }
        return SystemSetRepository::add(['title' => $title]);
    }

    public static function systemSetEdit(array $info, int $id): bool
    {
        if (!empty($info['title'])) {
            $one = SystemSetRepository::where('title', $info['title'])->where('id', '!=', $id)->get()->first();
            if (!empty($one)) {
                throw new CommonException('标题已存在');
            }
        }
        return SystemSetRepository::edit($info, $id);
    }

    public static function systemSetDel(int $id): bool
    {
        $one = SystemSetDetailRepository::where('set_id', $id)->get()->first();
        if (!empty($one)) {
            throw new CommonException('有数据不能删除');
        }
        return SystemSetRepository::del($id);
    }

    public static function systemSetDetail(int $id): array
    {
        return SystemSetRepository::getInfo($id);
    }


    /**
     * 格式处理 多选
     * @param $v
     * @return array
     */
    public static function getFormatCheckBox($v): array
    {
        $tempValue       = [];
        $tempoptionValue = json_encode($v['answerValue']);
        foreach ($v['tabdatas'] as $h) {
            if (in_array($h['key'], $v['answerValue'])) {
                $tempValue[] = $h['label'];
            }
        }
        $tempValue       = empty($tempValue) ? '' : json_encode($tempValue);
        $res['op_value'] = $tempoptionValue;
        $res['value']    = $tempValue;
        return $res;
    }

    /**
     * 格式处理 单选
     * @param $v
     * @return array
     */
    public static function getFormatRadio($v): array
    {
        foreach ($v['tabdatas'] as $h) {
            if ($v['answerValue'] == $h['key']) {
                $tempValue = $h['label'];
            }
        }
        $res['op_value'] = $v['answerValue'];
        $res['value']    = $tempValue ?? '';
        return $res;
    }

    /**
     * 格式处理 时间段
     * @param $v
     * @return array
     */
    public static function getFormatTimeArr($v): array
    {
        $res['op_value'] = $v['answerValue'] ? json_encode($v['answerValue']) : '';
        $res['value']    = $v['answerValue'] ? json_encode($v['answerValue']) : '';
        return $res;
    }

    /**
     * 格式处理 图片
     * @param $v
     * @return array
     */
    public static function getFormatImgArr($v): array
    {
        $imgurl          = $v['answerValue'] ? Base::unFillUrl($v['answerValue']) : '';
        $res['op_value'] = json_encode($imgurl);
        $res['value']    = json_encode($imgurl);
        return $res;
    }

}
