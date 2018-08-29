<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/4
 * Time: 16:21
 */

namespace App\Services;
use App\Support\ApiRequestTrait;

class AdminConfigService
{
    use ApiRequestTrait;

    protected  $adminBaseUrl;
    public function __construct()
    {
        $this->adminBaseUrl = env('ADMIN_BASE_URL');
    }
    /**
     * 根据配置项name获取admin配置信息
     * @param $name
     * @return array
     */
    public function adminConfigByName($name)
    {
        $url = "config/name/".$name;
        return $this->send_request($url,'get', "",  $this->adminBaseUrl);
    }

    /**
     * 获取全部admin配置信息
     * @return array
     */
    public function getAdminConfig()
    {
        $url = "config";
        return $this->send_request($url,"get", "",  $this->adminBaseUrl);
    }

    /**
     * 根据code获取admin配置信息
     * @param $code
     * @return array
     */
    public function getConfigByCode($code)
    {
        $url = "config/code/".$code;
        return $this->send_request($url,"get", "",  $this->adminBaseUrl);
    }
    
}