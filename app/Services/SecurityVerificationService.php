<?php

namespace App\Services;
use App\Support\ApiRequestTrait;
use Illuminate\Support\Facades\Redis;

class SecurityVerificationService
{
    use ApiRequestTrait;

    protected $validationBaseUrl;
    protected $tokenBaseUrl;
    protected $sendBaseUrl;
    protected $countryBaseUrl;

    public function __construct()
    {
        $this->validationBaseUrl = env('VALIDATION_BASE_URL');
        $this->tokenBaseUrl      = env('TOKEN_BASE_URL');
        $this->sendBaseUrl      = env('SEND_BASE_URL');
        $this->countryBaseUrl = env('COMMON_COUNTRY_URL');

    }

    /**
     * 获取google secret qrcode
     * @param $sessionId
     * @param $secret
     * @return array
     */
    public function getGoogleSecret($sessionId,$user_name,$secret='')
    {
        $data['sessionId'] = $sessionId;
        $data['Authorization'] = 'token';
        $data['showName'] = $user_name;
        if (!empty($secret)) {
            $data['secret'] = $secret;
        }
        $url = 'captcha/googleauth/secret';
        return $this->send_request($url,'post',$data,$this->validationBaseUrl);
    }

    /**
     * 获取sessionId
     */
    public function getSessionId()
    {
        $url = 'captcha/googleauth/sessionid?Authorization=token';
        return $this->send_request($url,'post','',$this->validationBaseUrl);
    }

    /**
     * 验证google验证码
     * @param $data
     * @return array
     */
    public function checkGoogleVerify($data)
    {
        $url = 'captcha/googleauth/verify/'.$data['verify'].'/secret/'.$data['secret'];
        return $this->send_request($url,'get','',$this->validationBaseUrl);
    }

    /**
     * 获取验证token
     * @return array
     */
    public function createToken()
    {
        $url = "captcha/captcha?Authorization=token";
        return $this->send_request($url, 'post',"",$this->validationBaseUrl);
    }

    /**
     * 获取验证码
     * @param $data
     * @return array
     */
    public function getCaptchaCode($data)
    {
        $url      = "captcha/captcha/token/".$data['data']['data']['token']."?Authorization=token&output=base64";
        $response = $response = $this->send_request($url, 'get', "", $this->tokenBaseUrl);
        return ['captcha' => "data:image/png;base64,".$response['data']['data']['image'],'token' => $data['data']['data']['token']];
    }

    /**
     * 验证验证码
     * @param $data
     * @return array
     */
    public function checkCaptcha($data)
    {
        $ip = $this->getIp();
        $data['ClientIp'] = $ip;
        $url = "captcha/captcha/code/".$data['code']."/token/".$data['token']."?Authorization=token&ClientIp=" . $data['ClientIp'];
        return $this->send_request($url, 'get', "", $this->validationBaseUrl);
    }

    /**
     * 获取真实ip
     * @return array|false|string
     */
    public function getIp()
    {
        //判断服务器是否允许$_SERVER
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $real_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $real_ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $real_ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            //不允许就使用getenv获取
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $real_ip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $real_ip = getenv("HTTP_CLIENT_IP");
            } else {
                $real_ip = getenv("REMOTE_ADDR");
            }
        }

        return $real_ip;
    }

    /**
     * 发送邮件
     * @param $email
     * @param $data
     * @return array
     */
    public function sendEmail($email, $data)
    {
        $data['email']   = $email;
        $url = "notify/email";
        return $this->send_request($url, 'post', $data, $this->sendBaseUrl, [], "form_params");
    }

    /**
     * 发送手机code
     * @param $phone_info
     * @param $data
     * @return array|bool
     */
    public function sendSms($phone_info, $data)
    {
        $data['phone'] = $phone_info['phone_idd'] . $phone_info['phone_number'];
        $url = "notify/sms";
        return $this->send_request($url, 'post', $data, $this->sendBaseUrl, [], "form_params");
    }

    /**
     * 获取国家区域信息
     * @param $limit
     * @param $start
     * @return array
     */
    public function getCountyr($limit,$start){
        $url = 'common/country?limit='.$limit."&start".$start;
        return $this->send_request($url, 'get', [], $this->countryBaseUrl);
    }

}