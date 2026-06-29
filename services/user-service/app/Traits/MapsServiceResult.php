<?php

namespace App\Traits;

use App\Support\ApiResponder;
use App\Support\ServiceResult;
use Illuminate\Http\JsonResponse;

/**
 * 供新版 Controller 使用：依 ServiceResult 內的 reason 自動映射
 * HTTP 狀態碼與 error.code，避免每個 Controller 重複 if-else。
 *
 * 映射規則來自 config/apiResponse.php → reason_map。
 * 無對應 reason 時預設回 bad_request(400)。
 */
trait MapsServiceResult
{
    protected function respond(ServiceResult $result, string $defaultErrorCode = 'bad_request'): JsonResponse
    {
        if ($result->isSuccess()) {
            return ApiResponder::success($result->messages, $result->data);
        }

        $reason     = $result->data['reason'] ?? null;
        $reasonMap  = config('apiResponse.reason_map', []);

        if ($reason && isset($reasonMap[$reason])) {
            $httpKey   = $reasonMap[$reason]['http'];
            $errorCode = $reasonMap[$reason]['code'];
        } else {
            $httpKey   = 'bad_request';
            $errorCode = $defaultErrorCode;
        }

        $httpCode = (int) config("apiResponse.http_code.{$httpKey}",
            config('apiResponse.http_code.bad_request')
        );

        return ApiResponder::fail($result->messages, [
            'code'   => $errorCode,
            'fields' => $result->data,
        ], $httpCode);
    }
}
