<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UserServiceProvider
 * @package App\Providers
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/23
 * Time: 16:12
 */
class VerificationCodeRequest extends FormRequest
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
            'phone_number'  => 'nullable|regex:/^1[34578]\d{9}$/',
            'phone_idd'     => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'phone_number'  => '手机号码',
            'phone_idd'     => '国际码',
        ];
    }
}
