<?php

namespace App\Http\Controllers\APi\V1\Authorize;

use App\Http\Controllers\Api\V1\BaseController;
use App\Services\PromoService;
use Dingo\Api\Http\Request;

/**
 * Class PromoController
 * @package App\Http\Controllers\APi\V1\Authorize
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/6
 * Time: 13:45
 */
class PromoController extends BaseController
{
    /**
     * @var PromoService
     */
    protected $promoService;

    /**
     * AccountSecurity constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return PromoService|\Illuminate\Foundation\Application|mixed
     */
    protected function getPromoService()
    {
        if (!isset($this->promoService)) {
            $this->promoService = app('promo');
        }
        return $this->promoService;
    }

    /**
     * 用户推广地址
     * @return array
     */
    public function getUserPromo()
    {
        $this->getPromoService();
        $info = $this->promoService->getPromo($this->user_id);

        //判断是否成功获取数据
        if ($info['code'] != 200) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //创建数据
        $data['parent_user_name']   = $info['data']['user_name'];
        $data['parent_user_id']     = $info['data']['user_id'];
        $data['agent_promo_agent']  = $info['data']['user_promo'];
        return $this->response($data, 200);

    }

    /**
     * 推广列表信息
     * @param  Request $request
     * @return array
     */
    public function GetPromoList(Request $request)
    {
        $start = $this->validate($request, [
            'page'  => 'required|int'
        ]);
        $pageSize   = 10;

        $this->getPromoService();
        $user_info  = $this->get_user_info();

        $promo_info = $this->promoService->promoCount($user_info);

        if ($promo_info['code'] != 200) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //获取受邀用户信息
        $other_info = $this->promoService->getPromoEntry($user_info, $start['page'], $pageSize);
        if ($other_info['code'] != 200) {
            $code = $this->code_num('GetUserFail');
            return $this->errors($code, __LINE__);
        }

        //数据处理
        $data = $this->promoService->getPromoData($other_info);
        $data['count'] = $promo_info['data']['count'];
        $data['total'] = ceil($promo_info['data']['count']/$pageSize);

        if (empty($other_info['data']['list'])) {
            return $this->response($data, 200);
        }

        return $this->response($data, 200);

    }
}
