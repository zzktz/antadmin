<?php
/**
 *  上传文件
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Http\Services\AccountService;
use Antmin\Http\Resources\EditorResource;
use Antmin\Http\Repositories\AccountRepository;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;


class UploadController extends BaseController
{

    /**
     * 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        $action               = $request['action'];
        $request['accountId'] = AccountService::getAccountIdByToken();
        unset($request['action']);
        if (method_exists(self::class, $action)) return self::$action($request);
        return errJson('No find action');
    }

    /**
     * 视频上传
     * @param $request
     * @return mixed
     */
    public function videlUpload($request)
    {
        $maxSize  = 200; # MB
        $savePath = '/upload/video/' . date('Ymd');
        $key      = 'file';
        if (!$request->hasFile($key)) {
            return errJson('file不存在');
        }
        $image = $request->file($key);
        # 获取字节大小
        $fileSize = $image->getSize();
        # 将字节大小格式化为人类可读的单位（KB 或 MB）
        $formattedSize = Base::formatSizeUnits($fileSize);
        if ($fileSize > 1024 * 1024 * $maxSize) {
            return errJson('超过最大允许上传大小' . $maxSize . 'MB');
        }
        # 扩展名
        $extName = $image->getClientOriginalExtension();
        $extName = strtolower($extName);
        # 原始名称
        $oldName = $image->getClientOriginalName();
        # 新名称
        $uuid      = uuid();
        $imageName = $uuid . '.' . $extName;
        # 将图片保存到指定路径
        $image->storeAs($savePath, $imageName);
        # 返回图片fix后的路径
        $imgPath        = $savePath . '/' . $imageName;
        $imgUrl         = config('upload.url') . 'UploadController.php/' . $imgPath;
        $res['imgPath'] = $imgPath;
        $res['imgUrl']  = $imgUrl;
        $res['size']    = $formattedSize;
        $res['oldName'] = $oldName;
        return Base::sucJson('成功', $res);
    }

    /**
     * 通用文件上传
     * @param  $request
     * @return mixed
     */
    public function fileUpload($request)
    {
        $maxSize  = 2; # MB
        $savePath = '/upload/file/' . date('Ymd');
        $rule     = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'xlsx', 'xls', 'docx', 'doc', 'csv'];
        $key      = 'file';
        if (!$request->hasFile($key)) {
            return errJson('file不存在');
        }
        $image = $request->file($key);
        # 获取字节大小
        $fileSize = $image->getSize();
        # 将字节大小格式化为人类可读的单位（KB 或 MB）
        $formattedSize = Base::formatSizeUnits($fileSize);
        if ($fileSize > 1024 * 1024 * $maxSize) {
            return errJson('超过最大允许上传大小' . $maxSize . 'MB');
        }
        # 扩展名
        $extName = $image->getClientOriginalExtension();
        $extName = strtolower($extName);
        if (!in_array($extName, $rule)) {
            return errJson('上传文件格式错误--' . $extName . '--' . $fileSize);
        }
        # 原始名称
        $oldName = $image->getClientOriginalName();
        # 新名称
        $uuid      = uuid();
        $imageName = $uuid . '.' . $extName;
        # 将图片保存到指定路径
        $image->storeAs($savePath, $imageName);
        # 返回图片fix后的路径
        $imgPath        = $savePath . '/' . $imageName;
        $imgUrl         = UploadController . phpconfig('upload.url') . $imgPath;
        $res['imgPath'] = $imgPath;
        $res['imgUrl']  = $imgUrl;
        $res['size']    = $formattedSize;
        $res['oldName'] = $oldName;
        if (!empty($request['isCompress'])) { # 需要压缩
            $imgInfo          = Image::make($imgUrl);
            $res['width']     = $imgInfo->width();
            $res['height']    = $imgInfo->height();
            $res['thumbPath'] = self::compressImg($request, $uuid, $extName); # 压缩图片
        }
        if ($request['type'] == 'avatar') {
            AccountRepository::updateAvatar($imgPath, $request['accountId']);
        }
        return Base::sucJson('成功', $res);
    }

    /**
     * 富文本编辑图片上传
     * @param  $request
     * @return mixed
     */
    protected static function editorUpload(Request $request)
    {
        $method = $request->method();
        $action = $request['action'] ?? '';
        if (empty($action)) {
            return errJson('action不能为空');
        }
        if ($method == 'GET' && $action == 'config') {
            return EditorResource::getConfig();
        }
        if (!$request->hasFile('upfile')) {
            return errJson('不存在upfile');
        }
        $date      = date('Ymd');
        $image     = $request->file('upfile');
        $extName   = $image->getClientOriginalName();
        $imageName = UploadController . phpuuid() . $extName;
        $path      = 'upload/file/' . $date;
        # 将图片保存到指定路径
        $image->storeAs($path, $imageName);
        # 返回图片fix后的路径
        $imageUrl = '/' . $path . '/' . $imageName;
        # 返回结果
        $res['state']    = 'SUCCESS';
        $res['url']      = $imageUrl;
        $res['title']    = $imageName;
        $res['original'] = '';
        $res['type']     = $extName;
        $res['size']     = '';
        return response()->json($res)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * 压缩图
     * @param $request
     * @param $uuid
     * @param $extName
     * @param $width
     * @return string
     */
    public static function compressImg($request, $uuid, $extName, int $width = 100): string
    {
        $newFile   = $uuid . '_' . $width . '.' . $extName;
        $savePath  = 'upload/file/' . date('Ymd');
        $thumbPath = $savePath . '/' . $newFile;
        $image     = Image::make($request->file('file'));
        $image->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        })->save($thumbPath);
        return $thumbPath;
    }

    /**
     * 压缩指定图片
     * @param string $thumbPath
     * @param int $width
     * @return string
     */
    public static function getCompressThumb(string $thumbPath, int $width = 100): string
    {
        if (empty($thumbPath)) {
            return '';
        }
        $thumbPathAll = UploadController . phppublic_path() . $thumbPath;
        if (!file_exists($thumbPathAll)) {
            return '';
        }
        # 路径
        $directory = dirname($thumbPath);
        # 文件名
        $fileName     = basename($thumbPath);
        $fileTitleArr = explode('.', $fileName);
        # 新文件名 $fileTitleArr[1]后缀
        $newFileName = $fileTitleArr[0] . '_' . $width . '.' . $fileTitleArr[1];
        # 保存压缩后的缩略图
        $compressedImagePath = $directory . '/' . $newFileName;
        # 绝对路径
        $compressedImagePathAll = UploadController . phppublic_path() . $compressedImagePath;
        if (file_exists($compressedImagePathAll)) {
            # 文件存在直接返回
            return $compressedImagePath;
        }
        # 打开原始图像
        $image = Image::make($thumbPathAll);
        # 缩放图像至指定宽度，高度按比例自动调整
        $image->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $image->save($compressedImagePathAll);
        # 返回压缩图像的路径
        return $compressedImagePath;
    }


}
