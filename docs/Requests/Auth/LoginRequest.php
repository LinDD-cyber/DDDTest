<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account'  => 'required|string',
            'password' => 'required_without:code|string',
            'code'     => 'required_without:password|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required_without' => '使用密碼登入時，密碼欄位必填。',
            'code.required_without'     => '使用驗證碼登入時，驗證碼欄位必填。',
            'code.size'                 => '驗證碼必須為 6 位數。',
        ];
    }
}
