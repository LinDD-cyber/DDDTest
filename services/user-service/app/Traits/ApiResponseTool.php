<?php
namespace App\Traits;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ApiResponseTool
{

    protected  int $status;
    protected  int $httpCode;
    public function __construct()
    {
        $this->httpCode = config('status.http_code.success');
        $this->status =config('status.status.success');
    }


    /**
     * 生成標準的 API 回應
     *
     * 此函式回傳 JSON 格式的 API 回應，包含狀態碼、訊息與數據內容。
     * 用於統一 API 的回應格式，確保前後端數據結構一致。
     *
     * @param integer $status   對照config/status.php(例如 0 表示成功，其他為錯誤代碼)
     * @param array $messages   訊息陣列，可包含多條錯誤或成功訊息
     * @param mixed $data       API 回應的主要內容，可為陣列或物件
     * @param integer $httpCode 回傳的HTTP狀態碼，預設為 200
     *
     * @return JsonResponse
     *
     */
    protected function generateApiResponse(int $httpCode = null, int $status = null, mixed $messages = [], mixed $data = []): JsonResponse
    {
        $httpCode = $httpCode ?? config('status.httpCode.success');
        $status = $status ?? config('status.businessCode.success');

        return response()->json([
            'status'   => $status,
            'messages' => $messages ?: [$this->defaultMessagesByHttpCode[$httpCode] ?? '未知錯誤'],
            ...($httpCode === 200 ? ['data' => $data] : []),
        ], $httpCode);
    }
    /**
     * try-catch 需要的錯誤回傳
     */
    protected function catchError(Throwable $exception, array $messages = []): JsonResponse
    {
        $httpCode = config('status.http_code.system_error');
        $status = config('status.status.fail');

        Log::error($exception->getMessage());

        return $this->generateApiResponse($httpCode, $status, $messages);
    }

    /**
     * 預設訊息
     * @var array|string[]
     */
    private array $defaultMessagesByHttpCode = [
        200 => '操作成功',
        400 => '驗證錯誤',
        401 => '登入失效，請重新再登入。',
        403 => '權限不足',
        404 => '系統錯誤'
    ];
}
