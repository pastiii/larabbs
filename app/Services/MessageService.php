<?php

namespace App\Services;

use App\Support\ApiRequestTrait;


/**
 * Created by PhpStorm.
 * User: Shao
 * Date: 2018/6/6
 * Time: 17:13
 */
class MessageService
{
    use ApiRequestTrait;

    protected $messageBaseUrl;

    public function __construct()
    {
        $this->messageBaseUrl = env('MESSAGE_BASE_URL');
    }

    /**
     * 获取用户消息列表
     * http://192.168.2.211:28083/api/v1/server/usermessage/user_message?order=DESC&sort=user_message_id&order1=ASC&sort1=has_read&user_id=1&has_read=1&limit=10&start=0
     * @param $data
     * @return array
     */
    public function getUserMessageList($data)
    {
        $data['sort1'] =  'has_read';
        $data['order1'] =  'ASC';

        $data['start'] = ($data['page']-1) * $data['limit'];
        unset($data['page']);
        $url = "usermessage/user_message?".http_build_query($data);
        // $res = $this->getGetApiForReturn($url,env('MESSAGE_PORT'));

        $res = $this->send_request($url,'GET','',$this->messageBaseUrl);


        if ( $res['code'] == 200 && !empty($res['data']['list']) )
        {
            foreach ( $res['data']['list'] as $k => $v )
            {
                unset($res['data']['list'][$k]['user_name']);
                unset($res['data']['list'][$k]['message_type_id']);
                unset($res['data']['list'][$k]['message_type_name']);
                unset($res['data']['list'][$k]['read_time']);
                unset($res['data']['list'][$k]['updated_at']);
            }
        }

        return $res;
    }

    /**
     * 获取消息数量
     *  GET   http://192.168.2.211:28083/api/v1/server/usermessage/user_message/count?user_id=1
     *  GET   http://192.168.2.211:28083/api/v1/server/usermessage/user_message/count?user_id=1&has_read=1
     * @param $id
     * @return array
     */
    public function getUserMessageCount($id)
    {
        $url = "usermessage/user_message/count?user_id=".$id;
        $count =   $this->send_request($url,'GET','',$this->messageBaseUrl);

        $count_url = "usermessage/user_message/count?user_id=".$id.'&has_read=1';
        $count_read =   $this->send_request($count_url,'GET','',$this->messageBaseUrl);

        return [
            'count' => $count['data']['count'],
            'count_read' => $count_read['data']['count'],
        ];
    }
    /**
     * 更新用户消息   标记已读
     * PATCH        http://192.168.2.211:28083/api/v1/server/usermessage/user_message
     * @param array $ids
     * @param int $user_id
     * @return array
     */
    public function signRead($ids,$user_id)
    {
        $data = [
            'user_message_ids' => $ids,
            'user_id' => $user_id,
            'has_read' => 2
        ];
        $url = "usermessage/user_message";
        return  $this->send_request($url,'PATCH',$data,$this->messageBaseUrl);
    }

    /**
     * 批量删除用户消息
     * DELETE  http://192.168.2.211:28083/api/v1/server/usermessage/user_message
     * @param $ids
     * @param $user_id
     * @return array
     */
    public function deleteBatchUserMessage($ids,$user_id)
    {
        $data = [
            'user_message_ids' => $ids,
            'user_id' => $user_id,
        ];


        $url = "usermessage/user_message";
        return  $this->send_request($url,'DELETE',$data,$this->messageBaseUrl);
    }



    /**
     * 通过消息获取消息
     * GET      http://192.168.2.211:28083/api/v1/server/usermessage/user_message/id/1
     * @param $id
     * @return array
     */
    public function getUserMessage($id)
    {
        $url = 'usermessage/user_message/id/'.$id;
        return $this->send_request($url,'get',[],$this->messageBaseUrl);
    }

    /**
     * 创建消息
     * POST http://192.168.2.211:28083/api/v1/server/usermessage/user_message
     * @param $data [user_id  user_name  message_type_id   message_type_name  message_title message_body]
     * @return array
     */
    public function createUserMessage($data)
    {
        $url = 'usermessage/user_message';
        return $this->send_request($url,'post',$data,$this->messageBaseUrl);
    }




}