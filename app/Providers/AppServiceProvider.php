<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AdminConfigService;
use App\Services\ApiService;
use App\Services\PromoService;
use Illuminate\Support\Facades\Validator;
use App\Token\Token;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //自定义验证码规则
        Validator::extend('authentication', function($attribute, $value, $parameters){
            return $this->validationFilterIdCard($value);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //token注册
        $this->app->singleton('Token',function ($app){
            return new Token($app->request);
        });

        //Api
        $this->app->singleton('api',function(){
            return new ApiService();
        });

        //promo
        $this->app->singleton('promo',function(){
            return new PromoService();
        });

        //config
        $this->app->singleton('admin_config',function(){
            return new AdminConfigService();
        });
    }

    function validationFilterIdCard( $id_card){
        if(strlen($id_card)==18){
            return $this->idCardChecksumEighteen($id_card);
        }elseif((strlen($id_card)==15)){
            $id_card=$this->idCardFifteenToEighteen($id_card);
            return $this->idCardCheckSumEighteen($id_card);
        }else{
            return false;
        }
    }
    // 计算身份证校验码，根据国家标准GB 11643-1999

    /**
     * @param $idCard_base
     * @return bool
     */
    function idCardVerifyNumber($idCard_base){
        if(strlen($idCard_base)!=17){
            return false;
        }
        //加权因子
        $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
        //校验码对应值
        $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
        $checksum=0;
        for($i=0;$i<strlen($idCard_base);$i++){
            $checksum += substr($idCard_base,$i,1) * $factor[$i];
        }
        $mod=$checksum % 11;
        $verify_number=$verify_number_list[$mod];
        return $verify_number;
    }
    // 将15位身份证升级到18位
    function idCardFifteenToEighteen($idCard){
        if(strlen($idCard)!=15){
            return false;
        }else{
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if(array_search(substr($idCard,12,3),array('996','997','998','999')) !== false){
                $idCard=substr($idCard,0,6).'18'.substr($idCard,6,9);
            }else{
                $idCard=substr($idCard,0,6).'19'.substr($idCard,6,9);
            }
        }
        $idCard=$idCard.$this->idCardVerifyNumber($idCard);
        return $idCard;
    }
    // 18位身份证校验码有效性检查
    function idCardChecksumEighteen($idCard){
        if(strlen($idCard)!=18){
            return false;
        }
        $idCard_base=substr($idCard,0,17);
        if($this->idCardVerifyNumber($idCard_base)!=strtoupper(substr($idCard,17,1))){
            return false;
        }else{
            return true;
        }
    }



}
