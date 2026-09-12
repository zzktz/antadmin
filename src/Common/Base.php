<?php
/**
 *  基础
 */

namespace Antmin\Common;

use Validator;
use Antmin\Exceptions\CommonException;
use Illuminate\Http\JsonResponse;

class Base
{

    /**
     * 判断当前是否为开发环境。
     */
    public static function isDev(): bool
    {
        return app()->environment(['local', 'development', 'testing', 'dev']);
    }


    public static function errJson(string $msg, array $data = [], int $code = 0, int $statusCode = 200): JsonResponse
    {
        $res['useTime'] = self::getUseTime();
        $res['status']  = "fail";
        $res['code']    = $code;
        $res['message'] = $msg;
        $res['data']    = $data;
        return response()->json($res, $statusCode)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public static function sucJson(string $msg, array $data = [], int $code = 0): JsonResponse
    {

        $res['useTime'] = self::getUseTime();
        $res['status']  = "success";
        $res['code']    = $code;
        $res['message'] = $msg;
        $res['data']    = $data;
        return response()->json($res)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public static function getUseTime(): string
    {
        # 记录请求结束时间
        $endTime = microtime(true);
        # 计算请求执行时间
        $requestStart = (float) request()->server('REQUEST_TIME_FLOAT', $endTime);
        $executionTime = max(0, $endTime - $requestStart);
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

    public static function isEmail(string $email): bool
    {
        return Validator::make(
            ['email' => $email],
            ['email' => 'email']  // 添加规则
        )->passes();
    }


    /**
     * 图片上传地址
     * @param string $savePath
     * @return string
     */
    public static function getUploadUrl(string $savePath): string
    {
        if (self::isDev()) {
            return rtrim((string) config('antmin.upload.url', config('upload.url', '')), '/') . '/' . ltrim($savePath, '/');
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
        $result = json_decode(json_encode($object), true);
        return is_array($result) ? $result : [];
    }

    /**
     * 页码列表格式输出  从数据库中获取
     * @param int $limit
     * @param $query
     * @return array
     */
    public static function listFormat(int $limit, $query): array
    {
        $limit = max(1, min($limit, 1000));
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
            $input = is_object($request) && method_exists($request, 'all') ? $request->all() : (array) $request;
            $validator = Validator::make($input, [$field => $validateRule], [], [$field => $fieldName]);
            if ($validator->fails()) {
                $message = empty($msg) ? $validator->errors()->first() : $msg;
                throw new CommonException($message);
            }
        }
        if (is_object($request) && method_exists($request, 'input')) {
            return $request->input($field);
        }
        return is_array($request) ? ($request[$field] ?? null) : null;
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
            if ($param === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $param, 2), 2, '');
            $params[urldecode($key)] = urldecode($value);
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
            return '';
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
                return '';
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
        if ($length <= 0) {
            return '';
        }
        if ($chars === '') {
            throw new \InvalidArgumentException('随机字符集不能为空');
        }
        $hash = '';
        $max  = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[random_int(0, $max)];
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
        $at    = date('Y-m-d') . ' ' . trim($at_str);
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
        if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/', trim($str), $matches) !== 1) {
            return false;
        }
        return (int) $matches[1] <= 23
            && (int) $matches[2] <= 59
            && (int) $matches[3] <= 59;
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
        if (!is_string($str)) {
            return (string) $str;
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
            if (str_starts_with($str, $prefix)) {
                return $str;
            }
        }
        # 获取默认 URL 如果未提供
        $baseUrl = $url ?? config('antmin.upload.url', config('upload.url', ''));
        # 确保拼接时避免多余的斜杠
        return rtrim((string) $baseUrl, '/') . '/' . ltrim($str, '/');
    }


    /**
     * 去除 URL
     * @param array|string $str
     * @return array|string
     */
    public static function unFillUrl($str)
    {
        $url = config('antmin.upload.url', config('upload.url', url('')));
        if (is_array($str)) {
            foreach ($str as $key => $item) {
                $str[$key] = self::unFillUrl($item);
            }
            return $str;
        }
        if (!is_string($str)) {
            return $str;
        }
        $urlStr = rtrim((string) $url, '/') . '/';
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
        return (str_starts_with($str, $find));
    }


    /**
     * 加密
     * @param string $input
     * @param string $key
     * @return string
     */
    public static function encrypt(string $input, string $key): string
    {
        $key2 = hash('sha256', $key, true);
        $iv   = random_bytes(12);
        $tag  = '';
        $data = openssl_encrypt($input, 'aes-256-gcm', $key2, OPENSSL_RAW_DATA, $iv, $tag);
        if ($data === false) {
            throw new \RuntimeException('数据加密失败');
        }
        return base64_encode("\x02" . $iv . $tag . $data);
    }

    /**
     * 解密
     * @param string $str
     * @param string $key
     * @return string
     */
    public static function decrypt(string $str, string $key): string
    {
        $encrypted = base64_decode($str);
        if ($encrypted !== false && strlen($encrypted) >= 29 && $encrypted[0] === "\x02") {
            $key2 = hash('sha256', $key, true);
            $iv   = substr($encrypted, 1, 12);
            $tag  = substr($encrypted, 13, 16);
            $data = substr($encrypted, 29);
            $plain = openssl_decrypt($data, 'aes-256-gcm', $key2, OPENSSL_RAW_DATA, $iv, $tag);
            if ($plain === false) {
                throw new \RuntimeException('数据解密失败');
            }
            return $plain;
        }

        # 兼容历史 AES-128-ECB 数据，新的数据不再使用无认证加密。
        $legacyKey = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        return (string) openssl_decrypt((string) $encrypted, 'aes-128-ecb', $legacyKey, OPENSSL_RAW_DATA);
    }


    public static function color(string $str, string $color): string
    {
        $safeColor = self::safeColor($color, 'inherit');
        return "<span style='color:" . $safeColor . "'>" . htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</span>";
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
        $color = self::safeColor($color, '#666666');
        return '<span style="background:' . $bColor . ';color:' . $color . '; border: 1px solid ' . $color . ';border-radius: 4px;padding:2px 4px;font-size:12px;">' . htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
    }

    private static function safeColor(string $color, string $default): string
    {
        return preg_match('/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]+)$/', $color) === 1 ? $color : $default;
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
        return version_compare($str1, $str2, '>') ? $str1 : $str2;
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


}
