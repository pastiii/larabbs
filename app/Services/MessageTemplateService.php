<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/13
 * Time: 17:36
 */

namespace App\Services;


class MessageTemplateService
{
    /**
     * 邮件文案
     * @return string
     */
    public function emailCopyWriting()
    {
        $data['code']    = random_int(100000, 999999);
        $data['time']    = 10;
        $data['subject'] = "EmailCode";
        $data['name']    = "HangZhou";
        $data['content'] = "<table cellpadding='0' cellspacing='0' style='border: 1px solid #cdcdcd; width: 640px; margin:auto;font-size: 12px; color: #1E2731; line-height: 20px;'>
        <tr>
            <td colspan='3' align='center' style='background-color:#454c6d; height: 55px; padding: 30px 0'><a href='https://www.kf.com' target='_blank'></a></td>
        </tr>
        <tr style='height: 30px;'>&nbsp;</tr>

        <tr>
            <td width='20'></td>
            <td style='line-height: 40px'>
        Hello,<br>

    Welcome to KF!<br>

                You have recently received instructions to enter a one-time authentication code to create your KF account.<br>

    Your code is: <b>" .$data['code']. "<br>

                For security reasons, this code will expire in " . $data['time'] . " minutes.<br>
                </td>
            <td width='20'></td>
        </tr>
       <tr style='height: 20px;'>&nbsp;</tr>
        <tr>
            <td width='20'></td>
            <td>
    Sincerely,<br>
    The KF Team<br>
                <a href='https://www.kf.com'>https://www.kf.com</a><br>
            </td>
            <td width='20'></td>
        </tr>
    <tr style='height: 50px;'>&nbsp;</tr>
</table>";

        return $data;
    }

    /**
     * 登录,注册验证码文案
     * @param $type
     */
    public function phoneCodeCopyWriting($type)
    {
        $verification_code = str_pad(random_int(1, 999999), 6, 0, STR_PAD_LEFT);
        $data['code'] = $verification_code;
        $data['hour'] = $type == "+86" ? "10分钟" : "10 min";
        $data['type'] = $type == "+86" ? 3 : 1;
        return $data;
    }

    /**
     * 登录异地提示文案
     * @param $type
     */
    public function phoneLoginCopyWriting($type, $user_name)
    {
        $data['name'] = mb_substr($user_name, 0, 2) . "***";
        $data['time'] = date("H:i",time());
        $data['type'] = $type == "+86" ? 4 : 2;
        return $data;
    }
}