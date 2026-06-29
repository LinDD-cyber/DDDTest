<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RegisterRequest extends ApiFormRequest
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
        $account = $this->input('account');
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);

        return [
            'account' => array_merge(
                ['required', 'string', 'unique:users,account'],
                $isEmail
                    ? ['email', 'max:150', 'unique:users,email']
                    : ['regex:/^09\d{8}$/', 'unique:users,phone']
            ),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'account.regex' => '帳號格式必須為正確的電子郵件或手機號碼。',
            'account.unique' => '此帳號已被註冊。',
        ];
    }
}
