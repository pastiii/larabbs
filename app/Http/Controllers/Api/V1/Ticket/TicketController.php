<?php

namespace App\Http\Controllers\Api\V1\Ticket;

use App\Handlers\ImageUploadHandler;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\Request;
use App\Services\TicketService;
use App\Services\SecurityVerificationService;

class TicketController extends BaseController
{

    protected $ticketService;

    /**
     * UserInfoController constructor.
     * @param TicketService $ticketService
     */
    public function __construct(TicketService $ticketService)
    {
        parent::__construct();
        $this->ticketService = $ticketService;
    }

    /**
     * 获取工单列表
     * @param Request $request
     * @return array
     */
    public function getTicketList(Request $request)
    {
       $data= $this->validate($request,[
           'ticket_status'   =>'required|int|in:1,2',
           'limit'           =>'nullable|int|min:1',
           'start'           =>'nullable|int|min:1'
       ]);
       $data['ticket_type_id']='';
       $data['user_id']=$this->user_id;
       if(!isset($data['limit']))$data['limit']=10;
       if(!isset($data['start']))$data['start']=1;
       //获取工单列表
       $result=$this->ticketService->getTicketList($data);
       if($result['code'] == 200){
            $res=$this->response($result['data'], 200);
            return $res;
       }
       $code = $this->code_num('GetMsgFail');
       return $this->errors($code, __LINE__);
    }

    /**
     * 获取工单
     * @param int $id
     * @return array
     */
    public function getTicket($id){

        //获取工单
        $ticket=$this->ticketService->getTicket($id);
        //获取工单文件
        $ticket_file=$this->ticketService->getTicketFile($id);

        if($ticket_file['code'] != 200 ){
            $code = $this->code_num('GetFileFail');
            return $this->errors($code, __LINE__);
        }
        if(empty($ticket['data'])){
            $code = $this->code_num('TicketFail');
            return $this->errors($code, __LINE__);
        }
        $ticket['data']['ticket_file']=$ticket_file['data']['list'];

        if($ticket['code'] == 200){
            return $this->response($ticket['data'], 200);
        }
        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);
    }

    /**
     * 获取工单类型列表
     * @param Request $request
     * @return array
     */
    public function getTicketTypeList(Request $request)
    {
        $data=[
            'order'  => 'DESC',
            'sort'   => 'ticket_type_id',
            'limit'  => $request->get('limit',100),
            'start'  => $request->get('start',1)
        ];
        //获取工单类型列表
        $result=$this->ticketService->getTicketTypeList($data);
        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }
        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);

    }

    /**
     * 获取工单描述列表
     * @param Request $request
     * @return array
     */
    public function getTicketDetailList(Request $request)
    {
        $data= $this->validate($request,[
            'ticket_id'  =>'required|int',
            'order'      =>'nullable|string|in:ASC,DESC',
            'limit'      =>'nullable|int|min:1',
            'start'      =>'nullable|int|min:1'
        ]);
        $data['user_id']=$this->user_id;
        $data['sort']='ticket_detail_id';
        if(!isset($data['order']))$data['order']='DESC';
        if(!isset($data['limit']))$data['limit']=10;
        if(!isset($data['start']))$data['start']=1;
        //获取工单描述列表
        $result=$this->ticketService->getTicketDetail($data);
        if($result['code'] == 200){
            return $this->response($result['data'], 200);
        }
        $code = $this->code_num('GetMsgFail');
        return $this->errors($code, __LINE__);

    }

    /**
     * 创建工单描述
     * @param Request $request
     * @return array
     */
    public function createTicketDetail(Request $request)
    {
        $user=$this->get_user_info();
        $data=$this->validate($request,[
          'ticket_id'  =>'required|int',
          'ticket_body'=>'required|string'
        ]);
        $data['ticket_id']=intval($data['ticket_id']);
        $data['user_id']=$this->user_id;
        $data['user_name']=$user['user_name'];
        $data['is_ticket_server_body']=0;
        //创建工单描述
        $result=$this->ticketService->createTicketDetail($data);
        if($result['code'] != 200){
            $code = $this->code_num('CreateFailure');
            return $this->errors($code, __LINE__);
        }
        return $this->response($result['data'], 200);
    }

    /**
     * 创建工单
     * @param Request $request
     * @return array
     */
    public function createTicket(Request $request)
    {

        $user=$this->get_user_info();
        $ticket=[];
        $ticket_data = $this->validate($request, [
            'ticket_type_id'     => 'required|int',
            'ticket_type_name'   => 'required|string',
            'ticket_title'       => 'required|string|min:2',
            'extra'              => 'required|string|min:4',
            'captcha_code'       => 'required',
            'captcha_key'        => 'required',
            'ticket_file.*'        => 'nullable|string'
        ]);

        /* @var SecurityVerificationService $securityVerification 验证服务接口 */
        $securityVerification = app(SecurityVerificationService::class);

        //图片验证码验证
        $info['token'] = $ticket_data['captcha_key'];
        $info['code']  = $ticket_data['captcha_code'];
        $info = $securityVerification->checkCaptcha($info);

        if ($info['data']['msg'] != 'ok') {
            $code = $this->code_num('VerificationCode');
            return $this->errors($code, __LINE__);
        }

        //组建数据
        $ticket['ticket_type_id']=intval($ticket_data['ticket_type_id']);
        $ticket['ticket_type_name']=$ticket_data['ticket_type_name'];
        $ticket['ticket_title']=$ticket_data['ticket_title'];
        $ticket['user_id']=$this->user_id;
        $ticket['user_name']=$user['user_name'];
        $ticket['ticket_status']=0;
        $ticket['extra']=$ticket_data['extra'];
        //创建工单
        $result=$this->ticketService->createTicket($ticket);
        if($result['code'] != 200){
            $code = $this->code_num('CreateFailure');
            return $this->errors($code, __LINE__);
        }
        //工单文件
        if(isset($ticket_data['ticket_file']) && !empty($ticket_data['ticket_file'])){
            $file_data=$ticket_data['ticket_file'];
            foreach ($file_data as $file_path){
                if(empty($file_path)){
                    continue;
                }
                $data=[];
                //工单文件信息
                $data['ticket_id']=$result['data']['ticket_id'];
                $data['ticket_file_path']=$file_path;
                $data['ticket_file_title']=substr($file_path,(strrpos($file_path,'/')+1));
                //创建工单文件
                $res=$this->ticketService->createTicketFile($data);
                if($res['code'] ==200){
                    $result['data']['ticket_file'][]=$res['data'];
                }
            }
        }

        return $this->response($result['data'], 200);
    }

    /**
     *更新工单
     * @param Request $request
     * @return array
     */
    public function updateTicket(Request $request)
    {
        $ticket = $this->validate($request, [
            'ticket_id'          => 'required|int',
            'ticket_type_id'     => 'nullable|int',
            'ticket_type_name'   => 'nullable|string',
            'extra'              => 'nullable|string|min:4',
            'ticket_status'      => 'nullable|in:1,2'
        ]);
        if(isset($ticket['ticket_status'])){
            $ticket['ticket_status']=intval($ticket['ticket_status']);
        }

        //更新工单
        $result=$this->ticketService->updateTicket($request['ticket_id'],$ticket);

        if($result['code'] != 200){
            $code = $this->code_num('UpdateFailure');
            return $this->errors($code, __LINE__);
        }
        if(empty($result['data'])){
            $code = $this->code_num('TicketFail');
            return $this->errors($code, __LINE__);
        }
        return $this->response($result['data'], 200);

    }

    /**
     * 删除工单
     * @param $ticket_id
     * @return array
    */
    public function deleteTicket($ticket_id)
    {
        $res=$this->ticketService->deleteTicket($ticket_id);
        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('DeleteFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     *删除工单文件
     * @param $file_id
     * @return array
     */
    public function deleteTicketFile($file_id)
    {
        $res=$this->ticketService->deleteTicketFile($file_id);
        if ($res['code'] == 200){
            return $this->response("",200);
        }
        $code = $this->code_num('DeleteFailure');
        return $this->errors($code, __LINE__);
    }

    /**
     *工单文件下载
     * @param Request $request
     * @return array
     */
    public function download(Request $request){
        $data=$this->validate($request, [
            'file_path'=>'required|string'
        ]);
        //截取文件地址
        $filename=substr($data['file_path'],strpos($data['file_path'],'uploads'));
        //拼接文件绝对路径
        $file=public_path() . '/' .$filename;
        //文件存在输出下载
        if ( file_exists ( $file )) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);//输出一个文件
            exit;
        }else{
            $code = $this->code_num('GetFileFail');
            return $this->errors($code, __LINE__);
        }
    }
}
