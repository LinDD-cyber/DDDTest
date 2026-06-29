<?php

namespace App\Support;

class ServiceResult
{
    public readonly int $status;
    public readonly array $messages;
    public readonly array $data;

    private function __construct(int $status, array $messages, array $data)
    {
        $this->status   = $status;
        $this->messages = $messages;
        $this->data     = $data;
    }

    public static function success(array $messages, array $data = []): static
    {
        return new static(
            (int) config('apiResponse.status.success'),
            $messages,
            $data,
        );
    }

    /**
     * @param array $messages  繁中訊息供前端顯示
     * @param array $data      內部攜帶 ['reason' => 'not_found', ...] 供 Controller 映射；
     *                         不直接成為 JSON response 的 data 欄位
     */
    public static function fail(array $messages, array $data = []): static
    {
        return new static(
            (int) config('apiResponse.status.fail'),
            $messages,
            $data,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === (int) config('apiResponse.status.success');
    }
}
