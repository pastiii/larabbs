<?php

namespace App\Services;

use App\Support\ApiRequestTrait;
use Illuminate\Support\Facades\Redis;

/**
 * Created by PhpStorm.
 * User: Shao
 * Date: 2018/6/7
 * Time: 14:32
 */
class AgentService {
    use ApiRequestTrait;
    const TYPE = 1;

    protected $agentBaseUrl;
    protected $sumUserBaseUrl;
    protected $authBaseUrl;

    public function __construct()
    {
        $this->agentBaseUrl = env('AGENT_BASE_URL');
        $this->sumUserBaseUrl = env('SUMUSER_BASE_URL');
        $this->authBaseUrl = env('AUTH_BASE_URL');
    }


    /**
     *  agent_promo
     * 获取推广码信息
     * @param int $id
     * @return  array
     */
    public function getAgentPromo($id) {
        // 获取数据返回
        $url = 'agent/agent_promo/id/' . $id;
        return $this->send_request($url, 'GET', [], $this->agentBaseUrl);
    }


    /**
     * 通过推广码获取推广码信息
     * @param $agent_promo
     * @return array
     */
    public function getAgentPromoByPromo($agent_promo) {
        // 获取数据返回
        $url = 'agent/agent_promo/promo/' . $agent_promo;
        return $this->send_request($url, 'GET', [], $this->agentBaseUrl);
    }



    /**
     * agent_info
     * 获取代理商详细信息
     * @param $agent_id
     * @return array
     */
    public function getAgentInfo($agent_id) {
        // 获取数据
        $url = 'agent/agent_info/id/' . $agent_id;
        $agentInfo = $this->send_request($url, 'GET', [], $this->agentBaseUrl);
        return $agentInfo;
    }


}
