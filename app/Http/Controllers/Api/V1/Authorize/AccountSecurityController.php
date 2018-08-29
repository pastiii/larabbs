<?php

namespace App\Http\Controllers\Api\V1\Authorize;

use App\Support\ApiRequestTrait;
use App\Support\SaltTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\UserService;
use App\Support\SendMessagesTrait;
use App\Support\SendTrait;
use App\Services\AccountService;
use App\Services\SecurityVerificationService;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\Api\CaptchaRequest;
use App\Handlers\ImageUploadHandler;
use App\Token\Apiauth;

/**
 * Class AccountSecurityController
 * @package App\Http\Controllers\Api\V1\Authorize
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/28
 * Time: 10:06
 */

class AccountSecurityController extends BaseController
{

    use SendMessagesTrait, ApiRequestTrait, SaltTrait, SendTrait;
    /* @var UserService */
    protected $userService;

    /* @var  AccountService $accountService*/
    protected $accountService;

    /* @var  SecurityVerificationService*/
    protected $securityVerificationService;

    /**
     * AccountSecurity constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取userService
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
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getAccountService()
    {
        if (!isset($this->accountService)) {
            $this->accountService = app(AccountService::class);
        }
        return $this->accountService;
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
     * 验证用户邮箱是否存在(找回密码)
     * @param CaptchaRequest $request
     * @return array
     */
    public function retrievePassword(CaptchaRequest $request)
    {
        $this->getUserService();
        $data = $this->validate($request, [
            'email'         => 'nullable|string|email',
            'phone_number'  => 'nullable|regex:/^[0-9]{2,20}$/',
            'phone_idd'     => 'nullable',
            'captcha_code'  => 'required',
            'captcha_key'   => 'required',
        ]);

        if (empty($data['email']) && empty($data['phone_number'])) {
            return $this->errors($this->code_num('AccountNull'),__LINE__);
        }

        if (!empty($data['email']) && !empty($data['phone_number'])) {
            return $this->errors($this->code_num('ParamError'),__LINE__);
        }

        /* @var SecurityVerificationService $securityVerification 验证服务接口 */
        $securityVerification = app(SecurityVerificationService::class);

        //图片验证码验证
        $info['token'] = $data['captcha_key'];
        $info['code']  = $data['captcha_code'];
        $info = $securityVerification->checkCaptcha($info);
        if ($info['data']['msg'] != 'ok') {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }
        $user_info = [];
        $code = $this->code_num('Empty');
        if (!empty($data['email'])) {
            //通过邮箱获取用户信息
            $user_info = $this->userService->getUserEmailByMail($data['email']);
            //账号不存在
            if (empty($user_info['data'])) {
                return $this->errors($code, __LINE__);
            }
        }

        if (!empty($data['phone_number'])) {
            if (empty($data['phone_idd'])) {
                return $this->errors($this->code_num('CountryNull'),__LINE__);
            }

            //通过手机获取用户信息
            $user_info = $this->userService->getUserPhoneByPhone($data['phone_number'],$data['phone_idd']);
            //账号不存在
            if (empty($user_info['data'])) {
                return $this->errors($code, __LINE__);
            }
        }
        //数据处理
        $data = $this->userService->resetUserPass($user_info['data']);
        return $this->response($data, 200);
    }

    /**
     * 用户绑定手机号码
     * @param Request $request
     * @return array
     */
    public function createPhone(Request $request)
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        $data = $this->validate($request, [
            'phone_number'  => 'required|string|regex:/^[0-9]{2,20}$/',
            'phone_idd'     => 'required|string',
            'verification_code' => 'required',
            'verification_key'  => 'required',
        ]);

        //验证手机验证码
        $redis_key = env('PC_PHONE') . $data['phone_idd'] . $data['phone_number'] . "_" . $data['verification_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码是否错误
        if (!hash_equals(redis::get($redis_key), $data['verification_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据
        redis::del($redis_key);

        //数据处理
        $info = $this->get_user_info();
        //绑定手机号码
        $this->getUserService();

        $phone_date = $this->userService->getUserPhoneByPhone($data['phone_number'], $data['phone_idd']);
        if (!empty($phone_date['data'])) {
            $code = $this->code_num('PhoneUnique');
            return $this->errors($code, __LINE__);
        }

        $phone_info = $this->userService->phone($info,$data);

        if ($phone_info['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('CreateFailure');
        return $this->errors($code, __LINE__);


    }

    /**
     * 修改用户绑定的手机号码
     * @param Request $request
     * @return array
     */
    public function editPhone(Request $request)
    {
        $this->getUserService();
        $phone = $this->validate($request, [
            'phone_number'      => 'required|regex:/^[0-9]{2,20}$/',
            'phone_idd'         => 'required',
            'verification_code' => 'required',
            'verification_key'  => 'required',
        ]);

        //获取手机号码
        $phone_info = $this->userService->getUserPhone($this->user_id);
        if ($phone_info['real_code'] != 200) {
            $code = $this->code_num('PhoneNull');
            return $this->errors($code, __LINE__);
        }

        if (hash_equals($phone_info['data']['phone_number'], $phone['phone_number'])) {
            $code = $this->code_num('Identical');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码
        $redis_key = env('PC_PHONE') . $phone['phone_number'] . "_" . $phone['verification_key'];
        //验证邮箱验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码是否错误
        if (!hash_equals(redis::get($redis_key), $phone['verification_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据
        redis::del($redis_key);

        //获取用户Id
        $result = $this->get_user_info();
        $user_info  = $this->userService->updatePhone($result, $phone);
        //判断and返回信息
        if ($user_info['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 开启,禁用二次验证(用户认证)
     * @return array
     */
    public function twoVerification()
    {
        $code = $this->code_num('TwoVerification');
        return $this->response($this->checkTwoStatus(), $code);
    }

    /**
     * 检查二次验证状态
     * @return mixed|string
     */
    protected function checkTwoStatus()
    {
        $info = '';
        $this->getUserService();
        //开启,禁用二次验证判断
        $redis_key = env('PC_STATUS') . "user_" . $this->user_id;
        if (empty(Redis::get($redis_key))) {
            $user_status = $this->userService->getUserStatus($this->user_id);
            if (!empty($user_status['data'])) {
                $info = $this->userService->bindingInfo($user_status, $this->user_id);
            }
        }
        return $info;
    }

    /**
     * 检查二次验证状态
     * @return mixed|string
     */
    public function checkTwo()
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        return $this->response("", 200);
    }
    
    /**
     * 开启,禁用二次验证
     * @param Request $request
     * @return array
     */
    public function patchStatus(Request $request)
    {
        $this->getUserService();
        $type = $this->validate($request, [
            'status'      => 'required|in:email,google,phone',
        ]);

        switch ($type['status']) {
            case 'email':
                $field = 'second_email_status';
                break;
            case 'phone':
                $field = 'second_phone_status';
                break;
            case 'google':
                $field = 'second_google_auth_status';
                break;
            default:
                $field = 'second_email_status';
                break;
        }

        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        //获取二验状态
        $status_info  = $this->userService->getUserStatus($this->user_id);

        if ($status_info['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }

        //验证用户是否有绑定
        $status = $this->userService->checkStatus($type['status'], $status_info);
        if ($status == false) {
            $code = $this->code_num('Unbound');
            return $this->errors($code, __LINE__);
        }

        //修改验证状态
        $status_info = $this->userService->updateUserStatus($this->user_id, $field, $status_info);

        //判断and返回结果
        if ($status_info['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 获取用户基本信息
     * @param $user_data
     * @return array
     */
    public function userInfo($user_data)
    {
        //获取用户创建时间
        $this->getUserService();
        $user_info = $this->userService->getUser($user_data['user_id']);
        if ($user_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }
        /* 获取accountService */
        $this->getAccountService();
        //获取用户最后一次登录历史
        $page       = 1;
        $pageSize   = 1;
        $last_login = $this->userService->getUserLoginHistoryList($user_data['user_id'], $pageSize, $page);
        $other_info = $this->accountService->otherInfo($user_data);

        //账号信息获取
        $email = $this->userService->getUserEmailById($user_data['user_id']);
        $phone = $this->userService->getUserPhone($user_data['user_id']);

        //数据处理
        $data['email']            = empty($email['data']) ? "" : substr($email['data']['email'], '0', 3)."*****".strstr($email['data']['email'], "@",false);
        $data['phone']            = empty($phone['data']) ? "" : substr($phone['data']['phone_number'] , 0 , 3)."******".substr($phone['data']['phone_number'], -2,2);
        $data['name']             = $user_data['user_name'];
        $data['user_avatar']      = isset($other_info['data']['user_avatar']) ?  $other_info['data']['user_avatar'] : '';
        $data['user_gender']      = isset($other_info['data']['user_gender']) ?  $other_info['data']['user_gender'] : '';
        $data['user_birthday']    = isset($other_info['data']['user_birthday']) ?  $other_info['data']['user_birthday'] : '';
        $data['user_brief']       = isset($other_info['data']['user_brief']) ? $other_info['data']['user_brief'] : '';
        $data['create_time']      = date('Y-m-d H:i:s', $user_info['data']['created_at']);
        $data['last_login_time']  = isset($last_login['data']['list']) ? date('Y-m-d H:i:s', $last_login['data']['list'][0]['created_at']) : '';

        return $data;
    }

    /**
     * 验证旧手机号
     * @param Request $request
     * @return array
     */
    public function getPhoneNumber(Request $request)
    {
        //查看是否已经绑定手机(暂时使用虚拟数据)
        $phone_data = $this->validate($request, [
            'phone_number' => 'required|regex:/^1[34578]\d{9}$/',
            'phone_idd'    => 'required|string'
        ]);
        $this->getUserService();

        //获取手机信息
        $phone_info = $this->userService->getUserPhone($this->user_id);

        if ($phone_info['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }

        if ($phone_data['phone_number'] == $phone_info['data']['phone_number'] && $phone_data['phone_idd'] == $phone_info['data']['phone_idd']) {
            return $this->response("", 200);
        }

        $code = $this->code_num("PhoneFail");
        return $this->errors($code, __LINE__);

    }

    /**
     * 创建userPin
     * @param Request $request
     * @return array
     */
    public function createPin(Request $request)
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        $this->getUserService();

        //获取参数
        $pin_data  = $this->validate($request, [
            'pin'              => 'required|string|confirmed|min:5|max:15',
            'pin_confirmation' => 'required|string|min:5|max:15'
        ]);

        //获取用户信息
        $user_info = $this->get_user_info();

        //检测用户是否绑定过pin
        $pin_info = $this->userService->getUserPin($user_info);
        if ($pin_info['real_code'] == 200) {
            $code = $this->code_num('NotBinDing');
            return $this->errors($code, __LINE__);
        }

        $pin_data['salt'] = $this->getUnique();
        $pin_data['pin'] = $this->getPassword($pin_data['pin'],$pin_data['salt']);
        //创建userPin
        $result = $this->userService->createUserPin($user_info, $pin_data);

        //判断
        if ($result['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('CreateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 重置支付密码
     * @param Request $request
     * @return array
     */
    public function updatePin(Request $request)
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        $data = $this->validate($request, [
            'pin'              => 'required|string|confirmed|min:5|max:15',
            'pin_confirmation' => 'required|string|min:5|max:15'
        ]);

        //数据
        unset($data['pin_confirmation']);
        //密码盐
        $this->getUserService();
        $data['salt'] = $this->getUnique();
        $data['pin'] = $this->getPassword($data['pin'],$data['salt']);
        //获取用户信息
        $user_info = $this->get_user_info();

        //判断是否绑定pin
        $pin = $this->userService->getUserPin($user_info);
        if (empty($pin['data']['pin'])) {
            $code = $this->code_num('GetPinFail');
            return $this->errors($code, __LINE__);
        }

        //执行密码重置
        $pin_info  = $this->userService->editPin($user_info, $data);
        //结果
        if ($pin_info['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return array
     */
    public function updateLoginPassword(Request $request)
    {
        //判断是否通过二次验证
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        $data = $this->validate($request, [
            'old_password' => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/',
            'new_password' => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/|confirmed',
            'new_password_confirmation' => 'required|string|regex:/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{5,15}$/'
        ]);

        $this->getUserService();
        //验证原始密码
        $user_info = $this->userService->getUser($this->user_id);
        if ($user_info['real_code'] != 200 || empty($user_info['data']['password'])) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        $password = $this->checkPassword($data['old_password'], $user_info['data']['password'], $user_info['data']['salt']);
        //判断密码是否正确
        if (!$password) {
            $code = $this->code_num('PasswordError');
            return $this->errors($code, __LINE__);
        }

        //获取修改数据
        $data['salt']     = $this->getUnique();
        $data['password'] = $this->getPassword($data['new_password'], $data['salt']);
        unset($data['new_password_confirmation']);
        unset($data['old_password']);

        //修改密码
        $user_info = $this->userService->updateUserPassword($this->user_id, $data);

        //返回结果
        if ($user_info['code'] == 200) {
            return $this->response("", 200);
        }

        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 获取googleAuthenticator 信息
     * @return array
     */
    public function getGoogleCode()
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        /* 获取google授权信息 */
        $this->getUserService();
        //获取用户信息
        $user_data = $this->get_user_info();
        /* @var SecurityVerificationService $securityVerification 验证服务接口 */
        $securityVerification = app(SecurityVerificationService::class);
        /* 先获取sessionId 通讯协议*/
        $result = $securityVerification->getSessionId();
        /* 错误状态码 */
        $code = $this->code_num('authorization');

        if ($result['code'] != 200) {
            $this->errors($code);
        }
        $sessionId = $result['data']['data']['sessionId'];
        /* 获取google授权 secret */
        $result = $securityVerification->getGoogleSecret($sessionId, $user_data['user_name']);

        if ($result['real_code'] != 200) {
                $this->errors($code);
        }

        //拼接二维码地址
        $result['data']['data']['QrCode'] = "data:image/png;base64,".$result['data']['data']['QrCode'];
        return $this->response($result['data']['data'], 200);
    }


    /**
     * 绑定google 信息
     * @param $user_data
     * @param $secret
     * @return bool
     */
    protected  function bindingGoogleKey ($user_data, $secret)
    {

        $google_data['user_id']    = intval($user_data['user_id']);
        $google_data['user_name']  = $user_data['user_name'];
        $google_data['google_key'] = $secret;
        /* 创建用户google_key */
        $response = $this->userService->createUserGoogleAuth($google_data);
        /* 授权失败 */
        if ($response['code'] != 200) {
            return false;
        }
        return true;
    }

    /**
     * 验证google验证码
     * @param Request $request
     * @return array
     */
    public function checkGoogleCode(Request $request)
    {
        $this->getUserService();
        /* 验证验证码 */
        $data = $this->validate($request, [
            'verify' => 'required|string',
            'secret' => 'nullable',
        ]);
        /* 获取用户信息 */
        $user_info = $this->get_user_info();
        /*  获取登陆用户的googleKey  */
        $googleAuthenticator = $this->userService->getUserGoogleAuth($user_info['user_id']);

        /* 不否存在secret */
        if (!isset($data['secret'])) {
            /* 判断用户是否绑定 */
            if ($googleAuthenticator['real_code'] != 200) {
                $code = $this->code_num('Unbound');
                return $this->errors($code, __LINE__);
            }
            /* 重新赋值 */
            $data['secret'] = $googleAuthenticator['data']['google_key'];
        }
        /* @var  SecurityVerificationService $securityVerification*/
        $securityVerification = app(SecurityVerificationService::class);
        /* 验证googleVerify */
        $result = $securityVerification->checkGoogleVerify($data);
        /* 数据返回 */
        if ($result['data']['code'] != 200) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        /* 验证成功没有绑定google key 则绑定 */
        if ($googleAuthenticator['real_code'] != 200) {
            $response = $this->bindingGoogleKey($user_info, $data['secret']);
            /* 判断是否绑定成功 */
            if (!$response) {
                $code = $this->code_num('BindingFail');
                return $this->errors($code, __LINE__);
            }
        }

        if (!empty($googleAuthenticator['data']) && !empty($data['secret']))
        {
            //绑定过就修改
            $response = $this->editGoogleKey($data['secret']);
            /* 判断是否绑定成功 */
            if (!$response) {
                $code = $this->code_num('BindingFail');
                return $this->errors($code, __LINE__);
            }
        }

        if (isset($request->type)) {
            $redis_key = env('PC_STATUS') . "user_" . $this->user_id;
            redis::setex($redis_key, env("STATUS_TIME", 300), "check");
            return $this->response(['status' => $request->type], 200);
        }

        return $this->response('', '200');
    }


    private function  editGoogleKey($secret)
    {
        $response = $this->userService->editUserGoogleAuth($secret, $this->user_id);
        if ($response['code'] != 200) {
            return false;
        }
        return true;
    }


    /**
     * 获取用户登录list
     * @param Request $request
     * @return array
     */
    public function getUserLoginList(Request $request)
    {
        $pageSize = 10;
        $page = $request->input('page', 1);

        //获取登录历史列表
        $this->getUserService();
        $history = $this->userService->getUserLoginHistoryList($this->user_id, $pageSize, $page);

        //判断
        if ($history['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        return $this->response($history['data']['list'], 200);
    }

    /**
     * 获取用户绑定验证信息
     * @return array
     */
    public function getUserStatusById()
    {
        $this->getUserService();
        $this->getAccountService();
        $user_data = $this->get_user_info();
        //获取用户密码
        $user_info = $this->userService->getUser($user_data['user_id']);
        if ($user_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }
        //获取用户绑定信息
        $user_status = $this->userService->getUserStatus($user_data['user_id']);
        if ($user_status['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        //判断用户状态
        $email_status  = $user_status['data']['has_email_status'] == 0 ? 0 : 1;
        $phone_status  = $user_status['data']['has_phone_status'] == 0 ? 0 : 1;
        $google_status = $user_status['data']['has_google_auth_status'] == 0 ? 0 : 1;

        if ($email_status == 1) {
            $email_status = $email_status + $user_status['data']['second_email_status'];
        }
        if ($phone_status == 1) {
            $phone_status = $phone_status + $user_status['data']['second_phone_status'];
        }
        if ($google_status == 1) {
            $google_status = $google_status + $user_status['data']['second_google_auth_status'];
        }

        $data = $this->userInfo($user_data);

        //获取pin
        $pin_info = $this->userService->getUserPin($user_data);

        if ($pin_info['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }

        //数据处理
        $data['email_status']     = $email_status;
        $data['phone_status']     = $phone_status;
        $data['google_status']    = $google_status;
        $data['password']         = "******";
        $pin = isset($pin_info['data']['pin']) ? "******" : "";
        $data['pin']              = $pin;

        $user_msg = $this->accountService->getUserIdentification($user_data['user_id']);

        $data = array_merge($data, $user_msg);
        //高级认证信息
        $high = $this->accountService->getHighIdentification($user_data['user_id']);
        $data['high_identification'] = $high;
        return $this->response($data, 200);
    }

    /**
     * 一般认证
     * @param Request $request
     * @return array
     */
    public function identification(Request $request)
    {
        //获取实名认证信息
        $this->getAccountService();
        $identification = $this->accountService->getUserIdentification($this->user_id);
        if (!empty(($identification['identification_code']))) {
            $code = $this->code_num('Certified');
            return $this->errors($code, __LINE__);
        }

        if ($request->identification_type == "2") {
            $data = $this->validate($request, [
                'identification_name' => 'required|string',
                'identification_code' => 'required|authentication',
            ]);
        }else{
            $data = $this->validate($request, [
                'identification_name' => 'required|string',
                'identification_code' => 'required',
            ]);
        }


        //获取用户的用户Id name
        $info = $this->get_user_info();
        $data['user_id']   = intval($info['user_id']);
        $data['user_name'] = $info['user_name'];
        $data['identification_type']   = intval($request->identification_type);
        $data['identification_status'] = 1;
        //创建认证
        $user_info = $this->accountService->createIdentification($data);

        if ($user_info['real_code'] != 200) {
            $code = $this->code_num('CertificateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 创建高级验证
     * @param ImageUploadHandler $uploader
     * @param Request $request
     * @return array
     */
    public function createIdentification(ImageUploadHandler $uploader, Request $request)
    {
        //获取实名认证信息
        $this->getAccountService();
        $identification = $this->accountService->getHighIdentification($this->user_id);

        if ($identification > 0) {
            $code = $this->code_num('Certified');
            return $this->errors($code, __LINE__);
        }

        $data = $this->validate($request, [
            'identification_front_img'   => 'required',
            'identification_reverse_img' => 'required',
            'identification_hand_img'    => 'required',
        ]);

        //获取用户信息
        $user_info = $this->get_user_info();
        $data['user_id']   = intval($user_info['user_id']);
        $data['user_name'] = $user_info['user_name'];
        $data['high_identification_status'] = 1;

        //创建高级验证
        $msg = $this->accountService->createHighIdentification($data);

        if ($msg['real_code'] != 200) {
            $code = $this->code_num('CreateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 修改一般认证
     * @param Request $request
     * @return array
     */
    public function updateIdentification(Request $request)
    {
        $data = $this->validate($request, [
            'identification_name' => 'required|string',
            'identification_type' => 'required|int',
            'identification_code' => 'required',
        ]);

        //获取用户的用户Id name
        $info = $this->get_user_info();
        $data['user_id']   = $info['user_id'];
        $data['user_name'] = $info['user_name'];
        $data['identification_type']   = intval($data['identification_type']);
        $data['identification_status'] = 1;

        //修改认证
        $this->getAccountService();
        $user_info = $this->accountService->editIdentification($this->user_id, $data);

        if ($user_info['real_code'] != 200) {
            $code = $this->code_num('UpdateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 修改高级认证
     * @param ImageUploadHandler $uploader
     * @param Request $request
     * @return array
     */
    public function updateHighIdentification(ImageUploadHandler $uploader, Request $request)
    {
        $data = $this->validate($request,[
            'identification_front_img'   => 'required|string',
            'identification_reverse_img' => 'required|string',
            'identification_hand_img'    => 'required|string',
        ]);


        //获取用户信息
        $user_info = $this->get_user_info();
        $data['user_id']   = intval($user_info['user_id']);
        $data['user_name'] = $user_info['user_name'];
        $data['high_identification_status'] = 1;

        //创建高级验证

        $this->getAccountService();
        $msg = $this->accountService->editHighIdentification($this->user_id, $data);

        if ($msg['real_code'] != 200) {
            $code = $this->code_num('UpdateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 获取用户电话信息
     * @return array
     */
    public function phoneInfo()
    {
        $this->getUserService();

        //判断是否通过二次验证
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        //获取登录用户类型
        $user_info  = $this->get_user_info();
        $phone_info = $this->userService->getUserPhone($user_info['user_id']);
        if (empty($phone_info['data']['phone_number'])) {
            $this->errors($this->code_num('PhoneNull'), __LINE__);
        }
        //数据处理
        $data['phone_number'] = substr($phone_info['data']['phone_number'], 0, 3) . "******" . substr($phone_info['data']['phone_number'], -2, 2);
        $data['phone_idd']    = $phone_info['data']['phone_idd'];
        return $this->response($data, 200);
    }

    /**
     * 验证手机验证码(绑定手机)
     * @param Request $request
     * @return array
     */
    public function validatePhoneCode(Request $request)
    {
        $this->getUserService();
        $data = $this->validate($request, [
            'verification_code' => 'required',
            'verification_key'  => 'required',
        ]);

        //获取用户手机
        $phone_info = $this->userService->getUserPhone($this->user_id);
        if (empty($phone_info['data']['phone_number'])) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //判断是否绑定手机号
        if (empty($phone_info['data']['phone_number'])) {
            $this->errors($this->code_num('PhoneNull'), __LINE__);
        }
        $redis_key = env('PC_PHONE') . $phone_info['data']['phone_idd'] . $phone_info['data']['phone_number'] . "_" . $data['verification_key'];
        //验证手机验证码是否过期
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证手机验证码是否错误
        if (!hash_equals(redis::get($redis_key), $data['verification_code'])) {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //清除redis 里面的数据
        redis::del($redis_key);

        //操作验证
        if (!empty($request->type)) {
            $redis_key = env('PC_STATUS') . "user_" . $this->user_id;
            redis::setex($redis_key, env("STATUS_TIME", 300), "check");
            return $this->response(['status' => $request->type], 200);
        }

        return $this->response("", 200);
    }

    /**
     * 获取用户的email address
     * @return array
     */
    public function emailInfo()
    {
        $this->getUserService();

        //判断是否通过二次验证
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }
        //获取用户信息
        $user_info = $this->get_user_info();

        //数据处理
        $data['email']  = substr($user_info['email'], '0', 3) . "*****" . strstr($user_info['email'], "@", false);
        return $this->response($data, 200);
    }

    /**
     * 修改用户其它信息
     * @param Request $request
     * @return array
     */
    public function editUserInfo(Request $request)
    {
        $this->getAccountService();
        //数据获取验证
        $user_info = $this->validate($request, [
            "user_avatar"    => "nullable|string",
            "user_gender"    => "nullable|in:1,2",
            "user_birthday"  => "nullable|string",
            "user_brief"     => "nullable|string",
        ]);

        //获取用户基本信息
        $user_data = $this->get_user_info();

        //验证用户是否已经创建过user_info
        $create_status = $this->accountService->otherInfo($user_data);
        $request_type = !empty($create_status['data']) ? "patch" : "post";

        //判断修改的字段
        $field = empty($user_info) ? "user_brief" : key($user_info);

        //处理数据返回结果
        if ($request_type == "post") {
            $result = $this->accountService->createUserInfo($user_data, $field, $user_info);
        } else {
            $result = $this->accountService->editUser($create_status['data'], $user_info, $field);
        }

        if ($result['code'] != 200) {
            $code = $this->code_num('UpdateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 验证邮箱验证码(update pin)
     * @param Request $request
     * @return array
     */
    public function validateEmailCode(Request $request)
    {
        $data = $this->validate($request, [
            'email_code' => 'required',
            'email_key'  => 'required',
        ]);

        //获取邮箱地址
        /* @var UserService $userService */
        $userService = app('user');
        $email_info = $userService->getEmailById($this->user_id);
        if (empty($email_info['data'])) {
            $code = $this->code_num('NetworkAnomaly');
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

        //清除redis 里面的数据
        redis::del($redis_key);

        if (isset($request->type)) {
            $redis_key = env('PC_STATUS') . "user_" . $this->user_id;
            redis::setex($redis_key, env("STATUS_TIME", 300), "check");
            return $this->response(['status' => $request->type], 200);
        }

        return $this->response("", 200);
    }

    /**
     * 开启,禁用二次验证手机code
     * @return array
     */
    public function sms()
    {
        $this->getUserService();
        //获取用户手机号码
        $phone_info = $this->userService->getUserPhone($this->user_id);

        //获取信息失败
        if ($phone_info['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        //手机号不存在
        if (empty($phone_info['data']['phone_number'])) {
            $code = $this->code_num('PhoneNull');
            return $this->errors($code, __LINE__);
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
     * 发送手机验证码
     * @param Request $request
     * @return mixed
     */
    public function sendSms(Request $request)
    {

        //判断手机验证的环境
        $phone_info = $this->validate($request, [
            'phone_number'  => 'required|regex:/^[0-9]{2,20}$/',
            'phone_idd'     => 'required|string',
        ]);

        //判断手机号码唯一性
        $this->getUserService();
        $return_info = $this->userService->getUserPhoneByPhone($phone_info['phone_number'],$phone_info['phone_idd']);
        if (!empty($return_info['data'])) {
            return $this->errors($this->code_num('PhoneUnique'),__LINE__);
        }

        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $data = $MessageTemplateService->phoneCodeCopyWriting($phone_info['phone_idd']);

        $this->getSecurityVerificationService();
        $smsMessage = $this->securityVerificationService->sendSms($phone_info, $data);
        $result = $this->storageCode($smsMessage, $data, $phone_info);
        if ($result['code'] == 200) {
            return $this->response(['verification_key' => $result['verification_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function crateEmail(Request $request)
    {
        //开启,禁用二次验证判断
        if (!empty($this->checkTwoStatus())) {
            $code = $this->code_num('TwoVerification');
            return $this->response($this->checkTwoStatus(), $code);
        }

        $this->getUserService();
        $email_info = $this->validate($request, [
            'email'      => 'required',
            'email_key'  => 'required',
            'email_code' => 'required',
        ]);

        //判断邮箱是否存在
        $email_data = $this->userService->getUserEmailByMail($email_info['email']);
        //验证邮箱状态
        if (!empty($email_data['data'])) {
            $code = $this->code_num('EmailUnique');
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否过期
        $redis_key = env('PC_EMAIL') . $email_info['email'] . "_" . $email_info['email_key'];
        if (empty(redis::get($redis_key))) {
            $code = $this->code_num('VerifyInvalid');
            return $this->errors($code, __LINE__);
        }

        //验证邮箱验证码是否错误
        if (!hash_equals(redis::get($redis_key), $email_info['email_code'])) {
            $code = $this->code_num('VerificationCode');

            return $this->errors($code, __LINE__);
        }
        //清除redis 里面的数据
        redis::del($redis_key);

        //用户绑定邮箱
        $user_info = $this->get_user_info();
        $data['email']      = $email_info['email'];
        $data['user_id']    = intval($user_info['user_id']);
        $data['user_name']  = $user_info['user_name'];

        $res = $this->userService->createEmail($data);

        if ($res['real_code'] == 200) {
            return $this->response('', 200);
        }

        $code = $this->code_num('CreateFailure');
        return $this->errors($code);
    }

    /**
     * 绑定邮箱发送验证码(用户绑定邮箱)
     * @param Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        $this->getUserService();
        //验证邮箱address
        $email_info = $this->validate($request, [
            'email' =>  'required|string|email',
        ]);

        //检查邮箱是否已经绑定
        $email_data = $this->userService->getUserEmailByMail($email_info['email']);
        //验证邮箱状态
        if (!empty($email_data['data'])) {
            $code = $this->code_num('EmailUnique');
            return $this->errors($code, __LINE__);
        }

        //发送邮件
        $this->getSecurityVerificationService();
        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $data = $MessageTemplateService->emailCopyWriting();
        $emailMessage = $this->securityVerificationService->sendEmail($email_info['email'], $data);
        $res = $this->storageEmail($emailMessage, $email_info['email'], $data);
        if ($res['code'] == 200) {
            return $this->response(['email_key' => $res['email_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 退出登录
     */
    public function login_out()
    {
        //清除二次验证状态
        $redis_key = env('PC_STATUS') . "user_" . $this->user_id;
        if (!empty(redis::get($redis_key))) {
            redis::del($redis_key);
        }

        /* 退出登录清除redis token */
        ApiAuth::deleted_token();
        return $this->response('', 200);
    }
}