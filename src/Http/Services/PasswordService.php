<?php
/**
 * 密码强度检测服务
 */

namespace Antmin\Http\Services;


use Antmin\Exceptions\CommonException;

class PasswordService
{


    /**
     * 检查密码强度
     * 要求：6-16位字符，至少包含字母大写、小写、数字、特殊符号 其中3种的组合
     *
     * @param string $password 待检查的密码
     * @return void
     */
    public static function checkPasswordStrength(string $password): void
    {
        # 1. 基本长度检查
        $passwordLength = mb_strlen($password, 'UTF-8');
        if ($passwordLength < 6 || $passwordLength > 16) {
            throw new CommonException('密码长度应为6~16个字符');
        }

        # 2. 性能优化：提前检查常见弱密码
        if (self::isWeakPassword($password)) {
            throw new CommonException('密码强度不足，请使用更复杂的密码');
        }

        # 3. 使用位运算记录字符类型（性能更好）
        $characterTypes     = 0;
        $characterTypesMask = [
            'lower'   => 1,    # 0001
            'upper'   => 2,    # 0010
            'digit'   => 4,    # 0100
            'special' => 8,    # 1000
        ];

        # 4. 单次遍历检查所有类型
        $specialChars   = '!@#$%^&*()_+-="\'|>?`~';
        $specialCharMap = array_flip(str_split($specialChars));

        for ($i = 0; $i < $passwordLength; $i++) {
            $char     = $password[$i];
            $charCode = ord($char);

            # 检查小写字母
            if ($charCode >= 97 && $charCode <= 122) {
                $characterTypes |= $characterTypesMask['lower'];
                continue;
            }

            # 检查大写字母
            if ($charCode >= 65 && $charCode <= 90) {
                $characterTypes |= $characterTypesMask['upper'];
                continue;
            }

            # 检查数字
            if ($charCode >= 48 && $charCode <= 57) {
                $characterTypes |= $characterTypesMask['digit'];
                continue;
            }

            # 检查特殊字符（使用哈希表O(1)查找）
            if (isset($specialCharMap[$char])) {
                $characterTypes |= $characterTypesMask['special'];
            }
        }

        # 5. 计算包含的类型数量（使用位运算）
        $typeCount = 0;
        foreach ($characterTypesMask as $mask) {
            if (($characterTypes & $mask) !== 0) {
                $typeCount++;
            }
        }

        # 6. 强度检查
        if ($typeCount < 3) {
            throw new CommonException('密码应为字母、数字、特殊符号组合，6~16个字符');
        }

        # 7. 额外安全检查：连续字符和重复模式
        if (self::hasBadPattern($password)) {
            throw new CommonException('密码包含不安全的模式（如连续字符或重复序列）');
        }

    }


    /**
     * 检查是否为常见弱密码
     * @param string $password
     * @return bool
     */
    private static function isWeakPassword(string $password): bool
    {
        # 转换为小写进行统一检查
        $lowerPassword = strtolower($password);

        # 常见弱密码字典（可根据需要扩展）
        $commonWeakPasswords = [
            # 数字序列
            '123456', '12345678', '123456789', '1234567890',
            '012345', '01234567', '012345678', '0123456789',
            '111111', '222222', '333333', '444444', '555555',
            '666666', '777777', '888888', '999999', '000000',
            # 键盘序列
            'qwerty', 'qwertyuiop', 'asdfgh', 'asdfghjkl',
            'zxcvbn', 'zxcvbnm', 'password', 'passwd',
            # 字母序列
            'abcdef', 'abcdefgh', 'abc123', 'abcd1234',
            # 简单重复
            'aaabbb', 'ababab', 'aabbcc',
            # 公司/产品相关（示例）
            'antmin', 'admin123', 'test123',
        ];

        # 检查是否在弱密码列表中
        if (in_array($lowerPassword, $commonWeakPasswords, true)) {
            return true;
        }

        # 检查是否为纯数字（已包含在字符类型检查中，但弱密码特别检查）
        if (preg_match('/^\d+$/', $password)) {
            return true;
        }

        # 检查是否为纯字母（已包含在字符类型检查中，但弱密码特别检查）
        if (preg_match('/^[a-z]+$/i', $password)) {
            return true;
        }

        return false;
    }

    /**
     * 检查密码中是否包含不安全的模式
     * @param string $password
     * @return bool
     */
    private static function hasBadPattern(string $password): bool
    {
        $length = strlen($password);

        # 1. 检查连续字符（字母或数字连续3个以上）
        for ($i = 0; $i < $length - 2; $i++) {
            $char1 = ord($password[$i]);
            $char2 = ord($password[$i + 1]);
            $char3 = ord($password[$i + 2]);

            # 检查递增序列（如abc, 123）
            if ($char2 - $char1 === 1 && $char3 - $char2 === 1) {
                # 排除特殊字符序列，只检查字母和数字
                if (
                    # 小写字母连续
                    ($char1 >= 97 && $char1 <= 122 && $char2 >= 97 && $char2 <= 122 && $char3 >= 97 && $char3 <= 122) ||
                    # 大写字母连续
                    ($char1 >= 65 && $char1 <= 90 && $char2 >= 65 && $char2 <= 90 && $char3 >= 65 && $char3 <= 90) ||
                    # 数字连续
                    ($char1 >= 48 && $char1 <= 57 && $char2 >= 48 && $char2 <= 57 && $char3 >= 48 && $char3 <= 57)
                ) {
                    return true;
                }
            }

            # 检查递减序列（如cba, 321）
            if ($char1 - $char2 === 1 && $char2 - $char3 === 1) {
                # 排除特殊字符序列，只检查字母和数字
                if (
                    # 小写字母连续
                    ($char1 >= 97 && $char1 <= 122 && $char2 >= 97 && $char2 <= 122 && $char3 >= 97 && $char3 <= 122) ||
                    # 大写字母连续
                    ($char1 >= 65 && $char1 <= 90 && $char2 >= 65 && $char2 <= 90 && $char3 >= 65 && $char3 <= 90) ||
                    # 数字连续
                    ($char1 >= 48 && $char1 <= 57 && $char2 >= 48 && $char2 <= 57 && $char3 >= 48 && $char3 <= 57)
                ) {
                    return true;
                }
            }
        }

        # 2. 检查重复字符（如aaa, 111）
        for ($i = 0; $i < $length - 2; $i++) {
            if ($password[$i] === $password[$i + 1] && $password[$i] === $password[$i + 2]) {
                return true;
            }
        }

        # 3. 检查键盘相邻键模式（简化版，检查常见水平相邻）
        $keyboardRows = [
            'qwertyuiop',
            'asdfghjkl',
            'zxcvbnm'
        ];

        $lowerPassword = strtolower($password);
        for ($i = 0; $i < $length - 2; $i++) {
            $seq = substr($lowerPassword, $i, 3);

            foreach ($keyboardRows as $row) {
                if (strpos($row, $seq) !== false) {
                    return true;
                }

                # 也检查反向序列
                $reverseSeq = strrev($seq);
                if (strpos($row, $reverseSeq) !== false) {
                    return true;
                }
            }
        }

        # 4. 检查简单交替模式（如ababab）
        if ($length >= 4) {
            # 检查2字符重复模式
            if ($length % 2 === 0) {
                $pattern   = substr($password, 0, 2);
                $isPattern = true;
                for ($i = 2; $i < $length; $i += 2) {
                    if (substr($password, $i, 2) !== $pattern) {
                        $isPattern = false;
                        break;
                    }
                }
                if ($isPattern) {
                    return true;
                }
            }
        }

        return false;
    }


}
