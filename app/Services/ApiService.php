<?php

namespace App\Services;

use App\Support\ApiRequestTrait;
/**
 * Class ApiService
 * @package App\Services
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/5
 * Time: 15:13
 */
class ApiService
{
    use ApiRequestTrait;

    protected $authBaseUrl;

    public function __construct()
    {
        $this->authBaseUrl = env('AUTH_BASE_URL');
    }

    /**
     * 创建用户Access_key
     * @param $data
     * @return array
     */
    public function createUserAccess($data)
    {
        //创建路径
        $url = "userauth/user_access";
        return $this->send_request($url, "post", $data,$this->authBaseUrl);
    }

    /**
     * 获取API列表
     * @param $id
     * @return array
     */
    public function getAccessListById($id)
    {
        $url = "userauth/user_access?user_id=".$id;
        return $this->send_request($url, 'get','',$this->authBaseUrl);
    }

    /**
     * 获取已创建API数目
     * @param $id
     * @return array
     */
    public function getAccessCount($id)
    {
        $url = "userauth/user_access/count?user_id=".$id;
        return $this->send_request($url, "get",'',$this->authBaseUrl);
    }

    /**
     * 删除API
     * @param $id
     * @return array
     */
    public function delAccess($id)
    {
        $url = "userauth/user_access/id/".$id;
        return $this->send_request($url, "delete",'',$this->authBaseUrl);
    }

    /**
     * 修改API
     * @param $id
     * @param $data
     * @param $cols
     * @return array
     */
    public function updateAPI($id, $data, $cols)
    {
        $url = "userauth/user_access/id/".$id."?cols=".$cols;
        return $this->send_request($url, "patch", $data,$this->authBaseUrl);
    }
}