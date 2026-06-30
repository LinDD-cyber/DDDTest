<?php

namespace App\Http\Requests;

use App\Support\ApiResponder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 新版 FormRequest 基底類別：僅負責驗證失敗時的統一回應格式。
 *
 * 驗證文案由 Laravel Validator 依 locale 從 lang/zh_TW/validation.php（及子類 rules）
 * 產生，此類不另定義訊息內容。
 *
 * 回應：HTTP 422、error.code = validation_failed、messages 為各欄錯誤字串陣列。
 */
abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): never
    {
        $errors   = $validator->errors()->toArray();
        $messages = $validator->errors()->all();

        throw new HttpResponseException(
            ApiResponder::fail($messages, [
                'code'   => 'validation_failed',
                'fields' => $errors,
            ], (int) config('apiResponse.http_code.unprocessable_entity'))
        );
    }
}
