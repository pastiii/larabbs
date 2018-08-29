<?php

namespace App\Services;
use App\Support\ApiRequestTrait;

class UserLogService
{
    use ApiRequestTrait;
    protected $logBaseUrl;

    public function __construct()
    {
        $this->logBaseUrl = env('LOG_BASE_URL');
    }

    /**
     * create user log
     * @param $info
     * @return array
     */
    public function createLog($info)
    {
        $data['user_id']            = intval($info['data']['user_id']);
        $data['user_name']          = $info['data']['user_name'];
        $data['agent_id']           = intval($info['data']['agent_id']);
        $data['agent_name']         = $info['data']['agent_name'];
        $data['parent_user_id']     = intval($info['data']['parent_user_id']);
        $data['parent_user_name']   = $info['data']['parent_user_name'];
        $data['agent_promo_id']     = intval($info['data']['agent_promo_id']);
        $url = "loguser/new_user";
        return $this->send_request($url,"post", $data, $this->logBaseUrl);
    }

    /**
     * create user login log
     * @param array $data
     * @return array
     */
    public function createLoginLog($data)
    {
        $url = "loguser/user_login";
        return $this->send_request($url, "post", $data, $this->logBaseUrl);
    }


}