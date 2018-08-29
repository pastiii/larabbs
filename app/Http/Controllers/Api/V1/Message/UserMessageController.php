<?php

namespace App\Http\Controllers\Api\V1\Message;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\MessageService;


/**
 * Created by PhpStorm.
 * User: Shao
 * Date: 2018/6/6
 * Time: 17:13
 */
class UserMessageController extends BaseController
{
    protected $messageService;

    /**
     * UserMessageController constructor.
     * @param MessageService $messageService
     */
    public function __construct(MessageService $messageService)
    {
        parent::__construct();
         $this->messageService = $messageService;
    }

    /**
     * 消息列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $request['user_id'] = $this->user_id;
        $data = $this->validate($request, [
            'user_id' => 'required|int',
            'has_read' => 'nullable|in:1,2',
            'limit' => 'nullable|int',
            'page' => 'nullable|int',
        ]);
        $data['page'] = empty($data['page'])?1:$data['page'];
        $data['limit'] = empty($data['limit'])?10:$data['limit'];


        $message_list = $this->messageService->getUserMessageList($data);

        if ( $message_list['code'] == 200 )
        {
            $count_list = $this->messageService->getUserMessageCount( $this->user_id );

            $page_info['current_page'] = $data['page'];
            $page_info['total_page'] =  ceil($count_list['count']/$data['limit']);

            $message_list = array_merge($message_list['data'],$count_list,$page_info);

            return $this->response($message_list, 200);
        }
        else
        {
            $num = $this->code_num("GetMsgFail");
            return $this->errors($num);
        }
    }


    /**
     * 标记阅读
     * @param Request $request
     * @return array
     */
    public function signRead(Request $request)
    {
        $data = $this->validate($request, [
            'ids'         => 'required|array',
        ]);

        foreach ($data['ids'] as $k => $v)
        {
            $data['ids'][$k] = intval($v);
        }
        // 验证 当前用户
        
        $sign_read = $this->messageService->signRead($data['ids'],$this->user_id);


        if ( $sign_read['code'] == 200 )
        {
            return $this->response( '标记成功',200);
        }
        else
        {
            $num = $this->code_num("UpdateFailure");
            return $this->errors( $num);
        }
    }

    /**
     * 批量删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $data = $this->validate($request, [
            'ids'         => 'required|array',
        ]);

        foreach ($data['ids'] as $k => $v)
        {
            $data['ids'][$k] = intval($v);
        }


        $delete_res = $this->messageService->deleteBatchUserMessage($data['ids'],$this->user_id);
 
        if ( $delete_res['code'] == 200 )
        {
            return $this->response($delete_res['data'], 200);
        }
        else
        {
            $num = $this->code_num("DeleteFailure");
            return $this->errors( $num);
        }
    }


    /**
     * 消息详情
     * @param Request $request
     * @return array
     */
    public function details(Request $request)
    {
        $data = $this->validate($request, [
            'id'         => 'required|int',
        ]);

        $message_details = $this->messageService->getUserMessage($data['id']);
                           
        // 验证 当前用户

        if ( $message_details['code'] == 200 )
        {
            if ( $message_details['data']['has_read'] == 1 )
            {
                $this->messageService->signRead([intval($data['id'])],$this->user_id);
            }

            return $this->response($message_details['data'], 200);
        }
        else
        {
            $num = $this->code_num("GetMsgFail");
            return $this->errors( $num);
        }
    }




}
