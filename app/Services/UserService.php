<?php

namespace App\Services;
use App\Support\ApiRequestTrait;
use Illuminate\Support\Facades\Redis;

/**
 * Class UserService
 * @package App\Services
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/17
 * Time: 16:12
 */
class UserService
{
    const TYPE = 1;
    use ApiRequestTrait;

    protected $authBaseUrl;

    public function __construct()
    {
        $this->authBaseUrl = env('AUTH_BASE_URL');
    }

    /**
     * 通过邮箱获取email
     * @param $data
     * @return array
     */
    public function getUserEmailByMail($data)
    {
        $email_url = "userauth/user_email/email/" . $data;
        return $this->send_request($email_url, 'get','',$this->authBaseUrl);
    }

    /**
     * 通过Id获取邮箱
     * @param $id
     * @return array
     */
    public function getUserEmailById($id)
    {
        $email_url = "userauth/user_email/id/" . $id;
        return $this->send_request($email_url, 'get','',$this->authBaseUrl);
    }

    /**
     * 通过用户名获取用户信息
     * @param $user_name
     * @return array
     */
    public function GetUserByName($user_name)
    {
        $url = "userauth/user/name/" . $user_name;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 创建用户
     * @param $data
     * @return array
     */
    public function createUser($data)
    {
        $url = "userauth/user";
        return $this->send_request($url, 'post', $data,$this->authBaseUrl);
    }

    /**
     * 通过用户id获取用户信息
     * @param $id
     * @return array
     */
    public function getUser($id)
    {
        $url  = "userauth/user/id/" . $id;
        $data = $this->send_request($url, 'get','',$this->authBaseUrl);
        return $data;
    }

    /**
     * 通过用户id获取email账号
     * @param $id
     * @return array
     */
    public function getEmailById($id)
    {
        $url = "userauth/user_email/id/" . $id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取用户状态
     * @param $id
     * @return array
     */
    public function getUserStatus($id)
    {
        $url = "userauth/user_status/id/" . $id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 开启,禁用二次验证,验证用户信息
     * @param $info
     * @param $id
     * @return mixed
     */
    public function bindingInfo($info, $id)
    {
        $data = [];
        if ($info['data']['second_phone_status'] == 1) {
            //获取用户手机信息
            $phone_info    = $this->getUserPhone($id);
            $data['phone'] = "phone";
            $data['phone_number'] = empty($phone_info['data']['phone_number']) ? "" : $phone_info['data']['phone_idd']." ". substr($phone_info['data']['phone_number'] , 0 , 3)."******".substr($phone_info['data']['phone_number'], -2,2);
        }

        if ($info['data']['second_email_status'] == 1) {
            //获取用户邮箱
            $email_info    = $this->getUserEmailById($id);
            $data['email'] = "email";
            $data['email_info'] = empty($email_info['data']['email']) ? "" : substr($email_info['data']['email'], 0, 3) . "*****" . strstr($email_info['data']['email'], "@", false);
        }

        if ($info['data']['second_google_auth_status'] == 1) {
            $data['google'] = "google";
        }

        return $data;
    }

    /**
     * 获取用户手机号
     * @param $id
     * @return array
     */
    public function getUserPhone($id)
    {
        $url = "userauth/user_phone/id/" . $id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 更新用户手机号
     * @param array $result
     * @param array $data
     * @return array
     */
    public function updatePhone($result, $data)
    {
        $url = "userauth/user_phone/id/" . $result['user_id'];
        return $this->send_request($url, 'patch', $data,$this->authBaseUrl);
    }

    /**
     * 绑定手机号
     * @param $info
     * @param $data
     * @return array
     */
    public function phone($info, $data)
    {
        $data['user_id']    = intval($info['user_id']);
        $data['user_name']  = $info['user_name'];
        $url = "userauth/user_phone";
        return $this->send_request($url, 'post', $data, $this->authBaseUrl);
    }

    /**
     * 更新用户密码
     * @param $id
     * @param $data
     * @return array
     */
    public function updateUserPassword($id, $data)
    {
        $url = "userauth/user/id/" . $id;
        return $this->send_request($url, 'patch', $data,$this->authBaseUrl);
    }

    /**
     * 更新用户状态
     * @param $user_id
     * @param $field
     * @param $type
     * @return array
     */
    public function updateUserStatus($user_id, $field, $type)
    {
        $type = $type['data'][$field] == 0 ? 'patch' : 'delete';
        $url = "userauth/user_status/id/" . $user_id . "/status/" . $field;
        return $this->send_request($url, $type,'',$this->authBaseUrl);
    }

    /**
     * 创建登录历史
     * @param $user_info
     * @param $token
     * @param $use_second
     * @return array
     */
    public function createLoginHistory($user_info, $token, $use_second = 0)
    {
        //创建用户登录信息
        $history['token']      = $token;
        $history['ip']         = $_SERVER["REMOTE_ADDR"];
        $history['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        //$history['user_agent'] = "ua";
        $history['login_type'] = self::TYPE;
        $history['use_second'] = $use_second;
        $history['login_status'] = empty($token) ? 0 : 1;


        $history['user_id']      = $user_info['user_id'];
        $history['user_name']    = $user_info['user_name'];
        //创建登录日志
        /* @var UserLogService $userLogService */
        $userLogService = app(UserLogService::class);
        $userLogService->createLoginLog($history);

        $url = "userauth/user_login_history";
        return $this->send_request($url, "post", $history,$this->authBaseUrl);

    }

    /**
     * 创建登录二次验证信息
     * @param  array $status_data
     * @param  array $validate_first
     * @param  array $user_info
     * @return mixed
     */
    public function createInfo($status_data, $user_info, $validate_first)
    {
        //数据处理
        $id = $user_info['data']['user_id'];
        //获取手机号码
        $phone_info = $this->getUserPhone($id);

        //数据处理
        foreach ($status_data['data'] as $value) {
            if ($value == 'second_email_status') {
                $validate_info['data']['email']         = "email";
                $validate_info['data']['email_address'] = $user_info['email'];
            }

            if ($value == 'second_phone_status') {
                $validate_info['data']['phone']         = "phone";
                $validate_info['data']['phone_number']  = $phone_info['data']['phone_number'];
                $validate_info['data']['phone_idd']     = $phone_info['data']['phone_idd'];
            }

            if ($value == 'second_google_auth_status') {
                $google_info = $this->getUserGoogleAuth($id);
                $validate_info['data']['google'] = "google";
                $validate_info['data']['google_secret']  = $google_info['data']['google_key'];
            }
        }

        if ($validate_first == 'second_email_status') {
            $first_data['email'] = substr($user_info['email'], '0', 3)."*****".strstr($user_info['email'], "@",false);
        }elseif ($validate_first == 'second_phone_status') {
            $first_data['phone'] = $phone_info['data']['phone_idd'].substr($phone_info['data']['phone_number'] , 0 , 3)."******".substr($phone_info['data']['phone_number'], -2,2);
        }else{
            $first_data['google'] = '******';
        }

        $id = $user_info['data']['user_id'];
        $first_data['identification'] = "user_".$id;

        $validate_info['info'] = $first_data;
        $validate_info['info']['validate_status'] = $validate_first;
        $validate_info['user_info']               = $user_info['data'];
        $validate_info['user_info']['email']      = $user_info['email'];
        $key = env('PC_VALIDATE')."user_".$id;
        Redis::set($key,serialize($validate_info));
        return $validate_info;
    }

    /**
     * 用户登录历史
     * @param $user_id
     * @param $pageSize
     * @param $page
     * @return array
     */
    public function getUserLoginHistoryList($user_id, $pageSize, $page)
    {
        $page = ($page - 1) * $pageSize;
        $url = "userauth/user_login_history?user_id=" . $user_id . "&sort=user_login_history_id&order=DESC&limit =" . $pageSize . "&start=" . $page;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取ping码
     * @param $user_info
     * @return array
     */
    public function getUserPin($user_info)
    {
        $url = "userauth/user_pin/id/" . $user_info['user_id'];
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 修改pin
     * @param $user_info
     * @param $data
     * @return array
     */
    public function editPin($user_info, $data)
    {
        $url = "userauth/user_pin/id/".$user_info['user_id']."?cols=true";
        return $this->send_request($url, 'patch', $data,$this->authBaseUrl);
    }

    /**
     * 创建userPin
     * @param $user_info
     * @param $data
     * @return array
     */
    public function createUserPin($user_info, $data)
    {
        unset($data['pin_confirmation']);
        $data['user_name'] = $user_info['user_name'];
        $data['user_id']   = intval($user_info['user_id']);
        $url = "userauth/user_pin";
        return $this->send_request($url,'post', $data,$this->authBaseUrl);
    }

    /**
     * 获取GoogleAuth信息
     * @param $user_id
     * @return array
     */
    public function getUserGoogleAuth($user_id)
    {
        $url = "userauth/user_google_auth/id/" . $user_id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);

    }

    /**
     * 创建google key
     * @param $data
     * @return array
     */
    public function createUserGoogleAuth($data)
    {
        $url = "userauth/user_google_auth";
        return $this->send_request($url, 'post', $data,$this->authBaseUrl);

    }

    public function editUserGoogleAuth($secret,$user_id)
    {
        $url = 'userauth/user_google_auth/id/'.$user_id.'?cole=true';
        $data['google_key'] = $secret;
        return $this->send_request($url, 'patch', $data,$this->authBaseUrl);

    }


    /**
     * 获取保证金
     * @param $user_id
     * @return array
     */
    public function getDeposit($user_id)
    {
        $url = "" . $user_id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取黑名单
     * @param $user_id
     * @return array
     */
    public function getBlackList($user_id)
    {
        $url = "" . $user_id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取登录前的状态
     * @param $id
     * @return array|bool
     */
    public function validateIp($id)
    {
        //为更换ip一天内免验证,获取最后一次登录信息
        $pageSize = 1;
        $page     = 1;
        $history = $this->getUserLoginHistoryList($id,$pageSize,$page);

        //新创建用户,第一次登陆
        if (empty($history['data']['list'])) {
            return false; //需要验证
        }

        $last_login_time    = isset($history['data']['list'][0]['created_at']) ? $history['data']['list'][0]['created_at'] : "";
        $last_login_ip      = isset($history['data']['list'][0]['ip']) ? $history['data']['list'][0]['ip'] : "";
        $last_login_status  = isset($history['data']['list'][0]['use_second']) ? $history['data']['list'][0]['use_second'] : "";
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($last_login_status != 1) {
            return false; //需要验证
        }

        if ($ip != $last_login_ip || (time()-$last_login_time) > 86400) {
            return false; //需要验证
        }
        return true; //免验证
    }


    /**
     * 下一个需要二次验证
     * @param $user_data
     * @param $status
     * @return array
     */
    public function lastValidate($user_data, $status)
    {
        $key  = env('PC_VALIDATE').$user_data['identification'];
        $data = unserialize(Redis::get($key));
        unset($data['info']);

        if ($status == "google") {
            return ['code' => 201, 'data' => $data['user_info']];
        }

        if (!empty($data['data']['phone']) && $status != 'phone') {
            unset($data['data']['email_address']);
            unset($data['data']['email']);
            //处理数据并储存
            $info['identification']  = $user_data['identification'];
            $info['validate_status'] = "second_phone_status";
            $info['phone']           = substr($data['data']['phone_number'] , 0 , 3)."******".substr($data['data']['phone_number'], -2,2);
            Redis::set($key,serialize($data));
            return ['code' => 200,'data'=>$info];
        }

        if (!empty($data['data']['google']) && $status != 'google') {
            unset($data['data']['phone_number']);
            unset($data['data']['phone']);
            //处理数据并储存
            $info['identification']  = $user_data['identification'];
            $info['validate_status'] = "second_google_auth_status";
            Redis::set($key,serialize($data));
            return ['code' => 200,'data'=>$info];
        }

        return ['code' => 201, 'data' => $data['user_info']];
    }

    /**
     * 创建user数据处理
     * @param $data
     * @param $request
     * @param $promo_info
     * @return mixed
     */
    public function handleData($data, $promo_info, $request)
    {
        if (substr( $request->code, 0, 1 ) == "U") {
            $promo_info = $this->GetUserByName($promo_info['data']['user_name']);
        }
        //创建
        $data['agent_id']           = isset($promo_info['data']['agent_id']) ? intval($promo_info['data']['agent_id']) : 0;
        $data['agent_name']         = isset($promo_info['data']['agent_name']) ? $promo_info['data']['agent_name'] : '';
        $data['agent_promo_id']     = isset($promo_info['data']['agent_promo_id']) ? intval($promo_info['data']['agent_promo_id']) : 0;
        $data['agent_promo']        = isset($promo_info['data']['agent_promo']) ? $promo_info['data']['agent_promo'] : "";
        $data['parent_user_id']     = isset($promo_info['data']['parent_user_id']) ? intval($promo_info['data']['user_id']) : 0;
        $data['parent_user_name']   = isset($promo_info['data']['parent_user_name']) ? $promo_info['data']['user_name'] : '';
        unset($data['password_confirmation']);
        unset($data['invite_code']);
        return $data;
    }

    /**
     * 验证邮箱验证码
     * @param $data
     * @return array
     */
    public function checkEmailCode($data)
    {
        $url = "";
        return $this->send_request($url,'post', $data,$this->authBaseUrl);
    }

    /**
     * 判断是否绑定
     * @param $type
     * @param $status_info
     * @return bool
     */
    public function checkStatus($type, $status_info)
    {
        switch($type){
            case 'email':
                $field = 'has_email_status';
                break;
            case 'phone':
                $field = 'has_phone_status';
                break;
            case 'google':
                $field = 'has_google_auth_status';
                break;
            default:
                $field = 'has_email_status';
                break;
        }
        if ($status_info['data'][$field] != 1) {
            return false;
        }
        return true;
    }

    /**
     * 找回密码信息
     * @param $user_info
     * @return mixed
     */
    public function resetUserPass($user_info)
    {
        //获取手机信息
        $id = $user_info['user_id'];
        $status_info = $this->getUserStatus($id);

        if ($status_info['data']['has_email_status'] == 1) {

            $email_info = $this->getUserEmailById($id);
            //用户是否开启邮箱验证
            $user_data['email'] = substr($email_info['data']['email'], '0', 3)."*****".strstr($email_info['data']['email'], "@",false);

        }

        //用户是否开启手机验证
        if ($status_info['data']['has_phone_status'] == 1) {
            $data = $this->getUserPhone($id);
            $user_data['phone_number'] = !empty($data['data']['phone_number']) ? substr($data['data']['phone_number'] , 0 , 3)."******".substr($data['data']['phone_number'], -2,2) : '';
        }
        $user_data['phone_idd']    = isset($data['data']['phone_idd']) ? $data['data']['phone_idd'] : '';

        //用户是否开启google验证
        if ($status_info['data']['has_google_auth_status'] == 1) {
            $google = $this->getUserGoogleAuth($id);
            $user_data['google'] = empty($google['data']['google_key']) ? "" : "******";
        }
        $user_data['user'] = isset($user_info['email']) ? $user_info['email'] : $user_info['phone_number'];

        return $user_data;
    }

    /**
     * 判断用户是否是异地登录
     * @param $id
     * @return bool|mixed
     */
    public function checkIp($id)
    {
        //获取用户上次登录历史
        $pageSize = 1;
        $page     = 1;
        $ip       = $_SERVER["REMOTE_ADDR"];
        $history  = $this->getUserLoginHistoryList($id,$pageSize,$page);
        if (empty($history['data']['list'])) {
            $last_login_ip = $_SERVER["REMOTE_ADDR"];
        }else{
            $last_login_ip = $history['code'] == 200 ? $history['data']['list'][0]['ip'] : "";
        }

        if ($last_login_ip == $ip) {
            return false;
        }

        //获取用户手机号码
        $phone_info = $this->getUserPhone($id);
        if ($phone_info['code'] != 200) {
            return false;
        }
        return $phone_info['data'];

    }

    /**
     * 通过promo获取用户信息
     * @param $promo
     * @return array|bool
     */
    public function getUserPromoByPromo($promo)
    {
        if (empty($promo)) {
            return false;
        }
        $url = "userauth/user_promo/promo/".$promo;
        return $this->send_request($url, "get" ,'',$this->authBaseUrl);
    }

    /**
     * 通过手机号获取用户信息
     * @param $phone
     * @param $phone_idd
     * @return array
     */
    public function getUserPhoneByPhone($phone,$phone_idd)
    {
        $url = 'userauth/user_phone/idd/'.$phone_idd.'/phone/'.$phone;
        return $this->send_request($url,'get','',$this->authBaseUrl);
    }

    /**
     * 用户绑定邮箱
     * @param $data
     * @return array
     */
    public function createEmail($data)
    {
        $url = "userauth/user_email";
        return $this->send_request($url, 'post', $data, $this->authBaseUrl);
    }

}
