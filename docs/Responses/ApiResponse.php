<?php
/*
 * 統一出口
 * 200 成功
 * 400 失敗
 *
 * */
namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, array $message = ['Success'], int $code = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(array $message = ['Error'], int $code = 400, $data = null)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
