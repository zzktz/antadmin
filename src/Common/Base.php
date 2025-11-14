<?php
/**
 *  基础
 */

namespace Antmin\Common;

use Validator;
use Antmin\Exceptions\CommonException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class Base
{


    public static function errJson(string $msg, array $data = [], int $code = 0): JsonResponse
    {
        $res['useTime1'] = self::getUseTime();
        $res['status']   = "fail";
        $res['code']     = $code;
        $res['message']  = $msg;
        $res['data']     = $data;
        return response()->json($res)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public static function sucJson(string $msg, array $data = [], int $code = 0): JsonResponse
    {

        $res['useTime1'] = self::getUseTime();
        $res['status']   = "success";
        $res['code']     = $code;
        $res['message']  = $msg;
        $res['data']     = $data;
        return response()->json($res)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public static function getUseTime(): string
    {
        # 记录请求结束时间
        $endTime = microtime(true);
        # 计算请求执行时间
        $executionTime = $endTime - LARAVEL_START;
        return intval($executionTime * 1000) . ' ms';
    }

    public static function isMobile(string $mobile): bool
    {
        $mobile = trim($mobile);
        if (!preg_match('/^1([0-9]{10})$/', $mobile)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 图片上传地址
     * @param string $savePath
     * @return string
     */
    public static function getUploadUrl(string $savePath): string
    {
        if (self::isDev()) {
            return config('upload.url') . 'Base.php/' . $savePath;
        } else {
            return $savePath;
        }
    }

    /**
     * 先编码成json字符串，再解码成数组
     * @param $object
     * @return mixed
     */
    public static function objToArr($object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * 页码列表格式输出  从数据库中获取
     * @param int $limit
     * @param $query
     * @return array
     */
    public static function listFormat(int $limit, $query): array
    {
        $datas             = $query->paginate($limit);
        $temp              = $datas ? $datas->toArray() : [];
        $data              = $temp['data'] ?? [];
        $res['pageSize']   = $limit;
        $res['pageNo']     = $datas->currentPage();
        $res['totalCount'] = $datas->total();
        $res['totalPage']  = $datas->lastPage();
        $res['data']       = $data;
        return $res;
    }


    /**
     * 获取一个请求值
     * @param $request
     * @param string $field
     * @param string $fieldName
     * @param string $validateRule
     * @param string $msg
     * @return mixed
     */
    public static function getValue($request, string $field, string $fieldName = '', string $validateRule = '', string $msg = '')
    {
        $fieldName = empty($fieldName) ? $field : $fieldName;
        if (!empty($validateRule)) {
            $validator = Validator::make($request->all(), [$field => $validateRule], [], [$field => $fieldName]);
            if ($validator->fails()) {
                $message = empty($msg) ? $validator->errors()->first() : $msg;
                throw new CommonException($message);
            }
        }
        return $request[$field];
    }

    /**
     * 获取请求地址的查询参数
     * @param string $url
     * @return mixed
     */
    public static function getUrlQueryParam(string $url): array
    {
        if (empty($url)) {
            return [];
        }
        $sarr = parse_url($url);
        if (!isset($sarr['query'])) {
            return [];
        }
        $query      = $sarr['query'];
        $queryParts = explode('&', $query);
        $params     = array();
        foreach ($queryParts as $param) {
            $item             = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }


    /**
     * 指定开始位置 用*号代替
     * @param $string
     * @param $start
     * @param $length
     * @return string
     */
    public static function replaceStar($string, $start, $length): string
    {
        $markStr = str_repeat('*', $length);
        if (!is_string($string)) {
            return false;
        }
        $status = Base::isAllChinese($string);
        if ($status) {
            $_length = mb_strlen($string);
            # 截取字符前面部分
            $first_str = mb_substr($string, 0, $start, "utf-8");
            # 截取字符后面部分
            $last_str = mb_substr($string, $length + $start, $_length, "utf-8");
            # 拼接字符串
            return $first_str . $markStr . $last_str;

        } else {
            $_length = strlen($string);
            if ($start > $_length || $length > $_length) {
                return false;
            }
            return substr_replace($string, $markStr, $start, $length);
        }
    }

    /**
     * 产生随机字符串
     * @param int $length
     * @param string $chars
     * @return string
     */
    public static function random(int $length, string $chars = '0123456789'): string
    {
        $hash = '';
        $max  = strlen($chars) - 1;
        mt_srand();
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * PHP截取UTF-8字符串，解决半字符问题。
     * @param string $str 源字符串
     * @param int $len 左边的子串的长度
     * @param int $start 何处开始
     * @return string           取出的字符串, 当$len小于等于0时, 会返回整个字符串
     */
    public static function utf8Substr(string $str, int $len, int $start = 0): string
    {
        $len     = $len * 2;
        $new_str = [];
        for ($i = 0; $i < $len; $i++) {
            $temp_str = substr($str, 0, 1);
            if (ord($temp_str) > 127) {
                $i++;
                if ($i < $len) {
                    $new_str[] = substr($str, 0, 3);
                    $str       = substr($str, 3);
                }
            } else {
                $new_str[] = substr($str, 0, 1);
                $str       = substr($str, 1);
            }
        }
        return join(array_slice($new_str, $start));
    }


    /**
     * 指定字符串 （支持中文） 替换
     * @param string $str 规定被搜索的字符串
     * @param string $find 规定要查找的值
     * @param string $replace 规定替换的值
     * @return string 返回替换的结果
     */
    public static function utf8_str_replace(string $str, string $find, string $replace): string
    {
        # 记录位置
        $strpos = 0;
        # 储存替换的字符串
        $strstr = $str;
        # $find在$str中查找到的次数
        $count = mb_substr_count($str, $find, "utf-8");
        # 遍历替换
        for ($i = 0; $i < $count; $i++) {
            # 获取当前查找到的字符位置
            $strpos = mb_strpos($strstr, $find, $strpos, "utf-8");
            # 获取查找的值的长度
            $chr_len = mb_strlen($find, "utf-8");
            # 截取字符前面部分
            $first_str = mb_substr($strstr, 0, $strpos, "utf-8");
            # 截取字符后面部分
            $last_str = mb_substr($strstr, $strpos + $chr_len);
            # 拼接字符串
            $strstr = $first_str . $replace . $last_str;
            # 计算下次的位置
            $strpos += mb_strlen($replace, "utf-8");
        }
        return $strstr;
    }

    /**
     * 判断是否全部是汉字
     * @param string $str
     * @return bool
     */
    public static function isAllChinese(string $str): bool
    {
        # 使用更准确的正则表达式来匹配汉字
        return preg_match('/^\p{Han}+$/u', $str) === 1;
    }

    /**
     * 判断是否是汉字 + 数字
     * @param string $str
     * @return bool
     */
    public static function isChineseAndNumberOnly(string $str): bool
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}0-9]+$/u', $str) === 1;
    }

    /**
     * 判断是否是汉字 + 字母
     * @param string $str
     * @return bool
     */
    public static function isChineseAndLetterOnly(string $str): bool
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z]+$/u', $str) === 1;
    }


    /**
     * 判断时间是否 到点
     * @param $at_str 00:05:00
     * @return bool
     */
    public static function isTimeUp($at_str): bool
    {
        if (!Base::isTime($at_str)) {
            return false;
        }
        $at    = date('Y-m-d') . ' Base.php' . trim($at_str);
        $time  = strtotime($at);
        $_time = time();
        if ($_time >= $time) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 检测日期格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isDate(string $str): bool
    {
        $strArr = explode('-', $str);
        if (empty($strArr) || count($strArr) != 3) {
            return false;
        } else {
            list($year, $month, $day) = $strArr;
            if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day)) {
                return false;
            }
            if (checkdate($month, $day, $year)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 检测时间格式
     * @param string $str 需要检测的字符串
     * @return bool
     */
    public static function isTime(string $str): bool
    {
        $strArr = explode(':', $str);
        if (empty($strArr) || count($strArr) != 3) {
            return false;
        } else {
            list($hour, $minute, $second) = $strArr;
            if (intval($hour) > 23 || intval($minute) > 59 || $second > 59) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * 补全 URL
     * @param array|string $str
     * @param string|null $url
     * @return array|string
     */
    public static function fillUrl($str, string $url = null)
    {
        if (empty($str)) {
            return $str;
        }
        # 处理数组情况
        if (is_array($str)) {
            return array_map(function ($item) use ($url) {
                return self::fillUrl($item, $url);
            }, $str);
        }
        # 定义有效 URL 前缀
        $validPrefixes = [
            'http://',
            'https://',
            'ftp://',
            'data:image/',
            '//'
        ];
        # 检查是否是有效的 URL
        foreach ($validPrefixes as $prefix) {
            if (strpos($str, $prefix) === 0) {
                return $str;
            }
        }
        # 获取默认 URL 如果未提供
        $baseUrl = $url ?? config('upload.url');
        # 确保拼接时避免多余的斜杠
        return rtrim($baseUrl, '/') . 'Base.php/' . ltrim($str, '/');
    }


    /**
     * 去除 URL
     * @param array|string $str
     * @return array|string
     */
    public static function unFillUrl($str)
    {
        $url = config('upload.url') ?? url('');
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                $str[$key] = self::unFillUrl($item);
            }
            return $str;
        }
        $urlStr = $url . '/';
        return self::leftDelete($str, $urlStr);
    }

    /**
     * 删除开头指定字符串
     * @param string $string
     * @param string $find
     * @param bool $lower
     * @return string
     */
    public static function leftDelete(string $string, string $find, bool $lower = false): string
    {
        if (self::leftExists($string, $find, $lower)) {
            $string = substr($string, strlen($find));
        }
        return $string;
    }

    /**
     * 判断字符串开头包含
     * @param string $str 原字符串
     * @param string $find 判断字符串
     * @param bool|false $lower 是否不区分大小写
     * @return bool
     */
    public static function leftExists(string $str, string $find, bool $lower = false): bool
    {
        if (empty($str) || empty($find)) {
            return false;
        }
        if ($lower) {
            $str  = strtolower($str);
            $find = strtolower($find);
        }
        return (substr($str, 0, strlen($find)) == $find);
    }


    /**
     * 加密
     * @param string $input
     * @param string $key
     * @return string
     */
    public static function encrypt(string $input, string $key): string
    {
        $key2 = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $data = openssl_encrypt($input, 'aes-128-ecb', $key2, OPENSSL_RAW_DATA);
        return base64_encode($data);
    }

    /**
     * 解密
     * @param string $str
     * @param string $key
     * @return string
     */
    public static function decrypt(string $str, string $key): string
    {
        $key2      = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $encrypted = base64_decode($str);
        return openssl_decrypt($encrypted, 'aes-128-ecb', $key2, OPENSSL_RAW_DATA);
    }


    public static function color(string $str, string $color): string
    {
        if ($color == 'red') {
            return "<span style='color:red'>" . $str . "</span>";
        } elseif ($color == 'green') {
            return "<span style='color:green'>" . $str . "</span>";
        } elseif ($color == 'blue') {
            return "<span style='color:blue'>" . $str . "</span>";
        } elseif ($color == 'white') {
            return "<span style='color:white'>" . $str . "</span>";
        } elseif ($color == 'orange') {
            return "<span style='color:orange'>" . $str . "</span>";
        } else {
            return "<span style='color:" . $color . "'>" . $str . "</span>";
        }
    }

    public static function tag(string $str, string $color = 'red'): string
    {
        if (empty($color)) {
            $color = '#666666';
        }
        if ($color == 'red') {
            $bColor = '#fff1f0';
        } elseif ($color == 'orange') {
            $bColor = '#fff7e6';
        } elseif ($color == 'green') {
            $bColor = '#f6ffed';
        } elseif ($color == 'blue') {
            $bColor = '#e6f7ff';
        } elseif ($color == 'grey') {
            $color  = '#ffffff';
            $bColor = '#d2d2d2';
        } else {
            $bColor = '#fafafa';
        }
        return '<span style="background:' . $bColor . ';color:' . $color . '; border: 1px solid ' . $color . ';border-radius: 4px;padding:2px 4px;font-size:12px;">' . $str . '</span>';
    }

    /**
     * 判断姓名是否符合身份证规则
     * @param string $name
     * @return bool
     */
    public static function isIdcardName(string $name): bool
    {
        # 中文+身份证允许有.
        if (!preg_match('/^[\x{4e00}-\x{9fa5}]+[·•]?[\x{4e00}-\x{9fa5}]+$/u', $name)) {
            return false;
        }
        $strLen = mb_strlen($name);
        # 字符长度2到8之间
        if ($strLen < 2 || $strLen > 8) {
            return false;
        }
        return true;
    }


    /**
     * 获取版本号
     * @param string $version
     * @return string
     */
    public static function getNextVersion(string $version): string
    {
        if (empty($version)) {
            return '1.0.1';
        }
        # 将版本号字符串按点号分割成数组
        $versionParts = explode('.', $version);
        # 获取版本号的每个部分
        $major = intval($versionParts[0]);    # 主版本号
        $minor = intval($versionParts[1]);    # 次版本号
        $patch = intval($versionParts[2]);    # 修订号
        # 判断修订号加1后是否大于等于100，如果是，则增加次版本号，并将修订号重置为 0
        if ($patch + 1 >= 100) {
            $minor += 1;
            $patch = 0;
        } else {
            $patch += 1;
        }
        # 判断次版本号加1后是否大于等于100，如果是，则增加主版本号，并将次版本号重置为 0
        if ($minor >= 100) {
            $major += 1;
            $minor = 0;
        }
        $patch = str_pad($patch, 2, '0', STR_PAD_LEFT);
        # 构造新的版本号字符串
        return $major . '.' . $minor . '.' . $patch;
    }

    /**
     * 比较版本号 返回最大的版本号
     * @param string $str1
     * @param string $str2
     * @return string
     */
    public static function getMaxVersion(string $str1, string $str2): string
    {
        # 按点号分割字符串为数组
        $arr1 = explode('.', $str1);
        $arr2 = explode('.', $str2);
        # 逐个比较数组元素
        for ($i = 0; $i < count($arr1); $i++) {
            if ($arr1[$i] > $arr2[$i]) {
                return $str1;   #  第一个字符串大于第二个字符串
            } elseif ($arr1[$i] < $arr2[$i]) {
                return $str2;   #  第一个字符串小于第二个字符串
            }
        }
        return $str1;   # 字符串相等，返回第一个字符串
    }

    /**
     * 是否版本号格式
     * @param string $str
     * @return bool
     */
    public static function isVersionFormat(string $str): bool
    {
        $pattern = '/^\d{1,3}\.\d{1,3}\.\d{1,3}$/';
        return preg_match($pattern, $str) === 1;
    }

    /**
     * 将字节大小格式化为人类可读的单位（KB 或 MB）
     * @param $bytes
     * @return string
     */
    public static function formatSizeUnits($bytes)
    {
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($sizes) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $sizes[$i];
    }


    /**
     * 是否是32位的 md5 值
     * @param $string
     * @return bool
     */
    public static function isValidMd5($string): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', $string) === 1;
    }


    /**
     * 向 config/app.php 的 providers 数组添加服务提供者
     *
     * @param string $provider 要添加的服务提供者类名
     * @return void
     */
    public static function addServiceProviderToConfig(string $provider): void
    {
        $configPath = config_path('app.php');

        # 检查配置文件是否存在
        if (!File::exists($configPath)) {
            echo "❌ 配置文件 config/app.php 不存在";
            return;
        }

        try {
            # 读取配置文件内容
            $content = File::get($configPath);
            $config  = require $configPath;

            # 检查是否已存在该服务提供者
            if (isset($config['providers']) && is_array($config['providers'])) {
                if (in_array($provider, $config['providers'])) {
                    echo "✅ Antmin 服务提供者已存在，无需添加";
                    return;
                }
            } else {
                echo "❌ 配置文件 config/app.php 不存在";
                return;
            }

            # 查找 providers 数组在文件中的位置
            $pattern = "/'providers' => \[(.*?)\],/s";
            if (preg_match($pattern, $content, $matches)) {
                $providersBlock   = $matches[0];
                $providersContent = $matches[1];

                # 在 providers 数组末尾添加新的服务提供者
                $newProviderLine = "\n        " . $provider . ",";

                # 检查最后一个字符是否是 ]，如果是则在之前添加
                if (strpos(trim($providersContent), ']') === strlen(trim($providersContent)) - 1) {
                    $newProvidersContent = substr(trim($providersContent), 0, -1) . $newProviderLine . "\n    ]";
                } else {
                    $newProvidersContent = $providersContent . $newProviderLine;
                }

                $newProvidersBlock = str_replace($providersContent, $newProvidersContent, $providersBlock);
                $newContent        = str_replace($providersBlock, $newProvidersBlock, $content);

                # 备份原文件
                File::copy($configPath, $configPath . '.backup_' . date('YmdHis'));

                # 写入新内容
                File::put($configPath, $newContent);
                echo "✅ Antmin 成功添加服务提供者到配置文件";
                return;
            } else {
                echo "❌ 未能在配置文件中找到 providers 数组";
                return;
            }

        } catch (\Exception $e) {
            echo "❌ ".$e->getMessage();
            return;
        }
    }

}
