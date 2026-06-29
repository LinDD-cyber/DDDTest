<?php

namespace App\Support;

use InvalidArgumentException;

class ApiMessageBuilder
{
    /**
     * 取單句訊息（config apiMessage.direct.{key}）
     *
     * @throws InvalidArgumentException  key 不存在時拋出
     */
    public function direct(string $key): string
    {
        $message = config("apiMessage.direct.{$key}");

        if ($message === null) {
            throw new InvalidArgumentException("ApiMessage key [{$key}] is not defined in config/apiMessage.php.");
        }

        return $message;
    }

    /**
     * 依 actions × subjects × results + template 組句
     * 例：build('create', 'order', 'success') → 「建立訂單成功」
     *
     * @throws InvalidArgumentException  任一 key 不存在時拋出
     */
    public function build(string $action, string $subject, string $result): string
    {
        $actionText  = config("apiMessage.actions.{$action}");
        $subjectText = config("apiMessage.subjects.{$subject}");
        $resultText  = config("apiMessage.results.{$result}");
        $template    = config('apiMessage.template', ':action:subject:result');

        if ($actionText === null) {
            throw new InvalidArgumentException("ApiMessage action [{$action}] is not defined.");
        }
        if ($subjectText === null) {
            throw new InvalidArgumentException("ApiMessage subject [{$subject}] is not defined.");
        }
        if ($resultText === null) {
            throw new InvalidArgumentException("ApiMessage result [{$result}] is not defined.");
        }

        return str_replace(
            [':action', ':subject', ':result'],
            [$actionText, $subjectText, $resultText],
            $template,
        );
    }
}
