<?php

namespace App\Support;

use Illuminate\Support\Facades\Redis;

/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/8/2
 * Time: 14:23
 */

trait SendTrait
{
    /**
     * 将phone code存入redis
     * @param $email_data
     * @param $data
     * @param $phone_info
     * @return array|bool
     */
    public function storageCode($email_data, $data, $phone_info)
    {
        if ($email_data['code'] == 200 && $data['type'] == 2) {
            return true;
        }

        if ($email_data['code'] == 200 && $data['type'] == 4) {
            return true;
        }

        if ($email_data['code'] == 200) {
            $key       = str_random(15);
            $redis_key = env('PC_PHONE') .$phone_info['phone_idd']. $phone_info['phone_number'] . "_" . $key;
            //将email code存入redis
            $time = env('SEND_PHONE_TIME') > 0 ? env('SEND_PHONE_TIME') * 60 : 10 * 60;
            Redis::setex($redis_key, $time, $data['code']);
            return ['verification_key' => $key, 'code' => 200];
        }


        return ['code' => 403];
    }

    /**
     * 将email code存入redis
     * @param $email_data
     * @param $email
     * @param $data
     * @return array
     */
    public function storageEmail($email_data, $email, $data)
    {

        if ($email_data['code'] == 200) {
            $key       = str_random(15);
            $redis_key = env('PC_EMAIL') . $email . "_" . $key;
            //将email code存入redis
            $time = env('SEND_EMAIL_TIME') > 0 ? env('SEND_EMAIL_TIME') * 60 : 10 * 60;
            redis::setex($redis_key, $time, $data['code']);
            return ['email_key' => $key, 'code' => 200];
        }

        return ['code' => 403];
    }
}