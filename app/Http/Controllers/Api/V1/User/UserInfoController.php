<?php

namespace App\Http\Controllers\Api\V1\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\AccountService;
use App\Services\UserService;
use App\Support\SaltTrait;

/**
 * Created by PhpStorm.
 * User: xuebi
 * Date: 2018/5/21
 * Time: 11:20
 */
class UserInfoController extends BaseController
{
    use SaltTrait;
    /* @var AccountService $accountService*/
    protected $accountService;
    /* @var UserService $userService */
    protected $userService;

    /**
     * UserInfoController constructor.
     */
    public function __construct()
    {
        $this->getAccountService();
        $this->getUserService();
        parent::__construct();
    }

    /**
     * @return UserService|\Illuminate\Foundation\Application|mixed
     */
    protected  function getUserService()
    {
        if (!isset($this->userService)) {
            $this->userService = app('user');
        }
        return $this->userService;

    }


    protected  function getAccountService()
    {
        if (!isset($this->accountService)) {
            $this->accountService = app(AccountService::class);
        }
        return $this->accountService;

    }

    /**
     * @return array
     */
    public function userInfo()
    {
        //获取保证金
        $deposit_info = $this->userService->getDeposit($this->user_id);
        if ($deposit_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //获取黑名单
        $black_list_info = $this->userService->getDeposit($this->user_id);
        if ($black_list_info['code'] != 200) {
            $code  = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }
        $data=[
            'deposit'  =>$deposit_info['data'],
            'black'    =>$black_list_info['data']
        ];

        return $this->response($data, 200);
    }

    /**
     * 支付列表
     * @return array
     */

     public function userPayList()
     {
         $data=[];
         $alipay_list=$this->accountService->getAliPay($this->user_id);
         $wechatpay_list=$this->accountService->getWechatpay($this->user_id);
         $bankpay_list=$this->accountService->getBankcard($this->user_id);
         $paypal_list=$this->accountService->getPaypal($this->user_id);

         if($alipay_list['code'] != 200 || $wechatpay_list['code'] != 200
             || $bankpay_list['code'] != 200 || $paypal_list['code'] != 200)
         {
             $code  = $this->code_num('GetMsgFail');
             return $this->errors($code, __LINE__);
         }
         //组装数据
         if(!empty($alipay_list['data']['list'])){
             foreach ($alipay_list['data']['list'] as $value){

                 $temp=[
                     'pay_type'=>'alipay',
                     'pay_id'  =>$value['user_account_alipay_id'],
                     'pay_name'=>mb_substr($value['alipay_account_name'] , 0 , 2)."******",
                     'account_name' =>$value['account_name'],
                     'account_status' =>$value['account_status']
                 ];
                 array_push($data,$temp);
             }
         }

         if(!empty($wechatpay_list['data']['list'])){
             foreach ($wechatpay_list['data']['list'] as $value){
                 $temp=[
                     'pay_type'=>'wechatpay',
                     'pay_id'  =>$value['user_account_wechatpay_id'],
                     'pay_name'=>mb_substr($value['wechatpay_account'] , 0 , 2)."******",
                     'account_name' =>$value['account_name'],
                     'account_status' =>$value['account_status']
                 ];
                 array_push($data,$temp);
             }
         }

         if(!empty($bankpay_list['data']['list'])){
             foreach ($bankpay_list['data']['list'] as $value){
                 $temp=[
                     'pay_type'=>'bank',
                     'pay_id'  =>$value['user_account_bank_id'],
                     'pay_name'=>mb_substr($value['bank_name'] , 0 , 2)."******",
                     'account_name' =>$value['account_name'],
                     'account_status' =>$value['account_status']
                 ];
                 array_push($data,$temp);
             }
         }

         if(!empty($paypal_list['data']['list'])){
             foreach ($paypal_list['data']['list'] as $value){
                 $temp=[
                     'pay_type'=>'paypal',
                     'pay_id'  =>$value['user_account_paypal_id'],
                     'pay_name'=>mb_substr($value['paypal_account'] , 0 , 2)."******",
                     'account_name' =>$value['account_name'],
                     'account_status' =>$value['account_status']
                 ];
                 array_push($data,$temp);
             }
         }

         return $this->response($data, 200);
     }

    /**
     * @param Request $request
     * @return array
     */
    public function updatePayStatus(Request $request)
    {
        $this->validate($request,[
            'pay_name'=> 'required|string|in:alipay,wechatpay,bank,paypal',
            'pay_id'  => 'required',
            'account_status' =>'required|int|in:1,2'
        ]);
       $data['account_status'] = intval($request['account_status']);

        $res = [];
       switch ($request['pay_name']){
           case 'alipay':
               $res = $this->accountService->updateUserAccountAlipay($request['pay_id'],'patch','',$data);
               break;
           case 'wechatpay':
               $res = $this->accountService->userAccountWechatpay($request['pay_id'],'patch',$data,'?cols=true1');
               break;
           case 'bank':
               $res = $this->accountService->userAccountBank($request['pay_id'],'patch',$data);
               break;
           case 'paypal':
               $res = $this->accountService->userAccountPaypal($request['pay_id'],'patch',$data);
               break;
       }
       if ($res['code'] == 200) {
            return $this->response($res['data'],200);
       }
       $code = $this->code_num('UpdateFailure');
       return $this->errors($code, __LINE__);
    }

    /**
     *  根据id获取支付信息
     * @param Request $request
     * @return array
     */
    public function getPayToId(Request $request){
        $data=$this->validate($request,[
            'pay_name'=> 'required|string|in:alipay,wechatpay,bank,paypal',
            'pay_id'  => 'required',
        ]);

        $res = [];
        switch ($request['pay_name']){
            case 'alipay':
                $res = $this->accountService->updateUserAccountAlipay($data['pay_id'],'get');
                if(!empty($res['data'])){
                    $res['data']['pay_name']='Alipay';
                }
                break;
            case 'wechatpay':
                $res = $this->accountService->userAccountWechatpay($data['pay_id'],'get');
                if(!empty($res['data'])){
                    $res['data']['pay_name']='Wechatpay';
                }
                break;
            case 'bank':
                $res = $this->accountService->userAccountBank($data['pay_id'],'get');
                if(!empty($res['data'])){
                    $res['data']['pay_name']='Bank';
                }
                break;
            case 'paypal':
                $res = $this->accountService->userAccountPaypal($data['pay_id'],'get');
                if(!empty($res['data'])){
                    $res['data']['pay_name']='PayPal';
                }
                break;
        }

        if(empty($res['data'])){
            $code = $this->code_num('PayFail');
            return $this->errors($code, __LINE__);
        }

        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);

        if ($res['code'] == 200) {
            if(!empty($response['identification_name'])){
                $res['data']['account_name']=$response['identification_name'];
            }
            return $this->response($res['data'],200);
        }
        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 通过id获取实名认证信息,判断账户是否实名认证
     * @return array
     */
    public function getUserIdentification()
    {
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);
        //如果curl执行出错,则返回false,此处不执行
        if ($response['code'] == 200) {
            if (isset($response['identification_status'])) {
                if ($response['identification_status'] == 1) {
                    $code = $this->code_num('authorization');
                    return $this->errors($code, __LINE__);
                } else if ($response['identification_status'] == 2) {
                    $code = $this->code_num('authorization');
                    return $this->errors($code, __LINE__);
                } else if ($response['identification_status'] == 3) {
                    $code = $this->code_num('good');
                    return $this->errors($code, __LINE__);
                }
            }
        }
        $code = $this->code_num('GetUserFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 添加支付方式支付宝微信银行卡
     * 创建支付宝账号信息
     * @param Request $request
     * @return array
     */
    public function createUserAccountAlipay(Request $request)
    {
        $user=$this->get_user_info();
        $data=$this->validate($request,[
            'alipay_account_name' => 'required|string',
            'alipay_qrcode'       => 'required|string',
            'account_name'        => 'required|string',
            'pin'                 => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code,__LINE__);
        }
        unset($data['pin']);
        $data['user_id']       = $this->user_id;
        $data['user_name']     = $user['user_name'];
        $data['account_status']= 2;
        $result=$this->accountService->createUserAccountAlipay($data);

        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('CreateFailure');
        }

        return $this->errors($code, __LINE__);
    }

    /**
     * 启动/更新支付宝
     * @param Request $request
     * @return array
     */
    public function updateUserAccountAlipay(Request $request)
    {
        $data = $this->validate($request, [
            'alipay_id'           => 'required|int',
            'alipay_account_name' => 'nullable|string',
            'account_name'        => 'nullable|string',
            'alipay_qrcode'       => 'nullable|string',
            'pin'                 => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }

        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);

        isset($request['account_status']) ? $data['account_status']=intval($request['account_status']) : '';
        unset($data['alipay_id']);

        /* 跟新支付宝状态 */
        $result = $this->accountService->updateUserAccountAlipay($request->alipay_id,'patch','?cols=true1',$data);
        //return $result;
        if($result['code'] == 200){
            if(empty($result['data'])){
                $code = $this->code_num('PayFail');
            }else{
                return $this->response($result['data'], 200);
            }
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('UpdateFailure');
        }

        return $this->errors($code, __LINE__);
    }

    /**
     * 根据alipay_id支付宝账号获取信息
     * @param $id
     * @return array
     */
    public function getUserAccountAlipay($id)
    {
        $alipay_info=$this->accountService->updateUserAccountAlipay($id,'get');
        if($alipay_info['code'] != 200){
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);

        if(!empty($response['identification_name'])){
            $alipay_info['data']['account_name']=$response['identification_name'];
        }

        return $this->response($alipay_info['data'],200);
    }

    /**
     * 删除支付宝账号信息
     * @param $id
     * @return array
     */
    public function deleteUserAccountAlipay($id)
    {
        $res=$this->accountService->deleteUserAccountAlipay($id);
        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('DeleteFailure');
        return $this->errors($code, __LINE__);
    }


    /**
     * 创建微信账号信息
     * @param Request $request
     * @return array
     */
    public function createUserAccountWechatpay(Request $request)
    {
        $user=$this->get_user_info();

        $data=$this->validate($request,[
            'wechatpay_account'   => 'required|string',
            'wechatpay_qrcode'    => 'required|string',
            'account_name'        => 'required',
            'pin'                 => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);
        $data['user_id']       = $this->user_id;
        $data['user_name']     = $user['user_name'];
        $data['account_status']= 2;

        $result=$this->accountService->createUserAccountWechatpay($data);

        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('CreateFailure');
        }

        return $this->errors($code, __LINE__);

    }

    /**
     *  启动/更新微信支付
     * @param Request $request
     * @return array
     */
    public function updateUserAccountWechatpay( Request $request)
    {
        $data = $this->validate($request, [
            'wechatpay_id'           => 'required|int',
            'wechatpay_account' => 'nullable|string',
            'account_name'      => 'nullable|string',
            'wechatpay_qrcode'  => 'nullable|string',
            'pin'               => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);
        unset($data['wechatpay_id']);
        isset($request['account_status']) ? $data['account_status']=intval($request['account_status']) : '';

        //$cols=isset($request['account_status'])&&$request['account_status'] === '0' ? :'';
        $this->getAccountService();
        /* 跟新微信 */
        $result = $this->accountService->userAccountWechatpay($request->wechatpay_id,'patch',$data,'?cols=true1');

        if($result['code'] == 200){
            if(empty($result['data'])){
                $code = $this->code_num('PayFail');
            }else{
                return $this->response($result['data'], 200);
            }
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('UpdateFailure');
        }

        return $this->errors($code, __LINE__);
    }

    /**
     * 根据id微信账号获取信息
     * @param $id
     * @return array
     */
    public function getUserAccountWechatpay($id)
    {
        $wechatpay_info=$this->accountService->userAccountWechatpay($id,'get');
        if($wechatpay_info['code'] != 200){
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);

        if(!empty($response['identification_name'])){
            $wechatpay_info['data']['account_name']=$response['identification_name'];
        }
        return $this->response($wechatpay_info['data'],200);
    }

    /**
     * 删除微信账号信息
     * @param $id
     * @return array
     */
    public function deleteUserAccountWechatpay($id)
    {
        $res=$this->accountService->userAccountWechatpay($id,'delete');

        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('DeleteFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function createUserAccountBank(Request $request)
    {
        $user=$this->get_user_info();

        $data=$this->validate($request,[
            'account_name' => 'required|string',   //正式名称
            'bank_name'    => 'required|string',   //银行名称
            'bank_branch'  => 'required|string',    //开户支行
            'bank_code'    => 'required|regex:/^([1-9]{1})(\d{14,18})$/', //卡号
            'pin'          => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);

        $data['user_id']       = $this->user_id;
        $data['user_name']     = $user['user_name'];
        $data['account_status']= 2;
        $result=$this->accountService->createUserAccountBank($data);

        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('CreateFailure');
        }
        return $this->errors($code, __LINE__);

    }

    /**
     * @param Request $request
     * @return array
     */
    public function updateUserAccountBank(Request $request)
    {
        $data = $this->validate($request, [
            'bank_id'      => 'required|int',
            'account_name' => 'nullable|string',   //正式名称
            'bank_name'    => 'nullable|string',   //银行名称
            'bank_branch'  => 'nullable|string',    //开户支行
            'bank_code'    => 'nullable|regex:/^([1-9]{1})(\d{14,18})$/', //卡号
            'pin'          => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);
        unset($data['bank_id']);
        isset($request['account_status']) ? $data['account_status']=intval($request['account_status']) : '';

        $result = $this->accountService->userAccountBank($request->bank_id,'patch',$data);

        if($result['code'] == 200){
            if(empty($result['data'])){
                $code = $this->code_num('PayFail');
            }else{
                return $this->response($result['data'], 200);
            }
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('UpdateFailure');
        }
        return $this->errors($code, __LINE__);

    }

    /**
     * @param $id
     * @return array
     */
    public function getUserAccountBank($id)
    {
        $bank_info=$this->accountService->userAccountBank($id,'get');
        if($bank_info['code'] != 200){
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);

        if(!empty($response['identification_name'])){
            $bank_info['data']['account_name']=$response['identification_name'];
        }
        return $this->response($bank_info['data'],200);
    }

    /**
     * @param $id
     * @return array
     */
    public function deleteUserAccountBank($id)
    {
        $res=$this->accountService->userAccountBank($id,'delete');
        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('DeleteFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function createUserAccountPaypal(Request $request)
    {
        $user=$this->get_user_info();

        $data=$this->validate($request,[
            'account_name' => 'required|string',
            'paypal_account' => 'required|string',//Paypal账号
            'pin'                 => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);

        $data['user_id']       = $this->user_id;
        $data['user_name']     = $user['user_name'];
        $data['account_status']= 2;
        $result=$this->accountService->createUserAccountPaypal($data);

        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('CreateFailure');
        }

        return $this->errors($code, __LINE__);

    }

    /**
     * @param Request $request
     * @return array
     */
    public function updateUserAccountPaypal(Request $request)
    {
        $data = $this->validate($request, [
            'paypal_id'      => 'required|int',
            'account_name'   => 'nullable|string',
            'paypal_account' => 'nullable|string',
            'pin'            => 'required|string'
        ]);
        //检查实名认证
        $userId_code=$this->checkUserIdentification();
        if($userId_code !== true){
            return $this->errors($userId_code,__LINE__);
        }
        //检查pin码
        $pin_code=$this->checkPin($data['pin']);
        if($pin_code !== true){
            return $this->errors($pin_code);
        }
        unset($data['pin']);
        unset($data['paypal_id']);
        isset($request['account_status']) ? $data['account_status']=intval($request['account_status']) : '';
        $result = $this->accountService->userAccountPaypal($request->paypal_id,'patch',$data);

        if($result['code'] == 200){
            if(empty($result['data'])){
                $code = $this->code_num('PayFail');
            }else{
                return $this->response($result['data'], 200);
            }
        }elseif ($result['code'] == 502){
            $code = $this->code_num('PayNameUnique');
        }else{
            $code = $this->code_num('UpdateFailure');
        }

        return $this->errors($code, __LINE__);

    }

    /**
     * @param $id
     * @return array
     */
    public function getUserAccountPaypal($id)
    {
        $paypal_info=$this->accountService->userAccountPaypal($id,'get');
        if($paypal_info['code'] != 200){
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);

        if(!empty($response['identification_name'])){
            $paypal_info['data']['account_name']=$response['identification_name'];
        }
        return $this->response($paypal_info['data'],200);
    }


    /**
     * @param $id
     * @return array
     */
    public function deleteUserAccountPaypal($id)
    {
        $res = $this->accountService->userAccountPaypal($id,'delete');
        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('UpdateFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     * 验证资金密码
     * @param $pin string
     * @return int
    */
    public function checkPin($pin){
        $ping_data = $this->userService->getUserPin($this->get_user_info());

        if($ping_data['code'] !=200 || empty($ping_data['data'])){
            $code = $this->code_num('GetPinFail');
            return $code;
        }
        //验证密码
        $password = $this->checkPassword($pin,$ping_data['data']['pin'],$ping_data['data']['salt']);
        //判断密码是否正确
        if(!$password){
            $code = $this->code_num('PinError');
            return $code;
        }
        return true;
    }
    /**
     * 验证实名认证
     * @return int boll
     */
    public function checkUserIdentification(){
        /* 获取实名认证信息 */
        $response = $this->accountService->getUserIdentification($this->user_id);
        if(empty($response['identification_name'])){
            $code = $this->code_num('IdentificationEmpty');
            return $code;
        }
        if($response['identification_status'] != 3){
            $code = $this->code_num('IdentificationStatus');
            return $code;
        }
        return true;
    }
}