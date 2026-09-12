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
        'image' => ['jpg', 'jpeg', 'png', 'gif'],
        'file'  => ['xlsx', 'xls', 'docx', 'doc', 'csv', 'pdf', 'jpg', 'jpeg', 'png', 'gif'],
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
        $action = (string) $request->input('action', '');
        if (in_array($action, ['imageUpload', 'videoUpload', 'fileUpload'], true)) {
            return $this->{$action}($request);
        }
        throw new CommonException('操作不存在');
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
            $this->validateMime($file, $extension, $fileType);

            #  生成存储路径和文件名
            $savePath = "/upload/{$fileType}/" . date('Ymd');
            $uuid     = uuid();
            $fileName = $uuid . '.' . $extension;

            #  存储文件
            $file->storeAs($savePath, $fileName, 'public');

            #  构建响应数据
            $filePath = $savePath . '/' . $fileName;
            $fileUrl  = rtrim((string) config('antmin.upload.url', config('upload.url', '')), '/') . '/' . ltrim($filePath, '/');

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

        } catch (CommonException $e) {
            throw $e;
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
        # 头像更新处理
        if ($request->input('type') === 'avatar') {
            $this->updateUserAvatar(
                $fileData['filePath'] ?? '',
                (int) $request->input('accountId', $request['accountId'] ?? 0),
                (int) ($request['accountId'] ?? 0)
            );
        }
    }


    /**
     * 更新用户头像
     * @param string $avatarPath
     * @param mixed $accountId
     * @return void
     */
    private function updateUserAvatar(string $avatarPath, int $accountId, int $operatorId): void
    {
        if ($accountId <= 0 || $operatorId <= 0 || ($accountId !== $operatorId && $operatorId !== 1)) {
            throw new CommonException('无权更新该头像');
        }
        if ($avatarPath !== '') {
            $this->accountRepo->updateAvatar($avatarPath, $accountId);
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
            if (!$file->isValid()) {
                throw new CommonException('文件无效');
            }
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, self::ALLOWED_EXTENSIONS['image'], true) || $file->getSize() > 5 * 1024 * 1024) {
                throw new CommonException('仅支持 5MB 以内的 JPG、PNG、GIF 图片');
            }
            $this->validateMime($file, $extension, 'image');
            $date         = date('Ymd');
            $originalName = $file->getClientOriginalName();
            $fileName     = uuid() . '.' . $extension;
            $path         = "upload/file/{$date}";

            #  存储文件
            $file->storeAs($path, $fileName, 'public');

            #  构建响应
            $fileUrl = rtrim((string) config('antmin.upload.url', config('upload.url', '')), '/') . '/' . $path . '/' . $fileName;

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

        } catch (CommonException $e) {
            return response()->json(['state' => 'ERROR', 'msg' => $e->getMessage()])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
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

    /**
     * 同时校验扩展名和实际 MIME，拒绝可执行文件伪装上传。
     */
    private function validateMime($file, string $extension, string $fileType): void
    {
        $mime = strtolower((string) $file->getMimeType());
        $mimeMap = [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'gif' => ['image/gif'],
            'mp4' => ['video/mp4'], 'avi' => ['video/x-msvideo', 'video/avi'], 'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'], 'flv' => ['video/x-flv'],
            'pdf' => ['application/pdf'], 'csv' => ['text/plain', 'text/csv', 'application/csv'],
            'doc' => ['application/msword'], 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
        if (!in_array($mime, $mimeMap[$extension] ?? [], true)) {
            throw new CommonException("文件内容与扩展名不匹配");
        }
    }
}
