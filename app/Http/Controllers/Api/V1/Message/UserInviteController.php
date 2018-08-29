<?php
/**
 * Created by PhpStorm.
 * User: funny
 * Date: 2018/7/14
 * Time: 14:37
 */

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Api\V1\BaseController;
use App\Services\InviteService;
use App\Http\Requests;
use Illuminate\Http\Request;


class UserInviteController extends BaseController
{
    private $inviteService;
    private $kt;
    private $order = "DESC";
    private $sort  = "user_id";
    private $limit = 10;
    public $fieldRelation = [
        'invite' => [
            'user_name' => 'name',
            'created_at' => 'create_time'
        ],
    ];

    public function __construct(InviteService $inviteService)
    {
        parent::__construct();
        $this->inviteService = $inviteService;
        $this->kt = env('KT_CON', 150);
    }

    public function detail(Request $request)
    {
        $userId = $this->user_id;
        if($userId == 0)
        {
            $num = $this->code_num("GetMsgFail");
            return $this->errors($num);
        }

        $data = [
          'sort'            => $this->sort,
          'order'           => $this->order,
          'limit'           => $this->limit,
          'start'           => empty($request->page) ? 1 : $this->limit*($request->page-1),
          'parent_user_id'  => $this->user_id,
        ];

        $inviteList = $this->inviteService->getInviteList($data);
        $inviteCount = $this->inviteService->getInviteCount($this->user_id);
        if ($inviteList['real_code'] != 200 || $inviteCount['real_code'] != 200) {
            $num = $this->code_num("GetMsgFail");
            return $this->errors($num);
        }

        $kt = ($inviteCount['data']['count'] + 1) * intval($this->kt);
        $result = $this->getFiled($inviteList['data']['list'], 'invite');
        $page = [
            'CurrentPage' => $request->page,
            'TotalPages'  => ceil($inviteCount['data']['count']/$this->limit),
        ];
        $responseData = ['user_list' => $result, 'kt_count' => $kt, 'user_count' => $inviteCount['data']['count'], 'able_kt_count' => 0, 'page' => $page];

        return $this->response($responseData, 200);

    }

    public function getFiled($data, $type)
    {
        $list = [];
        if (count($data) > 0) {
            $arr = ($this->fieldRelation)[$type];
            foreach ($data as $v) {
                $item = [];
                foreach ($v as $kk => $vv) {
                    if (isset($arr[$kk])) {
                        $item[$arr[$kk]] = $vv;
                    }
                }
                $item['status'] = 2;
                $list[] = $item;
            }
        }
        return $list;
    }

    public function export()
    {
        $userId = $this->user_id;
        if ($userId == 0) {
            $num = $this->code_num("GetMsgFail");
            return $this->errors($num);
        }

        $result = [
            'sort'            => $this->sort,
            'order'           => $this->order,
            'parent_user_id'  => $this->user_id,
        ];

        $list =  $this->inviteService->getInviteList($result);
        if ($list['real_code'] != 200) {
            $num = $this->code_num("GetMsgFail");
            return $this->errors($num);
        }
        $data = $this->getFiled($list['data']['list'], 'invite');
        return $this->response($data, 200);
    }
}

