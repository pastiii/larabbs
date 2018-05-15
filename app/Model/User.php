<?php

namespace App\Model;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     * 对表单提交的数据进行过滤,只有下列的字段才能够正常更新
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     * 需要对用户的敏感信息在用户实例通过数组或json显示时进行隐藏
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    // 指定数据交互的数据库为users
    protected $table = 'users';

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }
}
