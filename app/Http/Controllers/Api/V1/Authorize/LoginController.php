<?php

namespace App\Http\Controllers\Api\V1\Authorize;

use App\Support\SaltTrait;
use App\Support\UserStatusTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Common\CommonController;
use App\Services\UserService;
use App\Services\UserLogService;
use App\Services\AdminConfigService;
use App\Support\SendMessagesTrait;
use App\Support\SendTrait;
use Illuminate\Support\Facades\Redis;
use App\Services\AgentService;
use App\Services\SecurityVerificationService;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Log;
/**
 * Class LoginController
 * @package App\Http\Controllers\Api\V1\Authorize
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/21
 * Time: 15:02
 */
class LoginController extends CommonController
{
    use SendMessagesTrait, UserStatusTrait, SaltTrait, SendTrait;
    /* @var UserService */
    protected $userService;

    /* @var AgentService */
    protected $agentService;
    protected $adminConfigService;

    /**
     * LoginController constructor.
     * @param UserService $userService
     * @param adminConfigService $adminConfigService
     */
    public function __construct(UserService $userService, adminConfigService $adminConfigService)
    {
        $this->userService = $userService;
        $this->adminConfigService = $adminConfigService;
    }

    /**
     * 获取
     * @return AgentService
     */
    protected function getAgentService()
    {
        if (!isset($this->agentService)) {
            $this->agentService = app(AgentService::class);
        }
        return $this->agentService;
    }

    /**
     * create user
     * @param Request $request
     * @return array
     */
    public function createUser(Request $request)
    {
        //检测创建是否开启
//        $config = $this->adminConfigService->adminConfigByName("open_registration");
//        if ($config['code'] != 200 || $config['data']['open_registration'] != 1) {
//            $code = $this->code_num('RegisterClosed');
//            return $this->errors($code, __LINE__);
//        }
        $promo_code = $request->code;
        if (empty($promo_code)) {
            $code = $this->code_num('IllegalRegister');
            return $this->errors($code, __LINE__);
        }

        //验证创建信息
        //正则 2-20位 非特殊字符 用户名
        $data = $this->validate($request, [
            'user_name'             => ['required','string','regex:/^([^\/|_|\"|\/\' | \~ | \#|\$|\%|\^|\&|\*|\(|\)|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\/|\;|\`|\-]+){2,20}$/u'],
            'email'                 => 'nullable|E-mail',
            'email_code'            => 'nullable',
            'phone_number'          => 'nullable||regex:/^[0-9]{2,20}$/',
            'phone_idd'             => 'nullable',
            'phone_code'            => 'nullable',
            'verification_key'      => 'nullable',
            'email_key'             => 'nullable',
            'password'              => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/|confirmed',
            'password_confirmation' => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/',
        ]);

        if (empty($data['email']) && empty($data['phone_number'])) {
            return $this->errors($this->code_num('AccountNull'));
        }

        if (!empty($data['email']) && !empty($data['phone_number'])) {
            return $this->errors($this->code_num('ParamError'));
        }

        /* 通过用户名称获取用户信息 */
        $user_info = $this->userService->GetUserByName($data['user_name']);

        //验证用户名状态
        if (!empty($user_info['data'])) {
            return $this->errors($this->code_num('UserNameUnique'), __LINE__);
        }


        if (!empty($data['email'])) {
            //邮箱注册
            $return_result = $this->emailAuthorization($data);
            //响应判断
            if ($return_result['status_code'] != 200) {
                return $return_result;
            }
        }

        //手机注册
        if (!empty($data['phone_number'])) {
            $phone_response = $this->phoneAuthorization($data);
            //响应判断
            if ($phone_response['status_code'] != 200) {
                return $phone_response;
            }
        }

        //处理推广码信息
        $promo_info = $this->checkPromo($promo_code);
        //响应判断
        if (isset($promo_info['status_code'])) {
            return $promo_info;
        }

        if (isset($promo_info['code']) && $promo_info['code'] != 200) {
            return $promo_info;
        }

        //处理数据
        $data = $this->userService->handleData($data, $promo_info, $request);
        $data['salt'] = $this->getUnique();
        // 加密密码
        $data['password'] = $this->getPassword($data['password'],$data['salt']);
        /* 创建用户 */
        $result = $this->userService->createUser($data);
        if ($result['code'] == 200) {
            /* @var UserLogService $userLogService */
            $userLogService = app(UserLogService::class);
            //存入日志
            $userLogService->createLog($result);
            if(!empty($request->header('Appname')) && $request->header('Appname')=='h5'){

                $token = $this->getToken($result['data']);
                $result['data']['Authorization']= $token;
            }
            unset($result['data']['salt']);
            unset($result['data']['password']);
            return $this->response($result['data'], 200);
        }
        $code = $this->code_num('CreateFailure');
        return $this->errors($code, __LINE__);
    }


    /**
     * 手机验证
     * @param $data
     * @return array
     */
    private function phoneAuthorization($data)
    {
        //通过手机号获取用户id
        $phone_info = $this->userService->getUserPhoneByPhone($data['phone_number'],$data['phone_idd']);

        if (!empty($phone_info['data'])) {
            return $this->errors($this->code_num('PhoneUnique'));
        }

        if (empty($data['phone_idd'])) {
            return $this->errors($this->code_num('CountryNull'));
        }

        if (empty($data['verification_key'])) {
            return $this->errors($this->code_num('SendVerify'));
        }

        $redis_key = env('PC_PHONE') .$data['phone_idd']. $data['phone_number'] . "_" . $data['verification_key'];

        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码是否错误
        if (!hash_equals(redis::get($redis_key), $data['phone_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }
        //清除redis 里面的数据
        redis::del($redis_key);

        return $this->response('',200);
    }

    /**
     * 检测推广码
     * @param $promo_code
     * @return array|bool
     */
    private function checkPromo($promo_code)
    {
        $code = $this->code_num('GetPromoFail');
        //获取推广者信息
        $promo_first = substr( $promo_code, 0, 1 );

        //判断推广码合法性
        if ($promo_first != "U" && $promo_first != "P") {
            return $this->errors($code, __LINE__);
        }

        //获取推广者信息
        $promo_info = [];
        if ($promo_first == "U") {
            $promo_info = $this->userService->getUserPromoByPromo($promo_code);
            if ($promo_info['real_code'] != 200) {
                return $this->errors($code, __LINE__);
            }
        }

        $this->getAgentService();
        if ($promo_first == "P") {
            $promo_info = $this->agentService->getAgentPromoByPromo($promo_code);
            if ($promo_info['real_code'] != 200) {
                return $this->errors($code, __LINE__);
            }
        }

        return $promo_info;
    }


    /**
     * 邮箱授权
     * @param $data
     * @return array|bool
     */
    private function  emailAuthorization ($data)
    {

        if (empty($data['email'])) {
            return $this->errors($this->code_num('EmailNull'),__LINE__);
        }

//        if (empty($data['email_key'])) {
//            return $this->errors($this->code_num('SendEmailVerify'),__LINE__);
//        }

        /* 通过邮箱获取邮箱信息 */
        $email_info = $this->userService->getUserEmailByMail($data['email']);

        //验证邮箱状态
        $code = $this->code_num('EmailUnique');
        if (!empty($email_info['data'])) {
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否过期
//        $redis_key = env('PC_EMAIL') . $data['email'] . "_" . $data['email_key'];
//        if (empty(redis::get($redis_key))) {
//            $code = $this->code_num('VerifyInvalid');
//            return $this->errors($code, __LINE__);
//        }
//
//        //验证邮箱验证码是否错误
//        if (!hash_equals(redis::get($redis_key), $data['email_code'])) {
//            $code = $this->code_num('VerificationCode');
//
//            return $this->errors($code, __LINE__);
//        }
//        //清除redis 里面的数据
//        redis::del($redis_key);

        return $this->response('', 200);
    }

    /**
     * user login
     * @param Request $request
     * @return array|bool
     */
    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'email'         => 'nullable|E-mail',
            'phone_number'  => 'nullable|regex:/^[0-9]{2,20}$/',
            'phone_idd'     => 'nullable',
            'password'      => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/',
            'captcha_code'  => 'required',
            'captcha_key'   => 'required',
        ]);

        if (empty($data['email']) && empty($data['phone_number'])) {
            return $this->errors($this->code_num('AccountNull'),__LINE__);
        }

        if (!empty($data['email']) && !empty($data['phone_number'])) {
            return $this->errors($this->code_num('ParamError'),__LINE__);
        }

        //验证邮箱验证码
        $request['token'] = $data['captcha_key'];
        $request['code']  = $data['captcha_code'];
        //验证验证码
        $return_info = $this->checkCode($request);
        //响应判断
        if ($return_info['status_code'] != 200) {
            return $return_info;
        }

        $email_info = [];

        $code = $this->code_num('GetUserFail');
        //邮箱登陆
        if (!empty($data['email'])) {
            //通过邮箱获取用户信息
            $email_info = $this->userService->getUserEmailByMail($data['email']);
            if ($email_info['code'] != 200) {
                return $this->errors($code, __LINE__);
            }

            if (empty($email_info['data'])) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }

        }

        //手机登陆
        if (!empty($data['phone_number'])) {

            if (empty($data['phone_idd'])) {
                return $this->errors($this->code_num('CountryNull'),__LINE__);
            }

            $email_info = $this->userService->getUserPhoneByPhone($data['phone_number'],$data['phone_idd']);
            if ($email_info['code'] != 200) {
                return $this->errors($code, __LINE__);
            }

            if (empty($email_info['data'])) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }
        }


        $user_id = $email_info['data']['user_id'];
        //通过用户id获取用户信息
        $user_info = $this->userService->getUser($user_id);

        if ($user_info['code'] != 200) {
            return $this->errors($code, __LINE__);
        }

        //验证密码
        $password = $this->checkPassword($data['password'], $user_info['data']['password'], $user_info['data']['salt']);
        //判断密码是否正确
        if (!$password) {
            $code = $this->code_num('PasswordError');
            return $this->errors($code, __LINE__);
        }
        unset($user_info['data']['password']);
        //获取二次验证状态
        $status_data = $this->GetStatus($user_id);
        if ($status_data['code'] != 200) {
            return $this->errors($code, __LINE__);
        }

        //核对上次登录信息
        $ip_status  = $this->userService->validateIp($user_id);
        $use_second = $status_data['data'] && $ip_status == true ? 1 : 0; //开启了验证 && 免验证

        //创建email信息
        if (empty($data['email'])) {
            $email = $this->userService->getUserEmailById($user_info['data']['user_id']);
            $data['email'] = empty($email['data']['email']) ? "" : $email['data']['email'];
        }

        //不需要二次验证
        if (empty($status_data['data']) || $use_second) {
            $return_status = $this->loginValidate($user_info,$data,$user_id,$use_second);
            return $return_status;
        }

        $validate_first = $status_data['data'][0];
        //验证状态
        $code = $this->code_num('TwoVerification');
        //数据处理
        $user_info['email']        = $data['email'];
        $user_info['phone_number'] = isset($email_info['data']['phone_number']) ? $email_info['data']['phone_number'] : "";
        $validate_data = $this->userService->createInfo($status_data, $user_info, $validate_first);
        return $this->response($validate_data['info'], $code);
    }

    /**
     * 不需要二次验证
     * @param $data
     * @param $user_id
     * @param $user_info
     * @param $use_second
     * @return array
     */
    private function loginValidate($user_info,$data,$user_id,$use_second)
    {
        //判断登录角色并生成token
        $user_info['data']['email'] = $data['email'];

        $token = $this->getToken($user_info['data']);
        //存入登录历史

        //用户异地登录短信提醒
        $abnormal = $this->userService->checkIp($user_id);

        //保存登录历史
        $this->userService->createLoginHistory($user_info['data'], $token, $use_second);

        if (!$token) {
            $code = $this->code_num('LoginFailure');
            return $this->errors($code, __LINE__);
        }

        if ($abnormal != false) {
            /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
            $MessageTemplateService = app(MessageTemplateService::class);
            $type = $MessageTemplateService->phoneLoginCopyWriting($abnormal['phone_idd'], $abnormal['user_name']);
            /* @var SecurityVerificationService $securityVerification 验证服务接口 */
            $securityVerification = app(SecurityVerificationService::class);
            $securityVerification->sendSms($abnormal, $type);
        }

        $user_data['token'] = $token;
        $user_data['name']  = $user_info['data']['user_name'];
        return $this->response($user_data, 200);
    }




    /**
     * 用户update password
     * @param Request $request
     * @return array
     */
    public function resetUser(Request $request)
    {
        //验证数据
        $data = $this->validate($request, [
            'password'              => 'required|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/|confirmed',
            'password_confirmation' => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/',
            'user'                  => 'required',
            'phone_idd'             => 'nullable',
        ]);
        $email_forget_key = env('PC_FORGET').'email_forget_password'.$data['user'];
        $phone_forget_key = env('PC_FORGET').'phone_forget_password'.$data['user'];
        $google_forget_key = env('PC_FORGET').'email_forget_password'.$data['user'];
        if (empty(Redis::get($email_forget_key)) || empty(Redis::get($phone_forget_key)) || empty(Redis::get($google_forget_key))) {
            return $this->errors($this->code_num('SafeValidate'),__LINE__);
        }
        Redis::del($email_forget_key);
        Redis::del($phone_forget_key);
        Redis::del($google_forget_key);

        $account = $data['user'];
        $status = strpos($account,'@');
        //通过账号获取用户id
        if (!$status && !empty($data['phone_idd'])) {
            $phone_idd = $data['phone_idd'];
            //手机号为账号
            $user_info = $this->userService->getUserPhoneByPhone($account,$phone_idd);
        }else{
            //邮箱
            $user_info = $this->userService->getUserEmailByMail($account);
        }
        if (empty($user_info['data'])) {
            return $this->errors($this->code_num('Empty'),__LINE__);
        }


        //密码盐
        $data['salt'] = $this->getUnique();
        $data['password'] = $this->getPassword($data['password'], $data['salt']);
        unset($data['password_confirmation']);

        //修改密码
        $user_info = $this->userService->updateUserPassword($user_info['data']['user_id'], $data);

        if ($user_info['code'] == 200) {
            $redis_key = env('PC_EDIT_PASS') . $user_info['data']['user_id'];
            Redis::setex($redis_key, 86400, 1);
            return $this->response("", 200);
        }

        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 验证邮箱验证码(找回密码)
     * @param Request $request
     * @return array
     */
    public function validateEmailCode(Request $request)
    {
        $data = $this->validate($request, [
            'email_code' => 'required',
            'email_key'  => 'required',
            'user'  => 'required',
            'phone_idd'  => 'nullable',
        ]);

        $account = $data['user'];
        $status = strpos($account,'@');
        //通过账号获取用户id
        if (!$status && !empty($data['phone_idd'])) {
            $phone_idd = $data['phone_idd'];
            //手机号为账号
            $user_info = $this->userService->getUserPhoneByPhone($account,$phone_idd);
        }else{
            //邮箱
            $user_info = $this->userService->getUserEmailByMail($account);
        }
        if (empty($user_info['data'])) {
            return $this->errors($this->code_num('Empty'),__LINE__);
        }


        //获取用户邮箱
        $email_info = $this->userService->getUserEmailById($user_info['data']['user_id']);
        if ($email_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        $redis_key = env('PC_EMAIL') . $email_info['data']['email'] . "_" . $data['email_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否错误
        if (!hash_equals(redis::get($redis_key), $data['email_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据+
        redis::del($redis_key);
        $forget_key = env('PC_FORGET').'email_forget_password'.$data['user'];
        Redis::setex($forget_key,600,1);  //埋点
        return $this->response("", 200);
    }

    /**
     * 验证手机验证码(找回密码)
     * @param Request $request
     * @return array
     */
    public function validatePhoneCode(Request $request)
    {
        $data = $this->validate($request, [
            'verification_code' => 'required',
            'verification_key'  => 'required',
            'user'              => 'required',
            'phone_idd'         => 'nullable',
        ]);

        $account = $data['user'];
        $status = strpos($account,'@');
        //通过账号获取用户id
        if (!$status && !empty($data['phone_idd'])) {
            $phone_idd = $data['phone_idd'];
            //手机号为账号
            $user_info = $this->userService->getUserPhoneByPhone($account,$phone_idd);
        }else{
            //邮箱
            $user_info = $this->userService->getUserEmailByMail($account);
        }
        if (empty($user_info['data'])) {
            return $this->errors($this->code_num('Empty'),__LINE__);
        }

        //获取用户邮箱
        $phone_info = $this->userService->getUserPhone($user_info['data']['user_id']);
        if ($phone_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        $redis_key = env('PC_PHONE') . $phone_info['data']['phone_idd'] . $phone_info['data']['phone_number'] . "_" . $data['verification_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否错误
        if (!hash_equals(redis::get($redis_key), $data['verification_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }
        //存入验证标识(用于忘记密码验证)
        $forget_key = env('PC_FORGET').'phone_forget_password'.$data['user'];
        Redis::setex($forget_key,600,1);  //埋点
        //清除redis 里面的数据
        redis::del($redis_key);


        return $this->response("", 200);
    }

    /**
     * 邮箱发送验证码(注册用户)
     * @param Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        //验证邮箱address
        $email_info = $this->validate($request, [
            'email' =>  'required|string|email',
            'captcha_code'  => 'required',
            'captcha_key'   => 'required',
        ]);

        if (empty($email_info['captcha_key']) || empty($email_info['captcha_code'])) {
            return $this->errors($this->code_num('SafeValidate'),__LINE__);
        }

        //图片验证码验证
        $info['token'] = $email_info['captcha_key'];
        $info['code']  = $email_info['captcha_code'];
        /* @var SecurityVerificationService $securityVerification 验证服务接口 */
        $securityVerification = app(SecurityVerificationService::class);
        $info = $securityVerification->checkCaptcha($info);

        if ($info['data']['msg'] != 'ok') {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //判断用户是否存在
        $email = $this->userService->getUserEmailByMail($email_info['email']);

        if (!empty($email['data'])) {
            $code = $this->code_num('EmailUnique');
            return $this->errors($code, __LINE__);
        }

        //发送邮件
        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $data = $MessageTemplateService->emailCopyWriting();
        /* @var SecurityVerificationService $securityVerification 验证服务接口 */
        $securityVerification = app(SecurityVerificationService::class);
        $emailMessage = $securityVerification->sendEmail($email_info['email'], $data);
        $email_data = $this->storageEmail($emailMessage, $email_info['email'], $data);

        if ($email_data['code'] == 200) {
            return $this->response(['email_key' => $email_data['email_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }

    public function test_log(Request $request)
    {
        $token = $request->token;
        $key = '#$*&@#$%^JjUgIsGf';
        $token_1 = md5(md5($key));
        if ($token_1 != $token) {
            die('非法访问');
        }
        $url = storage_path('logs/api/'.date('Y-m-d',time()));
        $file = file($url.'.log');
        dd($file);
    }

    /**
     * 检测登陆状态
     * @return array
     */
    public function checkLoginStatus()
    {
        return $this->response('',200);
    }

}
