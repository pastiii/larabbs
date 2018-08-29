<?php

namespace App\Support;

/**
 * Trait SaltTrait
 * @package App\Support
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/6/7
 * Time: 20:22
 */

trait SaltTrait
{
    /**
     * 密码salt
     * @return string
     */
    protected function getUnique()
    {
        return md5(uniqid((time().mt_rand(1000,9999))));
    }

    /**
     * 密码加密
     * @param $password
     * @param $unique
     * @return string
     */
    protected  function getPassword($password,$unique)
    {
        return hash('sha256', sha1($password).$unique);
    }


    /**
     * 验证密码
     * @param $userPassword
     * @param $DbPassword
     * @param $unique
     * @return bool
     */
    protected function checkPassword($userPassword,$DbPassword,$unique)
    {
        $userPassword = $this->getPassword($userPassword,$unique);
        if($DbPassword == $userPassword){
            return true;
        }
        return false;
    }
}