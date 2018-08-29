<?php

namespace App\Services;
use App\Support\ApiRequestTrait;

/**
 * 账户信息
 * Class AccountService
 * @package App\Services
 */
class AccountService
{
    use ApiRequestTrait;

    protected $accountBaseUrl;

    public function __construct()
    {
        $this->accountBaseUrl = env('ACCOUNT_BASE_URL');

    }

    /**
     * 获取一般认证信息
     * @param $user_id
     * @return array
     */
    public function getUserIdentification($user_id)
    {
        $url = "userinfo/user_identification/id/" . $user_id;
        $info = $this->send_request($url, 'get', "", $this->accountBaseUrl);

        $data['identification_name']   = isset($info['data']['identification_name']) ? $info['data']['identification_name'] : '';
        $data['identification_code']   = isset($info['data']['identification_code']) ? substr($info['data']['identification_code'] , 0 , 5)."******".substr($info['data']['identification_code'], -2,3): '';
        $data['identification_status'] = isset($info['data']['identification_status']) ? $info['data']['identification_status'] : '';

        return $data;
    }

    /**
     * 获取高级验证信息
     * @param $user_id
     * @return array
     */
    public function getHighIdentification($user_id)
    {
        $url  = "userinfo/user_high_identification/id/" . $user_id;
        $data = $this->send_request($url, 'get', "", $this->accountBaseUrl);
        $high = isset($data['data']['high_identification_status']) ? $data['data']['high_identification_status'] : 0;
        return $high;

    }

    /**
     * 创建一般认证
     * @param $data
     * @return array
     */
    public function createIdentification($data)
    {
        $url = "userinfo/user_identification";
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);
    }


    /**
     * 创建高级验证
     * @param $data
     * @return array
     */
    public function createHighIdentification($data)
    {
        $url = "userinfo/user_high_identification";
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);
    }

    /**
     * update 一般验证
     * @param $id
     * @param $data
     * @return array
     */
    public function editIdentification($id, $data)
    {
        $url = "userinfo/user_identification/id/".$id;
        return $this->send_request($url, 'patch', $data, $this->accountBaseUrl);
    }


    /**
     * update 高级验证
     * @param $id
     * @param $data
     * @return array
     */
    public function editHighIdentification($id, $data)
    {
        $url = "userinfo/user_high_identification/id/".$id;
        return $this->send_request($url, 'patch', $data, $this->accountBaseUrl);
    }


    /**
     * 根据id获取支付宝账户信息
     * @param $user_id
     * @return array
     */
    public function getAliPay($user_id)
    {
        $url = "userinfo/user_account_alipay?user_id=" . $user_id;
        return $this->send_request($url, 'get', '', $this->accountBaseUrl);
    }

    /**
     * 根据id获取微信账户信息
     * @param $user_id
     * @return array
     */
    public function getWechatpay($user_id)
    {
        $url = "userinfo/user_account_wechatpay?user_id=" . $user_id;
        return $this->send_request($url, 'get', '', $this->accountBaseUrl);
    }

    /**
     * 根据id获取银行卡账户信息
     * @param $user_id
     * @return array
     */
    public function getBankcard($user_id)
    {
        $url = "userinfo/user_account_bank?user_id=" . $user_id;
        return $this->send_request($url, 'get', '', $this->accountBaseUrl);
    }

    /**
     * 根据id获取Paypal账户信息
     * @param $user_id
     * @return array
     */
    public function getPaypal($user_id)
    {
        $url = "userinfo/user_account_paypal?user_id=" . $user_id;
        return $this->send_request($url, 'get', '', $this->accountBaseUrl);
    }


    /**
     * 启用禁用支付宝
     * @param $alipay_id
     * @param $request_type
     * @param string $cols
     * @param string $data
     * @return array
     */
    public function updateUserAccountAlipay($alipay_id, $request_type,$cols='',$data='')
    {
        $url = "userinfo/user_account_alipay/id/" . $alipay_id . $cols;
        return $this->send_request($url, $request_type, $data, $this->accountBaseUrl);
    }

    /**
     * 删除支付宝
     * @param $alipay_id
     * @return array
     */
    public function deleteUserAccountAlipay($alipay_id)
    {
        $url = "userinfo/user_account_alipay/id/". $alipay_id;
        return $this->send_request($url, 'delete','', $this->accountBaseUrl);
    }

    /**
     * 支付宝创建
     * @param $data
     * @return array
     */
    public function createUserAccountAlipay($data)
    {
        $url = 'userinfo/user_account_alipay';
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);

    }

    /**
     * 微信创建
     * @param $data
     * @return array
     */
    public function createUserAccountWechatpay($data)
    {
        $url = 'userinfo/user_account_wechatpay';
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);
    }


    /**
     * 微信信息
     * @param $wachat_id
     * @param $request_type
     * @param $data
     * @param $cols
     * @return array
     */
    public function userAccountWechatpay($wachat_id,$request_type,$data='',$cols='')
    {
        $url = 'userinfo/user_account_wechatpay/id/' . $wachat_id . $cols;
        return $this->send_request($url, $request_type, $data, $this->accountBaseUrl);
    }

    /**
     * 银行卡创建
     * @param $data
     * @return array
     */
    public function createUserAccountBank($data)
    {
        $url = 'userinfo/user_account_bank';
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);
    }

    /**
     * 银行卡
     * @param $bank_id
     * @param $request_type
     * @param $data
     * @param $cols
     * @return array
     */
    public function userAccountBank($bank_id,$request_type,$data='',$cols='')
    {
        $url = 'userinfo/user_account_bank/id/' . $bank_id . $cols;
        return $this->send_request($url, $request_type, $data, $this->accountBaseUrl);
    }

    /**
     * 创建PayPal方式
     * @param $data
     * @return array
     */
    public function createUserAccountPaypal($data)
    {
        $url = 'userinfo/user_account_paypal';
        return $this->send_request($url, 'post', $data, $this->accountBaseUrl);
    }

    /**
     * 银行卡
     * @param $paypal_id
     * @param $request_type
     * @param $data
     * @param $cols
     * @return array
     */
    public function userAccountPaypal($paypal_id,$request_type,$data='',$cols='')
    {
        $url = 'userinfo/user_account_paypal/id/' . $paypal_id . $cols;
        return $this->send_request($url, $request_type, $data, $this->accountBaseUrl);
    }

    /**
     * 获取用户基础信息
     * @param $user_data
     * @return mixed
     */
    public function otherInfo($user_data)
    {
        $url = "userinfo/user_info/id/".$user_data['user_id'];
        return $this->send_request($url,'get', "", $this->accountBaseUrl);
    }

    /**
     * 创建用户基础信息
     * @param $user_date
     * @param $field
     * @param $user_info
     * @return array
     */
    public function createUserInfo($user_date, $field, $user_info)
    {
        //数据处理
        if (empty($user_info)) {
            $user_info['user_brief'] = "";
        }
        $data['user_id']       = intval($user_date['user_id']);
        $data['user_name']     = $user_date['user_name'];
        $data['user_avatar']   = $field == "user_avatar" ? $user_info['user_avatar'] : "";
        $data['user_gender']   = $field == "user_gender" ? intval($user_info['user_gender']) : 0;
        $data['user_birthday'] = $field == "user_birthday" ? $user_info['user_birthday'] : "";
        $data['user_brief']    = $field == "user_brief" ? $user_info['user_brief'] : "";

        $url = "userinfo/user_info";
        return $this->send_request($url, "post", $data, $this->accountBaseUrl);
    }

    /**
     * 修改用户其它信息
     * @param $data
     * @param $user_info
     * @param $field
     * @return array
     */
    public function editUser($data, $user_info, $field)
    {
        if ($field == 'user_gender') {
            $data[$field] = empty($user_info) ? $data[$field] : intval($user_info[$field]);
        }else{
            $data[$field] = empty($user_info) ? $data[$field] : $user_info[$field];
        }

        $url = "userinfo/user_info/id/".$data['user_id']."?cols=true";
        return $this->send_request($url, "patch", $data, $this->accountBaseUrl);
    }

}