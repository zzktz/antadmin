<?php
/**
 * 账号
 */

namespace App\Http\Services;

use Antmin\Common\BaseImage;
use Antmin\Exceptions\CommonException;

class IsPasswordService
{
    public static function handleIsPassword(string $password)
    {
        $pwd_len = strlen($password);
        if ($pwd_len > 16 || $pwd_len < 6) {
            throw new CommonException('密码长度应为6~16个字符');
        }
        //2.密码强度
        //1) 是否包含小写字母
        $pattern = '/[a-z]+/';
        $res     = preg_match($pattern, $password);
        //2) 是否包含大写字母
        $pattern = '/[A-Z]+/';
        $res2    = preg_match($pattern, $password);
        //3) 是否包含数字
        $pattern = '/\d+/';
        $res3    = preg_match($pattern, $password);
        //4) 是否包含特殊符号
        $pattern = '/[\!\@\#\$\%\^\&\*\(\)\_\+\-\=\;\:\"\'\|\\\<\>\?\/\.\,\`\~]+/';
        $res4    = preg_match($pattern, $password);
        $num = $res + $res2 + $res3 + $res4;
        if ($num == 1) {
            throw new CommonException('密码应为字母、数字、特殊符号6~16个字符');
        } elseif ($num == 2) {
            throw new CommonException('密码应为字母、数字、特殊符号6~16个字符');
        } elseif ($num >= 3) {
            return true;
        }
    }

}
