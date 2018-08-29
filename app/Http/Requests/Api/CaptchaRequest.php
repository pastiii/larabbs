<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CaptchaRequest
 * @package App\Http\Requests\Api
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/22
 * Time: 10:00
 */
class CaptchaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'captcha_key' => 'required|string',
            'captcha_code' => 'required|string',
        ];
    }

    /**
     * set attributes
     * @return array
     */
    public function attributes()
    {
        return [
            'captcha_key' => '图片验证码 key',
            'captcha_code' => '图片验证码',
        ];
    }
}
