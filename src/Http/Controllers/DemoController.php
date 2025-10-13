<?php
/**
 * 账号
 */

namespace Antmin\Http\Controllers;


use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Illuminate\Http\Request;

class DemoController extends BaseController
{
    public function operate(Request $request)
    {
        $action               = $request['action'];
        //$request['accountId'] = AccountService::getAccountIdByToken();
        unset($request['action']);
        if (method_exists(self::class, $action)) return self::$action($request);
        throw new CommonException('System Not Find Action111');
    }
}