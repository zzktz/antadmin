<?php
/**
 * 登录
 */

namespace Antmin\Http\Services;

use Antmin\Common\Base;
use Antmin\Common\Limit;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repositories\SmsRepository;
use Antmin\Http\Repositories\TokenRepository;
use Antmin\Http\Repositories\AccountRepository;
use Illuminate\Support\Facades\Hash;
use Exception;

class LoginService
{


    /**
     * 构造函数注入依赖
     */
    public function __construct(
        protected AccountRepository $accountRepo,
        protected TokenRepository   $tokenRepo,
        protected SmsRepository     $smsRepo,
    )
    {
        # 依赖已通过容器自动注入
    }

    /**
     * 账号登陆
     * @param string $name
     * @param string $password
     * @return string
     */
    public function accountLogin(string $name, string $password): string
    {
        # 安全检查
        SafeService::checking();
        try {
            if (Base::isEmail($name)) {
                $info = $this->accountRepo->getInfoByEmail($name);
            } else {
                $info = $this->accountRepo->getInfoByName($name);
            }
            if (empty($info)) {
                throw new CommonException('账户或密码错误');
            }
            if (!$this->isAccountActive($info)) {
                throw new CommonException('账号已被禁用');
            }
            $_password = (string) ($info['password'] ?? '');
            if ($_password === '' || !$this->checkPassword($password, $_password)) {
                throw new CommonException('账户或密码错误');
            }

            $accountId = $info['id'];
            $token     = $this->tokenRepo->getTokenById($accountId);
            # 成功
            SafeService::flagSuccess();
            return $token;
        } catch (Exception $e) {
            # 失败
            $num = SafeService::flagFail();
            $msg = '第' . $num . '次' . $e->getMessage() . '，' . SafeService::getMaxTip();
            throw new CommonException($msg);
        }
    }

    /**
     * 邮箱注册
     * @param string $email
     * @param string $code
     * @param string $password
     * @return string
     */
    public function register(string $email, string $code, string $password): string
    {
        $one = $this->accountRepo->getInfoByEmail($email);
        if (!empty($one)) {
            throw new CommonException('邮箱已注册');
        }
        $verify = EmailService::verifyCode($email, $code, 'register');
        if (empty($verify)) {
            throw new CommonException('邮箱验证码不正确');
        }
        # 进行注册
        # 密码哈希统一由账号仓储完成，避免重复哈希导致无法登录。
        $info['password'] = $password;
        $info['email']    = $email;
        $info['roles']    = [4];
        $accountId        = $this->accountRepo->add($info);
        return $this->tokenRepo->getTokenById($accountId);
    }

    /**
     * 发送邮件验证码
     * @param string $email
     * @return bool
     */
    public function sendCodeByEmail(string $email, string $type): bool
    {
        $info = $this->accountRepo->getInfoByEmail($email);
        if ($type == 'forget') {
            if (empty($info)) {
                throw new CommonException('邮箱未注册');
            }
        } else {
            if (!empty($info)) {
                throw new CommonException('邮箱已注册');
            }
        }
        $codeType = $type === 'forget' ? 'forget' : 'register';
        EmailService::sendCode($email, $codeType);
        return true;
    }


    /**
     * 短信登陆
     * @param string $mobile
     * @param string $smscode
     * @return string
     */
    public function mobileLogin(string $mobile, string $smscode): string
    {
        $key = 'login_sms_check_mobile_' . $mobile;
        if (!Limit::handle($key, 1, 10)) {
            throw new CommonException('您访问太快了，稍后再试');
        }
        $res = $this->smsRepo->checkSmsCode($mobile, $smscode, 300);
        if (!$res) {
            throw new CommonException('短信验证码错误');
        }
        $info = $this->accountRepo->getInfoByMobile($mobile);
        if (empty($info)) {
            throw new CommonException('手机号不存在');
        }
        if (!$this->isAccountActive($info)) {
            throw new CommonException('账号已被禁用');
        }
        return $this->tokenRepo->getTokenById($info['id']);
    }

    /**
     * 修改密码
     * @param string $email
     * @param string $password
     * @param string $code
     * @return bool
     */
    public function systemResetPassword(string $email, string $password, string $code): bool
    {
        $info = $this->accountRepo->getInfoByEmail($email);
        if (empty($info)) {
            throw new CommonException('邮箱未注册');
        }
        $verify = EmailService::verifyCode($email, $code, 'forget');
        if (empty($verify)) {
            throw new CommonException('邮箱验证码不正确');
        }

        $this->accountRepo->updatePassword($password, (int) $info['id']);
        return true;
    }

    /**
     * 账号状态必须在登录和 Token 鉴权时保持一致。
     */
    private function isAccountActive(array $info): bool
    {
        return (int) ($info['status'] ?? 0) === 1 && (int) ($info['deleted'] ?? 0) === 0;
    }

    /**
     * 兼容标准 MD5 登录协议和旧前端明文登录协议。
     */
    private function checkPassword(string $password, string $hashedPassword): bool
    {
        $normalized = preg_match('/^[a-f0-9]{32}$/i', $password) === 1
            ? strtolower($password)
            : $password;
        $candidates = [$normalized];
        if ($normalized === $password) {
            $candidates[] = md5($password);
        }

        foreach (array_unique($candidates) as $candidate) {
            if (Hash::check($candidate, $hashedPassword)) {
                return true;
            }
        }
        return false;
    }

}
