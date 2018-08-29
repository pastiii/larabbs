<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Token\Apiauth;

class BaseController extends Controller
{
    const TYPE = 1;
    protected $user_id;
    public function __construct()
    {
        $this->user_id =  intval(ApiAuth::userId());

    }

    /**
     *数据返回
     * @param $data
     * @param int $code
     * @return array
     */
    protected function response($data, $code = 0)
    {
        return [
            'status_code' => $code,
            'timestamp' => time(),
            'data' => $data,
        ];
    }

    /**
     * 错误数据返回
     * @param int $code
     * @param  $line
     * @return array
     */
    protected function errors( $code = 0, $line = '')
    {
        return [
            'status_code' => $code,
            'line' => $line,
            'timestamp' => time(),
        ];
    }

    /**
     * 验证错误信息
     * @param $code
     * @return mixed
     */
    protected function code_num($code){
        return config('state_code.'.$code);
    }

    /**
     * 根据获取用户信息
     * @return array
     */
    protected function get_user_info()
    {
        return ApiAuth::user();
    }

    /**
     * 获取token
     * @param $user_info
     * @param $type
     */
    public function getToken($user_info,$type = self::TYPE)
    {
        return ApiAuth::login($user_info,$type);
    }

}


?>

