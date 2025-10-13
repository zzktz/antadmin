<?php
/**
 * 个人信息设置
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use App\Exceptions\CommonException;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Services\PersonSetService;
use Antmin\Http\Services\OperateLogService;
use Cache;

class PersonSetController extends BaseController
{
    /**
     * 个人信息编辑
     * @param $request
     * @return mixed
     */
    public static function personalInfoEdit($request)
    {
        $accountId = $request['accountId'];
        $email     = Base::getValue($request, 'email', '', 'email');
        $mobile    = Base::getValue($request, 'mobile', '', 'mobile');
        $password  = Base::getValue($request, 'password', '', 'max:32');
        $nickname  = Base::getValue($request, 'nickname', '', 'alpha_dash|max:50');
        $birthday  = Base::getValue($request, 'birthday', '', 'date_format:Y-m-d');
        if (!empty($mobile)) {
            $filed = 'mobile';
            $value = $mobile;
        } elseif (!empty($nickname)) {
            $filed = 'nickname';
            $value = $nickname;
        } elseif (!empty($email)) {
            $filed = 'email';
            $value = $email;
        } elseif (!empty($birthday)) {
            $filed = 'birthday';
            $value = $birthday;
        } elseif (!empty($password)) {
            AccountService::editMyselfPassword($password, $accountId);
            return Base::sucJson('成功');
        } else {
            $filed = '';
            $value = '';
        }
        AccountService::personalEdit($filed, $value, $accountId);
        return Base::sucJson('成功');
    }

    /**
     * 重新修改密码
     * @param $request
     * @return mixed
     */
    public static function resetPassword($request)
    {
        $accountId = $request['accountId'];
        $password  = Base::getValue($request, 'password', '', 'required|max:32');
        if (!Base::isValidMd5($password)) {
            throw new CommonException('不是有效的密码字符');
        }

        return Base::sucJson('成功');
    }

    /**
     * 重新初始化密码
     * @param $request
     * @return mixed
     */
    public static function reInitPassword($request)
    {
        $accountId = Base::getValue($request, 'id', '', 'required|integer');
        PersonSetService::reInitPassword($accountId);
        return Base::sucJson('成功重置初始密码！');
    }


}
