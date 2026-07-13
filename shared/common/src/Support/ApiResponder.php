<?php
 
namespace Shared\Support;
 
use Illuminate\Http\JsonResponse;
 
class ApiResponder
{
    /**
     * 成功回應（四欄固定格式）
     *
     * @param array       $messages  繁中訊息陣列
     * @param mixed       $data      業務物件或陣列
     * @param int|null    $httpCode  若為 null 則使用 config apiResponse.http_code.ok
     */
    public static function success(array $messages, mixed $data = [], ?int $httpCode = null): JsonResponse
    {
        $code = $httpCode ?? (int) config('apiResponse.http_code.ok');
 
        return response()->json([
            'status'   => (int) config('apiResponse.status.success'),
            'data'     => $data,
            'messages' => $messages,
            'error'    => null,
        ], $code);
    }
 
    /**
     * 失敗回應（四欄固定格式，data 強制為 []）
     *
     * @param array       $messages  繁中訊息陣列
     * @param array       $error     ['code' => string, 'fields' => array]
     * @param int|null    $httpCode  若為 null 則使用 config apiResponse.http_code.bad_request
     */
    public static function fail(array $messages, array $error, ?int $httpCode = null): JsonResponse
    {
        $code = $httpCode ?? (int) config('apiResponse.http_code.bad_request');
 
        return response()->json([
            'status'   => (int) config('apiResponse.status.fail'),
            'data'     => [],
            'messages' => $messages,
            'error'    => [
                'code'   => $error['code'] ?? 'bad_request',
                'fields' => $error['fields'] ?? [],
            ],
        ], $code);
    }
}
