<?php

namespace App\Http\Controllers\Api\V1\Authorize;

use App\Support\ApiRequestTrait;
use App\Support\SaltTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\ApiService;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api\V1\Authorize
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/5
 * Time: 15:13
 */
class ApiController extends BaseController
{
    use ApiRequestTrait, SaltTrait;
    /**
     * @var ApiService
     */
    protected $apiService;

    /**
     * AccountSecurity constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getApiService()
    {
        if (!isset($this->apiService)) {
            $this->apiService = app('api');
        }
        return $this->apiService;
    }

    /**
     * 创建API
     * @param Request $request
     * @return array
     */
    public function createAccess(Request $request)
    {
        $this->getapiService();

        //判断用户Api数目是否符合标准
        $access_msg = $this->apiService->getAccessCount($this->user_id);
        if ($access_msg['code'] != 200) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //判断Api是否达到上限
        if ($access_msg['data']['count'] >= 5) {
            $code = $this->code_num('UpperLimit');
            return $this->errors($code, __LINE__);
        }

        //数据验证
        $data = $this->validate($request, [
            'note'   => 'nullable|string|max:40',
            'ip'     => 'nullable|string',
        ]);

        //获取数据
        $ip = empty($data['ip']) ? "" : $data['ip'];
        $data['note'] =  empty($data['note']) ? "" : $data['note'];
        unset($data['ip']);
        if (empty($ip)) {
            $data['allow_ip']  = $ip;
            $data['expire_at'] = intval(date('Ymd', strtotime("+90 day")));
        }else{
            $data['allow_ip']  = str_replace(",", ";", $ip);
            $data['expire_at'] = null;
        }
        $data['access_key']    = $this->getUnique();
        $data['access_secret'] = $this->getUnique();
        $token = $this->get_user_info();
        $data['user_id']   = intval($token['user_id']);
        $data['user_name'] = $token['user_name'];

        //创建Api
        $access_info = $this->apiService->createUserAccess($data);
        if ($access_info['code'] !=200) {
            $code = $this->code_num('CreateFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);
    }

    /**
     * 用户API列表
     * @return array
     */
    public function getAccessList()
    {
        $this->getApiService();

        //获取数据
        $access_info = $this->apiService->getAccessListById($this->user_id);

        //判断
        if ($access_info['code'] != 200) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //数据处理
        $data = [];
        foreach ($access_info['data']['list'] as $value) {
            if (empty($value['allow_ip'])) {
                $value['expire_at'] = (strtotime($value['expire_at']) < time()) ? 0 : ceil((strtotime($value['expire_at'])-time())/86400);
                array_push($data, $value);
            }else{
                $value['expire_at'] = "";
                array_push($data, $value);
            }
        }

        return $this->response($data, 200);
    }

    /**
     * 删除API
     * @param Request $request
     * @return array
     */
    public function deleteAccess(Request $request)
    {
        $data = $this->validate($request, [
            'user_access_id' => 'required|int'
        ]);

        //删除API
        $this->getApiService();
        $access_info = $this->apiService->delAccess($data['user_access_id']);

        //判断
        if ($access_info['code'] != 200) {
            $code = $this->code_num('DeleteFailure');
            return $this->errors($code, __LINE__);
        }

        return $this->response("", 200);

    }

    /**
     * 修改API
     * @param Request $request
     * @return array
     */
    public function editAccess(Request $request)
    {
        $data = $this->validate($request, [
            'note'              => 'nullable|string|max:40',
            'ip'                => 'nullable|string',
            'user_access_id'    => 'required|string',
        ]);
        //获取数据
        $ip = empty($data['ip']) ? "" : $data['ip'];
        $id = $data['user_access_id'];
        $data['note'] =  empty($data['note']) ? "" : $data['note'];
        unset($data['ip']);
        unset($data['id']);
        if (empty($ip)) {
            $cols = "true";
            $data['allow_ip']  = $ip;
            $data['expire_at'] = intval(date('Ymd', strtotime("+90 day")));
        }else{
            $data['allow_ip']  = str_replace(",", ";", $ip);
            $data['expire_at'] = null;
            $cols = "true";
        }
        $data['access_key']    = $this->getUnique();
        $data['access_secret'] = $this->getUnique();

        //修改
        $this->getApiService();
        $Api_info = $this->apiService->updateAPI($id, $data, $cols);

        if ($Api_info['code'] != 200) {
            $code = $this->code_num('UpdateFailure');
            return $this->errors($code, __LINE__);
        }
        return $this->response("", 200);
    }
}
