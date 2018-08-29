<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/9
 * Time: 21:12
 */

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\BaseController;
use App\Services\SecurityVerificationService;
use App\Services\MessageTemplateService;
use App\Support\SendTrait;
use App\Services\UserService;
use Illuminate\Http\Request;

class CommonController extends BaseController
{
    use SendTrait;

    /* @var SecurityVerificationService */
    protected $securityVerificationService;

    /**
     * @return SecurityVerificationService|\Illuminate\Foundation\Application|mixed
     */
    protected function getSecurityVerificationService()
    {
        if (!isset($this->securityVerificationService)) {
            $this->securityVerificationService = app(SecurityVerificationService::class);
        }
        return $this->securityVerificationService;
    }

    /**
     * 获取token
     * @return array
     */
    public function getCaptcha()
    {
        $this->getSecurityVerificationService();
        //获取验证码token
        $data = $this->securityVerificationService->createToken();

        if ($data['code'] != 200) {
            $code = $this->code_num('GetVerify');
            return $this->errors($code, __LINE__);
        }
        return $this->response($data['data']['data']);
    }

    /**
     * 验证验证码
     * @param Request $request
     * @return array
     */
    public function checkCode(Request $request)
    {
        $this->getSecurityVerificationService();
        $data = $this->validate($request, [
            'code' => 'required|string',
            'token'=> 'required|string'
        ]);

        //获取验证信息
        $info = $this->securityVerificationService->checkCaptcha($data);

        if ($info['data']['msg'] != "ok") {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        if ($info['code'] == 200 && $info['data']['msg'] == "ok") {
            return $this->response("", 200);
        }

        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 发送邮件(改pin)
     * @return array
     */
    public function email()
    {
        //发送邮件
        /* @var UserService $userService */
        $userService = app('user');
        $email_info = $userService->getEmailById($this->user_id);
        if (empty($email_info['data'])) {
            $code = $this->code_num('NetworkAnomaly');
            return $this->errors($code, __LINE__);
        }

        /* @var MessageTemplateService $MessageTemplateService 验证服务接口 */
        $MessageTemplateService = app(MessageTemplateService::class);
        $data = $MessageTemplateService->emailCopyWriting();
        $this->getSecurityVerificationService();
        $emailMessage = $this->securityVerificationService->sendEmail($email_info['data']['email'], $data);
        $email_data = $this->storageEmail($emailMessage, $email_info['data']['email'], $data);

        if ($email_data['code'] == 200) {
            return $this->response(['email_key' => $email_data['email_key']], 200);
        }

        $code = $this->code_num('SendFail');
        return $this->errors($code, __LINE__);
    }
    
    /**
     * 邮箱发送验证码
     * @param Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        //验证邮箱address
        $email_info = $this->validate($request, [
            'email' =>  'required|string|email',
        ]);

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
            'captcha_code'  => 'nullable',
            'captcha_key'   => 'nullable',
        ]);

        //需要验证验证码的
        if (!empty($phone_info['captcha_code']) || !empty($phone_info['captcha_key'])) {
            //图片验证码验证
            $info['token'] = $phone_info['captcha_key'];
            $info['code']  = $phone_info['captcha_code'];
            $this->getSecurityVerificationService();
            $info = $this->securityVerificationService->checkCaptcha($info);
            if ($info['data']['msg'] != 'ok') {
                $code = $this->code_num('VerificationCode');
                return $this->errors($code, __LINE__);
            }
        }

        /* @var UserService $userService */
        $userService = app('user');
        $return_info = $userService->getUserPhoneByPhone($phone_info['phone_number'],$phone_info['phone_idd']);
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
     * 获取国家区域信息
     * @param Request $request
     * @return array
     */
    public function getCountry(Request $request)
    {
        $limit =  300;
        $page  = 1;

        $start = ( $page - 1 ) * $limit;

        /**var AgentService $agentService*/
        $this->getSecurityVerificationService();
        $country      = $this->securityVerificationService->getCountyr($limit, $start);

        if ($country['real_code'] == 200) {
            return $this->response($country['data'], 200);
        }

        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);
    }

}