<?php
namespace App\Support;

use Illuminate\Support\Facades\Mail;
/**
 * Created by PhpStorm.
 * Date: 2018/05/23
 * Time: 18:56
 *
 * @author huan
 */
trait SendMessagesTrait
{
    protected $email;
    protected $title;
    /**
     * 发送邮箱验证码
     * @param string $title
     * @param string $email
     * @param string $text
     * @return array
     */
    protected function send_email($title,$email,$text){
        $this->email = $email;
        $this->title = $title;
        Mail::raw($text, function ($message) {
            $to = $this->email;
            $message ->to($to)->subject($this->title);
        });
        $res =Mail::failures();

        if (empty($res)) {
            return array('status'=>1,'msg'=>'发送成功');
        }

        return array('status'=>0,'msg'=>'发送失败');
    }

}

