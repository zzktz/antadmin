<?php
/**
 * 上传文件控制器
 */

namespace Antmin\Http\Controllers;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Resources\EditorResource;
use Antmin\Http\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class UploadController extends BaseController
{


    public function __construct(
        protected AccountRepository $accountRepo,
    )
    {

    }

    /**
     * 允许的文件类型配置
     */
    const ALLOWED_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
        'file'  => ['xlsx', 'xls', 'docx', 'doc', 'csv', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv']
    ];

    /**
     * 文件大小限制（MB）
     */
    const SIZE_LIMITS = [
        'image' => 2,
        'file'  => 10,
        'video' => 200
    ];

    /**
     * 入口
     * @param Request $request
     * @return mixed
     */
    public function operate(Request $request)
    {
        $action = $request['action'];
        if (method_exists(self::class, $action)) return $this->$action($request);
        throw new CommonException('System Not Find Action');
    }


    /**
     * 图片上传
     * @param  $request
     * @return mixed
     */
    protected function imageUpload($request)
    {
        return $this->handleFileUpload($request, 'image', 'file');
    }

    /**
     * 视频上传
     * @param  $request
     * @return mixed
     */
    protected function videoUpload($request)
    {
        return $this->handleFileUpload($request, 'video', 'file');
    }

    /**
     * 通用文件上传
     * @param  $request
     * @return mixed
     */
    protected function fileUpload($request)
    {
        return $this->handleFileUpload($request, 'file', 'file');
    }


    /**
     * 通用文件上传处理
     * @param Request $request
     * @param string $fileType
     * @param string $fileKey
     * @return mixed
     */
    private function handleFileUpload(Request $request, string $fileType, string $fileKey)
    {
        # 验证文件存在性
        if (!$request->hasFile($fileKey)) {
            throw new CommonException($fileKey . "不存在");
        }

        $file = $request->file($fileKey);

        #  验证文件有效性
        if (!$file->isValid()) {
            throw new CommonException('文件无效');
        }

        try {
            #  获取文件信息
            $fileSize      = $file->getSize();
            $extension     = strtolower($file->getClientOriginalExtension());
            $originalName  = $file->getClientOriginalName();
            $formattedSize = Base::formatSizeUnits($fileSize);

            #  验证文件大小
            $maxSize = self::SIZE_LIMITS[$fileType] ?? 2;
            if ($fileSize > 1024 * 1024 * $maxSize) {
                throw new CommonException("超过最大允许上传大小 {$maxSize}MB");
            }

            #  验证文件类型
            $allowedExtensions = self::ALLOWED_EXTENSIONS[$fileType] ?? [];
            if (!in_array($extension, $allowedExtensions)) {
                throw new CommonException("不支持的文件格式: {$extension}");
            }

            #  生成存储路径和文件名
            $savePath = "/upload/{$fileType}/" . date('Ymd');
            $uuid     = uuid();
            $fileName = $uuid . '.' . $extension;

            #  存储文件
            $file->storeAs($savePath, $fileName, 'public');

            #  构建响应数据
            $filePath = $savePath . '/' . $fileName;
            $fileUrl  = config('upload.url') . $filePath;

            $responseData = [
                'filePath'     => $filePath,
                'fileUrl'      => $fileUrl,
                'size'         => $formattedSize,
                'originalName' => $originalName,
                'extension'    => $extension,
            ];
            #  处理上传后的操作
            $this->handlePostUploadActions($request, $responseData);
            #  关键修复：从请求中移除文件对象，防止序列化问题
            $request->files->remove($fileKey);

            return Base::sucJson('文件上传成功', $responseData);

        } catch (Exception $e) {
            Log::error($fileType . "上传失败", [
                'file'  => $originalName ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new CommonException('文件上传失败，请稍后重试');
        }
    }

    /**
     * 处理上传后的操作
     * @param Request $request
     * @param array $fileData
     * @return void
     */
    private function handlePostUploadActions(Request $request, array $fileData): void
    {
        try {
            #  头像更新处理
            if ($request->input('type') === 'avatar') {
                $this->updateUserAvatar($fileData['filePath'] ?? '', $request->input('accountId'));
            }
        } catch (Exception $e) {
            Log::error('上传后处理失败', [
                'error'    => $e->getMessage(),
                'fileData' => $fileData
            ]);
        }
    }


    /**
     * 更新用户头像
     * @param string $avatarPath
     * @param mixed $accountId
     * @return void
     */
    private function updateUserAvatar(string $avatarPath, $accountId): void
    {
        try {
            if (!empty($avatarPath) && !empty($accountId)) {
                # 更新用户头像
                $this->accountRepo->updateAvatar($avatarPath, $accountId);
            }
        } catch (Exception $e) {
            Log::error('更新用户头像失败', [
                'accountId'  => $accountId,
                'avatarPath' => $avatarPath,
                'error'      => $e->getMessage()
            ]);
        }
    }

    /**
     * 富文本编辑器图片上传
     * @param Request $request
     * @return mixed
     */
    public function editorUpload(Request $request)
    {
        $method = $request->method();
        $action = $request->input('action', '');

        if (empty($action)) {
            throw new CommonException('action不能为空');
        }

        if ($method === 'GET' && $action === 'config') {
            return EditorResource::getConfig();
        }

        if (!$request->hasFile('upfile')) {
            throw new CommonException('不存在upfile');
        }

        try {
            $file         = $request->file('upfile');
            $date         = date('Ymd');
            $extension    = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();
            $fileName     = uuid() . '.' . $extension;
            $path         = "upload/file/{$date}";

            #  存储文件
            $file->storeAs($path, $fileName, 'public');

            #  构建响应
            $fileUrl = config('upload.url') . '/' . $path . '/' . $fileName;

            $response = [
                'state'    => 'SUCCESS',
                'url'      => $fileUrl,
                'title'    => $fileName,
                'original' => $originalName,
                'type'     => $extension,
                'size'     => Base::formatSizeUnits($file->getSize()),
            ];

            #  移除文件对象防止序列化问题
            $request->files->remove('upfile');

            return response()->json($response)->setEncodingOptions(JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error('富文本编辑器上传失败', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'ERROR',
                'msg'   => '上传失败'
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }
    }
}
