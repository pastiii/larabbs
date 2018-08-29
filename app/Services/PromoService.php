<?php

namespace App\Services;

use App\Support\ApiRequestTrait;

/**
 * Class PromoService
 * @package App\Services
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/6
 * Time: 13:40
 */
class PromoService
{
    use ApiRequestTrait;

    protected $authBaseUrl;

    public function __construct()
    {
        $this->authBaseUrl = env('AUTH_BASE_URL');
    }

    /**
     * 获取用户推广码
     * @param $id
     * @return array
     */
    public function getPromo($id)
    {
        $url = "userauth/user_promo/id/".$id;
        return $this->send_request($url, "get",'',$this->authBaseUrl);
    }

    /**
     * 获取邀请列表
     * @param $user_info $start
     * @param $pageSize
     * @param $start
     * @return array
     */
    public function getPromoEntry($user_info, $start,$pageSize)
    {
        $start = ($start-1)*$pageSize;

        $url = "userauth/user?order=DESC&sort=user_id&limit=".$pageSize."&start=".$start."&parent_user_name=".$user_info['user_name']."&parent_user_id=".$user_info['user_id'];
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取受邀总人数
     * @param $user_info
     * @return array
     */
    public function promoCount($user_info)
    {
        $url = "userauth/user/count?parent_user_name=".$user_info['user_name']."&parent_user_id=".$user_info['user_id'];
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 数据处理
     * @param $other_info
     * @return array
     */
    public function getPromoData($other_info)
    {
        $data = [];
        $len  = count($other_info['data']['list']);
        for ($i = 0; $i < $len; $i++) {
            $data[$i]['name']        = $other_info['data']['list'][$i]['user_name'];
            $data[$i]['create_time'] = date("Y-m-d H:i:s",$other_info['data']['list'][$i]['created_at']);
        }

        return ['list' => $data];
    }
}