<?php


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class CodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * 创建一个新的消息实例。
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * 构建邮件消息。
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')) // 发件人
        ->subject('邮件验证码') // 主题
        ->view('emails.welcome'); // 指定邮件视图
    }
}
