<?php

namespace App\Services;
use App\Support\ApiRequestTrait;

class TicketService
{
    use ApiRequestTrait;

    protected $ticketBaseUrl;

    public function __construct()
    {
        $this->ticketBaseUrl = env('TICKET_BASE_URL');
    }

    /**
     * 获取工单列表
     * @param $data array
     * @return array
     */
    public function getTicketList($data)
    {
        if($data['start'] <= 0) $data['start']=1;

        //获取进行中工单数据
        if($data['ticket_status'] != 2){
            $ticket=[
                'user_id'=>$data['user_id'],
                'ticket_type_id'=>$data['ticket_type_id'],
            ];
            $count=$this->getTicketCount($ticket); //工单数量
            if($count['code'] != 200){
                $ticket['limit']=1000;
            }else{
                $ticket['limit']=$count['data']['count'];
            }
            $ticket['order'] = 'DESC';
            $ticket['sort']  = 'ticket_id';

            //获取工单状态为1(处理中的工单)
            $ticket['ticket_status']=1;
            $url = "ticket/ticket?" . http_build_query($ticket);
            $ticket_data1=$this->send_request($url, 'get',$ticket,$this->ticketBaseUrl);
            if($ticket_data1['code'] != 200){
                return $ticket_data1;
            }
            //获取工单状态为0(未开始的工单)
            $ticket['ticket_status']=0;
            $url = "ticket/ticket?" . http_build_query($ticket);
            $ticket_data0=$this->send_request($url, 'get',$ticket,$this->ticketBaseUrl);
            if($ticket_data0['code'] != 200){
                return $ticket_data0;
            }
            //合并数据
            $ticket_merge= array_merge($ticket_data1['data']['list'],$ticket_data0['data']['list']);
            //返回状态码
            $list['code']=200;
            //计算总数
            $count=count($ticket_merge);
            //分页信息
            $page_info['count']=$count;//总数
            $page_info['current_page'] = intval($data['start']);//当前页
            $page_info['total_page'] =  ceil($count/$data['limit']);//总页数
            //开始节点
            $start=($data['start']-1)*$data['limit'];
            //分段取数据
            $list['data']['list']=array_slice($ticket_merge,$start,$data['limit']);
            $list['data']['page']=$page_info;
            return $list;
        }

        //获取已完结工单数据
        $count=$this->getTicketCount($data); //工单数量
        if($count['code'] != 200){
            return $count;
        }
        $page_info['count']=$count['data']['count'];
        $page_info['current_page'] = intval($data['start']);
        $page_info['total_page'] =  ceil($count['data']['count']/$data['limit']);
        $data['start']=($data['start']-1)*$data['limit'];
        $url = "ticket/ticket?" . http_build_query($data);
        $ticket_data=$this->send_request($url, 'get',$data,$this->ticketBaseUrl);

        if($ticket_data['code'] ==200){
            $ticket_data['data']['page']=$page_info;
            return $ticket_data;
        }
        return $this->send_request($url, 'get',$data,$this->ticketBaseUrl);
    }
    /**
     * 获取工单数量
     * @param $data array
     * @return array
     */
    public function getTicketCount($data){
        if(isset($data['limit'])) unset($data['limit']);
        if(isset($data['start'])) unset($data['start']);
        $url = "ticket/ticket/count?".http_build_query($data);
        return $this->send_request($url, 'get',$data,$this->ticketBaseUrl);
    }

    /**
     * 获取工单
     * @param $id int
     * @return array
     */
    public function getTicket($id)
    {
        $url = "ticket/ticket/id/" . $id;
        return $this->send_request($url, 'get','',$this->ticketBaseUrl);
    }

    /**
     * 获取工单文件
     * @param $id int
     * @return array
     */
    public function getTicketFile($id)
    {
        $url = "ticket/ticket_file?ticket_id=".$id;
        return $this->send_request($url, 'get','',$this->ticketBaseUrl);
    }

    /**
     * 获取工单类型列表
     * @param $data array
     * @return array
     */
    public function getTicketTypeList($data)
    {
        //获取工单类型数量
        $count=$this->getTicketTypeCount($data);
        if($count['code'] != 200){
            return $count;
        }

        if($data['start'] <= 0) $data['start']=1;
        $page_info['count']=$count['data']['count'];
        $page_info['current_page'] = intval($data['start']);
        $page_info['total_page'] =  ceil($count['data']['count']/$data['limit']);
        $data['start']=($data['start']-1)*$data['limit'];

        $url = "ticket/ticket_type?" . http_build_query($data);
        $ticket_data=$this->send_request($url, 'get','',$this->ticketBaseUrl);
        if($ticket_data['code'] ==200){
            $ticket_data['data']['page']=$page_info;
        }
        return $ticket_data;
    }

    /**
     * 获取工单类型数量
     * @param $data array
     * @return array
     */
    public function getTicketTypeCount($data)
    {
        if(isset($data['limit'])) unset($data['limit']);
        if(isset($data['start'])) unset($data['start']);
        $url = "ticket/ticket_type/count?" . http_build_query($data);
        return $this->send_request($url, 'get','',$this->ticketBaseUrl);
    }

    /**
     * 创建工单
     * @param $data array
     * @return array
     */
    public function createTicket($data)
    {
        $url = "ticket/ticket";
        return $this->send_request($url, 'post',$data,$this->ticketBaseUrl);
    }
    /**
     * 更新工单
     * @param $id
     * @param $data
     * @return array
     */
    public function updateTicket($id,$data)
    {
        $url = "ticket/ticket/id/". $id ;
        return $this->send_request($url, 'patch',$data,$this->ticketBaseUrl);
    }

    /**
     * 删除工单
     * @param $ticket_id
     * @return array
     */
    public function deleteTicket($ticket_id)
    {
        $url = "ticket/ticket/id/". $ticket_id ;
        return $this->send_request($url, 'delete',[],$this->ticketBaseUrl);
    }

    /**
     * 创建工单描述
     * @param $data
     * @return array
     */
    public function createTicketDetail($data)
    {
        $url = "ticket/ticket_detail";
        return $this->send_request($url, 'post',$data,$this->ticketBaseUrl);
    }


    /**
     * 获取工单描述
     * @param $data
     * @return array
     */
    public function getTicketDetail($data)
    {
        //获取工单类型数量
        $count=$this->getTicketDetailCount($data);
        if($count['code'] != 200){
            return $count;
        }

        if($data['start'] <= 0) $data['start']=1;
        $page_info['count']=$count['data']['count'];
        $page_info['current_page'] = intval($data['start']);
        $page_info['total_page'] =  ceil($count['data']['count']/$data['limit']);
        $data['start']=($data['start']-1)*$data['limit'];

        $url = "ticket/ticket_detail?" . http_build_query($data);
        $ticketDetail= $this->send_request($url, 'get','',$this->ticketBaseUrl);

        if($ticketDetail['code'] == 200 ){
            $ticketDetail['data']['list']=array_reverse($ticketDetail['data']['list']);
            $ticketDetail['data']['page']=$page_info;
        }
        return $ticketDetail;

    }
    /**
     * 获取工单描述数量
     * @param $data array
     * @return array
     */
    public function getTicketDetailCount($data){
        $param['ticket_id']=$data['ticket_id'];
        $param['user_id']=$data['user_id'];
        $url = "ticket/ticket_detail/count?" . http_build_query($param);
        return $this->send_request($url, 'get','',$this->ticketBaseUrl);
    }

    /**
     * 创建工单文件
     * @param $data
     * @return array
     */
    public function createTicketFile($data)
    {
        $url = "ticket/ticket_file";
        return $this->send_request($url, 'post',$data,$this->ticketBaseUrl);
    }

    /**
     * 删除工单文件
     * @param $file_id
     * @return array
     */
    public function deleteTicketFile($file_id)
    {
        $url = "ticket/ticket_file/id/".$file_id;
        return $this->send_request($url, 'delete',[],$this->ticketBaseUrl);
    }
}