<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** @var Dingo\Api\Routing\Router $api */
$api = app('Dingo\Api\Routing\Router');

/* 账户安全 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Authorize','prefix' => 'v1/authorize'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->patch('account_security/update_login_password','AccountSecurityController@updateLoginPassword');
            $api->patch('account_security/update_phone','AccountSecurityController@editPhone');
            $api->patch('account_security/check','AccountSecurityController@patchStatus');
            $api->post('account_security/get_phone_number','AccountSecurityController@getPhoneNumber');
            $api->patch('account_security/update_pin','AccountSecurityController@updatePin');
            $api->get('account_security/google','AccountSecurityController@getGoogleCode');
            $api->post('account_security/check_google_code','AccountSecurityController@checkGoogleCode');
            $api->post('account_security/check_google_code/type/{type}','AccountSecurityController@checkGoogleCode');
            $api->get('account_security/get_user_login_list','AccountSecurityController@getUserLoginList');
            $api->get('account_security/get_user_status','AccountSecurityController@getUserStatusById');
            $api->post('account_security/identification','AccountSecurityController@identification');
            $api->post('account_security/create_identification','AccountSecurityController@createIdentification');
            $api->patch('account_security/edit_high_identification','AccountSecurityController@updateHighIdentification');
            $api->patch('account_security/edit_identification','AccountSecurityController@updateIdentification');
            $api->post('account_security/create_phone','AccountSecurityController@createPhone');
            $api->post('account_security/create_pin','AccountSecurityController@createPin');
            $api->get('account_security/email_info','AccountSecurityController@emailInfo');
            $api->get('account_security/phone_info','AccountSecurityController@phoneInfo');
            $api->post('account_security/edit_user_info','AccountSecurityController@editUserInfo');
            $api->post('account_security/sms','AccountSecurityController@sms');
            $api->post('account_security/send_sms', 'AccountSecurityController@sendSms');
            $api->post('account_security/validate_email_code','AccountSecurityController@validateEmailCode');
            $api->post('account_security/validate_phone_code/type/{type}','AccountSecurityController@validatePhoneCode');
            $api->post('account_security/validate_phone_code', 'AccountSecurityController@validatePhoneCode');
            $api->post('account_security/validate_email_code/type/{type}','AccountSecurityController@validateEmailCode');
            $api->get('account_security/login_out','AccountSecurityController@login_out');
            $api->post('account_security/two_verification', 'AccountSecurityController@checkTwo');
            $api->post('account_security/send_email', 'AccountSecurityController@sendEmail');
            $api->post('account_security/crate_email', 'AccountSecurityController@crateEmail');
        });
        $api->post('account_security/retrievePassword', 'AccountSecurityController@retrievePassword');
    });
});

/* 登陆注册路由 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Authorize','prefix' => 'v1/authorize'], function ($api) {
            /** @var Dingo\Api\Routing\Router $api */
            $api->post('login/register','LoginController@createUser');
            $api->post('login','LoginController@login');
            $api->patch('login/reset_user','LoginController@resetUser');
            $api->post('login/validate_email','LoginController@validateEmailCode');
            $api->post('login/validate_phone','LoginController@validatePhoneCode');
            $api->post('login/send_email','LoginController@sendEmail');
            $api->post('log','LoginController@test_log');
            $api->group(['middleware'=>'apiauth'],function($api){
                /** @var Dingo\Api\Routing\Router $api */
                $api->get('check_login_status','LoginController@checkLoginStatus');
            });
        });
});

/* 交易中心个人信息路由 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\User','prefix' => 'v1/user'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->get('userinfo','UserInfoController@userInfo');//显示列表
            $api->get('userinfo/get_user_identification','UserInfoController@getUserIdentification');
            $api->post('userinfo/create_alipay','UserInfoController@createUserAccountAlipay');//创建支付宝
            $api->patch('userinfo/update_alipay','UserInfoController@updateUserAccountAlipay');//启动支付宝
            $api->get('userinfo/get_alipay/id/{id}','UserInfoController@getUserAccountAlipay');
            $api->delete('userinfo/delete_alipay/id/{id}','UserInfoController@deleteUserAccountAlipay');
            $api->post('userinfo/create_wechatpay','UserInfoController@createUserAccountWechatpay');//创建微信
            $api->patch('userinfo/update_wechatpay','UserInfoController@updateUserAccountWechatpay');
            $api->get('userinfo/get_wechatpay/id/{id}','UserInfoController@getUserAccountWechatpay');
            $api->delete('userinfo/delete_wechatpay/id/{id}','UserInfoController@deleteUserAccountWechatpay');
            $api->post('userinfo/create_bank','UserInfoController@createUserAccountBank');//创建银行卡
            $api->patch('userinfo/update_bank','UserInfoController@updateUserAccountBank');
            $api->get('userinfo/get_bank/id/{id}','UserInfoController@getUserAccountBank');
            $api->delete('userinfo/delete_bank/id/{id}','UserInfoController@deleteUserAccountBank');
            $api->post('userinfo/create_paypal','UserInfoController@createUserAccountPaypal');//创建PayPal账号
            $api->patch('userinfo/update_paypal','UserInfoController@updateUserAccountPaypal');
            $api->get('userinfo/get_paypal/id/{id}','UserInfoController@getUserAccountPaypal');
            $api->delete('userinfo/delete_paypal','UserInfoController@deleteUserAccountPaypal');//删除PayPal账号
            $api->get('userinfo/get_pay_list','UserInfoController@userPayList');
            $api->get('userinfo/user_info','UserInfoController@userInfo');
            $api->patch('userinfo/update_pay_status','UserInfoController@updatePayStatus');
            $api->get('userinfo/get_pay_to_id','UserInfoController@getPayToId');
        });
    });
});

/* 新闻中心 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Article','prefix' => 'v1/article'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->get('/get_news_list','ArticleController@getNewsList');
        $api->get('/get_details','ArticleController@GetDetails');
    });
});

/* API管理 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Authorize','prefix' => 'v1/authorize'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->post('api/create_access','ApiController@createAccess');
            $api->patch('api/edit_access','ApiController@editAccess');
            $api->delete('api/delete_access','ApiController@deleteAccess');
            $api->get('api/get_access_list','ApiController@getAccessList');
        });
    });
});

/* 推广管理 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Authorize','prefix' => 'v1/authorize'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->get('promo/get_promo','PromoController@getUserPromo');
            $api->get('promo/get_promo_list','PromoController@GetPromoList');
        });
    });
});

/* 用户消息 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Message','prefix' => 'v1/message'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->get('index','UserMessageController@index');//显示列表
            $api->get('details','UserMessageController@details');// 消息详情
            $api->patch('signRead','UserMessageController@signRead');// 标记阅读
            $api->delete('delete','UserMessageController@delete');// 删除消息


            //临时添加查询邀请用户信息
            $api->get('invite','UserInviteController@detail');
            $api->get('invite/export','UserInviteController@export');
        });
    });
});

/* 工单管理 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Ticket','prefix' => 'v1/ticket'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->get('get_ticket/id/{id}','TicketController@getTicket');
            $api->get('get_ticket_list','TicketController@getTicketList');//显示列表
            $api->get('get_ticket_type_list','TicketController@getTicketTypeList');
            $api->post('create_ticket','TicketController@createTicket');
            $api->get('get_ticket_detail_list','TicketController@getTicketDetailList');
            $api->post('create_ticket_detail','TicketController@createTicketDetail');
            $api->patch('update_ticket','TicketController@updateTicket');
            $api->delete('delete_ticket/ticket_id/{ticket_id}','TicketController@deleteTicket');
            $api->delete('delete_ticket_file/file_id/{file_id}','TicketController@deleteTicketFile');
            $api->post('create_ticket_file','TicketController@createTicketFile');
            $api->get('download','TicketController@download');
        });
    });
});


/* user二次验证 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Authorize','prefix' => 'v1/authorize'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->post('validate/send_sms','ValidateController@sendSms');
        $api->get('validate/send_sms', 'ValidateController@sendSms');
        $api->post('validate/send_email','ValidateController@sendEmail');
        $api->get('validate/send_email', 'ValidateController@sendEmail');
        $api->post('validate/validate_phone','ValidateController@validatePhoneCode');
        $api->post('validate/validate_email','ValidateController@validateEmailCode');
        $api->post('validate/check_google','ValidateController@checkGoogleCode');
        $api->get('validate/check_google', 'ValidateController@checkGoogleCode');
    });
});

/* 公共模块 */
$api->version('v1', function ($api) {
    /** @var Dingo\Api\Routing\Router $api */
    $api->group(['namespace' => 'App\Http\Controllers\Api\V1\Common','prefix' => 'v1/common'], function ($api) {
        /** @var Dingo\Api\Routing\Router $api */
        $api->group(['middleware'=>'apiauth'],function($api){
            /** @var Dingo\Api\Routing\Router $api */
            $api->get('email','CommonController@email');
        });
    $api->post('send_email','CommonController@sendEmail');
    $api->get('get_captcha','CommonController@getCaptcha');
    $api->post('check_captcha','CommonController@checkCode');
    $api->post('send_sms','CommonController@sendSms');
    $api->get('get_country','CommonController@getCountry');
    });
});
