<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/7
 * Time: 13:18
 */

namespace App\Support;

use App\Services\AgentService;

trait AgentStatusTrait
{
    /* @var AgentService */
    protected $agentService;

    /**
     * 获取
     * @return AgentService
     */
    protected function getAgentService()
    {
        if (!isset($this->agentService)) {
            $this->agentService = app('agent');
        }
        return $this->agentService;
    }

    /**
     * 二次验证是否开启
     * @param $id
     * @return array
     */
    public function GetStatus($id)
    {
        //判断用户是否开启二次验证
        $status_info = $this->agentService->getUserStatus($id);
        if ($status_info['code'] != 200) {
            return ['code' => $status_info['code']];
        }

        //判断是否开启二次验证(开启的是哪个二次验证)
        if ($status_info['data']['second_email_status'] == 1) {
            $status_data[] = "second_email_status";
        }
        if ($status_info['data']['second_phone_status'] == 1) {
            $status_data[] = "second_phone_status";
        }
        if ($status_info['data']['second_google_auth_status'] == 1) {
            $status_data[] = "second_google_auth_status";
        }
        if (empty($status_data)) {
            return ['code' => 200, 'data' => ""];
        }

        return ['code' => 200, 'data' => $status_data];
    }

}