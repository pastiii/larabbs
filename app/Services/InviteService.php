<?php

namespace App\Services;

use App\Support\ApiRequestTrait;


/**
 * Created by PhpStorm.
 * User: Shao
 * Date: 2018/6/6
 * Time: 17:13
 */
class InviteService
{
    use ApiRequestTrait;

    protected $messageBaseUrl;
    private $http ;
    private $userListPrefix = 'userauth/user?';
    private $userCountPrefix = 'userauth/user/count?parent_user_id=';

    public function __construct()
    {
        $this->http = env('AUTH_BASE_URL');

    }


    public function getInviteList($data)
    {
        $url = $this->getParam($data);
        $fullUrl = $this->userListPrefix.$url;
        $result = $this->send_request($fullUrl, 'get', "", $this->http);

        return $result;
    }


    private function getParam($data, $method = 'get')
    {
        if ($method == 'get')
            $str = '';
        foreach ($data as $k => $v) {
            $str .= "&{$k}={$v}";
        }
        return substr($str, 1);
    }


    public function getInviteCount($data)
    {
        $fullUrl = $this->userCountPrefix.$data;
        $result = $this->send_request($fullUrl, 'get', "", $this->http);
        return $result;
    }



}