<?php

namespace App\Http\Controllers\Api\V1\Authorize;

use App\Services\UserService;
use App\Services\SecurityVerificationService;
use App\Services\MessageTemplateService;
use App\Support\SendMessagesTrait;
use App\Support\SendTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Support\Facades\Redis;

/**
 * Class ValidateController
 * @package App\Http\Controllers\Api\V1\Authorize
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/11
 * Time: 15:30
 */
class ValidateController extends BaseController
{
    use SendMessagesTrait, SendTrait;
    /* @var UserService */
    protected $userService;
    /* @var  SecurityVerificationService*/
    protected $securityVerificationService;

    /**
     * @return UserService|\Illuminate\Foundation\Application|mixed
     */
    protected function getUserService()
    {
        if (!isset($this->userService)) {
            $this->userService = app('user');
        }
        return $this->userService;
    }

    /**
     * @return UserService|\Illuminate\Foundation\Application|mixed
     */
    protected function getSecurityVerificationService()
    {
        if (!isset($this->securityVerificationService)) {
            $this->securityVerificationService = app(SecurityVerificationService::class);
        }
        return $this->securityVerificationService;
    }

    /**
     * 发送手机验证码
     * @param Request $request
     * @return array
     */
    public function sendSms(Request $request)
    {
        $this->getUserService();
        if ($request->isMethod('get')) {
            $account = $request->user;
            $phone_idd = $request->phone_idd;
            $status = strpos($account,'@');
            //通过账号获取用户id
            if (!$status && !empty($phone_idd)) {
                //手机号为账号
                $user_info = $this->userService->getUserPhoneByPhone($account,'+'.$phone_idd);
            }else{
                //邮箱
                $user_info = $this->userService->getUserEmailByMail($account);
            }
            if (empty($user_info['data'])) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }


            $phone_info = $this->userService->getUserPhone($user_info['data']['user_id']);

            //获取信息失败
            if ($phone_info['code'] != 200) {
                $code = $this->code_num('GetMsgFail');
                return $this->errors($code, __LINE__);
            }
            //手机号不存在
            if (empty($phone_info['data'])) {
                $code = $this->code_num('Unbound');
                return $this->errors($code, __LINE__);
            }
        }else{
            $user_data = $this->validate($request, [
                'identification' => 'required|string'
            ]);

            //获取需要验证的手机号码
            $key  = env('PC_VALIDATE').$user_data['identification'];
            $phone_info = unserialize(Redis::get($key));
            if (empty($phone_info)) {
                $code = $this->code_num('GetMsgFail');
                return $this->errors($code, __LINE__);
            }
        }
        $phone['phone_number'] = $phone_info['data']['phone_number'];
        $phone['phone_idd']    = $phone_info['data']['phone_idd'];

        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $type = $MessageTemplateService->phoneCodeCopyWriting($phone['phone_idd']);

        $this->getSecurityVerificationService();
        $smsMessage = $this->securityVerificationService->sendSms($phone, $type);
        $result = $this->storageCode($smsMessage, $type, $phone);
        if ($result['code'] == 200) {
            return $this->response(['verification_key' => $result['verification_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 手机验证码二次验证
     * @param Request $request
     * @return array
     */
    public function validatePhoneCode(Request $request)
    {
        $user_data = $this->validate($request, [
            'identification'    => 'required|string',
            'verification_code' => 'required',
            'verification_key'  => 'required',
        ]);

        //获取需要验证信息
        $key   = env('PC_VALIDATE'). $user_data['identification'];
        $data  = unserialize(Redis::get($key));
        $phone = $data['data']['phone_idd'].$data['data']['phone_number'];

        //验证手机验证码
        $redis_key = env('PC_PHONE') . $phone . "_" . $user_data['verification_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码是否错误
        if (!hash_equals(redis::get($redis_key), $user_data['verification_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据
        redis::del($redis_key);

        //获取数据
        $this->getUserService();
        $status_info = $this->userService->lastValidate($user_data, $status = "phone");

        if ($status_info['code'] == 200) {
            return $this->response($status_info['data'], 200);
        }
        //判断登录角色并生成token
        $info = $this->createToken($status_info['data'], $user_data['identification']);
        return $this->response($info, 200);
    }

    /**
     * 发送邮箱验证码
     * @param Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        $this->getUserService();
        if ($request->isMethod('get')) {

            $account = $request->user;
            $phone_idd = $request->phone_idd;
            $status = strpos($account,'@');
            //通过账号获取用户id
            if (!$status && !empty($phone_idd)) {
                //手机号为账号
                $user_info = $this->userService->getUserPhoneByPhone($account,'+'.$phone_idd);
            }else{
                //邮箱
                $user_info = $this->userService->getUserEmailByMail($account);
            }
            if (empty($user_info['data'])) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }

            //获取邮箱信息
            $email_info =$this->userService->getUserEmailById($user_info['data']['user_id']);
            $email = isset( $email_info['data']['email']) ? $email_info['data']['email'] : "";
            if (empty($email)) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }

        } else {
            $user_data = $this->validate($request, [
                'identification' => 'required|string'
            ]);
            //获取需要验证的email
            $key   = env('PC_VALIDATE'). $user_data['identification'];
            $data  = unserialize(Redis::get($key));
            $email = $data['data']['email_address'];
        }

        //发送邮件
        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $email_data = $MessageTemplateService->emailCopyWriting();
        $this->getSecurityVerificationService();
        $emailMessage = $this->securityVerificationService->sendEmail($email, $email_data);
        $res = $this->storageEmail($emailMessage, $email, $email_data);
        if ($res['code'] == 200) {
            return $this->response(['email_key' => $res['email_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * email验证码二次验证
     * @param Request $request
     * @return array
     */
    public function validateEmailCode(Request $request)
    {
        $this->getUserService();
        $user_data = $this->validate($request, [
            'email_code'     => 'required',
            'email_key'      => 'required',
            'identification' => 'required|string'
        ]);

        //获取需要验证的email
        $key   = env('PC_VALIDATE'). $user_data['identification'];
        $data  = unserialize(Redis::get($key));
        $email = $data['data']['email_address'];

        $redis_key = env('PC_EMAIL') . $email . "_" . $user_data['email_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否错误
        if (!hash_equals(redis::get($redis_key), $user_data['email_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据
        redis::del($redis_key);

        //获取数据
        $status_info = $this->userService->lastValidate($user_data,$status = "email");

        if ($status_info['code'] == 200) {
            return $this->response($status_info['data'], 200);
        }
        //判断登录角色并生成token
        $info = $this->createToken($status_info['data'], $user_data['identification']);
        return $this->response($info, 200);
    }

    /**
     * 验证google验证码
     * @param Request $request
     * @return array
     */
    public function checkGoogleCode( Request $request)
    {
        $this->getUserService();
        if ($request->isMethod('get')) {

            $account = $request->user;
            $phone_idd = $request->phone_idd;
            $status = strpos($account,'@');
            //通过账号获取用户id
            if (!$status && !empty($phone_idd)) {
                //手机号为账号
                $user_info = $this->userService->getUserPhoneByPhone($account,'+'.$phone_idd);
            }else{
                //邮箱
                $user_info = $this->userService->getUserEmailByMail($account);
            }
            if (empty($user_info['data'])) {
                return $this->errors($this->code_num('Empty'),__LINE__);
            }

            $google_data = $this->userService->getUserGoogleAuth($user_info['data']['user_id']);
            $user_data['verify'] = $request->verify;
            $user_data['secret'] = $google_data['data']['google_key'];

        }else{
            /* 获取信息 */
            $user_data = $this->validate($request, [
                'verify'         => 'required|string',
                'identification' => 'required|string'
            ]);
            $key = env('PC_VALIDATE').$user_data['identification'];
            $data = unserialize(Redis::get($key));
            $user_data['secret'] = $data['data']['google_secret'];
        }

        //验证google_key
        $this->getSecurityVerificationService();
        $result = $this->securityVerificationService->checkGoogleVerify($user_data);

        if ($result['data']['code'] != 200) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }elseif ($request->isMethod('get') && $result['data']['code'] == 200){
            $forget_key = env('PC_FORGET').'google_forget_password'.$request->user;
            Redis::setex($forget_key,600,1);  //埋点
            return $this->response("", 200);
        }
        $status_info = $this->userService->lastValidate($user_data, "google");
        //判断登录角色并生成token
        $info = $this->createToken($status_info['data'], $user_data['identification']);
        return $this->response($info, 200);
    }

    /**
     * 二次验证创建Token
     * @param $status_info
     * @param $key
     * @return array
     */
    public function createToken($status_info, $key)
    {
        $key  = env("PC_VALIDATE") .$key;
        $token = $this->getToken($status_info);

        //用户异地登录短信提醒
        $abnormal = $this->userService->checkIp($status_info['user_id']);

        //创建登录历史
        $this->userService->createLoginHistory($status_info, $token, 1);

        if (!$token) {
            $code = $this->code_num('LoginFailure');
            return $this->errors($code, __LINE__);
        }

        //异地登录短信提醒
        if ($abnormal != false) {
            /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
            $MessageTemplateService = app(MessageTemplateService::class);
            $type = $MessageTemplateService->phoneLoginCopyWriting($abnormal['phone_idd'], $abnormal['user_name']);
            $this->getSecurityVerificationService();
            $this->securityVerificationService->sendSms($abnormal, $type);
        }

        //成功并登录
        $info['token']     = $token;
        $info['name'] = $status_info['user_name'];
        Redis::del($key);
        return $info;
    }
}
