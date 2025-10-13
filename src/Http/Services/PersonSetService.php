<?php
/**
 * 权限
 */

namespace Antmin\Http\Services;


use Antmin\Http\Repositories\AccountRepository;


class PersonSetService
{

    protected static $defPassword = 'a@123456';
    protected static $fixPasswordKey = 'reInitPasswordCacheKey';

    public static function isResetPassword(int $accountId)
    {

    }

    public static function resetPassword(int $accountId)
    {

    }


    public static function reInitPassword(int $accountId)
    {
        AccountRepository::updatePassword(self::$defPassword, $accountId);
    }


}
