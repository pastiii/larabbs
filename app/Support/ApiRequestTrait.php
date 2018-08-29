<?php
namespace App\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Log;

/**
 * Class ApiRequestTrait
 * @package App\Support
 * Created by PhpStorm.
 * @author: huan
 * Date: 2018/5/21
 * Time: 15:02
 */
trait ApiRequestTrait
{
    /**
     * @param  string $url
     * @param string $request_type
     * @param array $resources
     * @param string $url_suffix
     * @param string $form
     * @return array
     */
    protected  function send_request($url, $request_type, $resources=[],$url_suffix, $ClientIp=[],$form='json')
    {
        $client = new Client(['base_uri' => $url_suffix]);
        $data = ['code' => 0, 'real_code'=>500,'message' => 'error','data' => []];
        try {
            /* 接受请求类型 */
            $request_type = empty($request_type) ? 'GET' :strtoupper($request_type);
            /* 请求参数 */
            $resources = empty($resources) ? [] : [$form=>$resources];
            /* 发送请求 */
            $resources['headers'] = $ClientIp;
            $res = $client->request($request_type, $url,$resources);
            $data['code'] = $res->getStatusCode();
            $data['real_code'] = $res->getStatusCode();
            $data['message'] = 'success';
            $data['data'] = json_decode($res->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $res = $e->getResponse();
                $result = json_decode($res->getBody(), true);
                $data['code'] = $res->getStatusCode() == 404 ? 200 : $res->getStatusCode();
                $data['real_code'] = $res->getStatusCode();
                $data['message'] = 'error';
                $data['data'] = [];
                /* 记录api请求日志 */
                $this->apiLog($url_suffix.$url,$resources,$result);
            }
            //无响应的情况
            $this->apiLog($url_suffix.$url,$resources,'null');
        }
        return $data;
    }

    /**
     * 记录api日志
     * @param $url
     * @param $request_param
     * @param $response
     */
    protected function apiLog($url,$request_param,$response)
    {
        $data['request_url'] = $url;
        $data['request_param'] = $request_param;
        $data['response'] = $response;
        Log::useFiles(storage_path('logs/api/'.date('Y-m-d',time()).'.log'));
        Log::info($data);
    }
}
