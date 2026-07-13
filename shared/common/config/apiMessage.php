<?php
 
return [
 
    /*
     * direct.{key}：單句訊息，由 ApiMessageBuilder::direct($key) 取出。
     */
    'direct' => [
        'validation_failed'      => '輸入資料有誤，請檢查各欄位。',
        'bad_request'            => '請求格式錯誤。',
        'not_found'              => '查無此資料。',
        'conflict'               => '操作衝突，請稍後再試。',
        'duplicate_registration' => '此上課人已報名過相同的課程梯次。',
        'registration_full'      => '抱歉，部分梯次已經額滿囉！',
        'unauthorized'           => '請先登入後再操作。',
        'forbidden'              => '您沒有執行此操作的權限。',
        'server_error'           => '系統發生錯誤，請稍後再試。',
        // Order Adjust Batch
        'batch_count_mismatch'   => '編輯前後的課程數目必須相同。',
        'batch_date_group_mismatch' => '相同日期的課程梯次必須同時修改至相同的日期，不可拆分。',
        'schedule_conflict'      => '選擇的課程梯次時間有衝突，請重新確認。',
        // Auth
        'login_success'          => '登入成功。',
        'logout_success'         => '登出成功。',
        'invalid_credentials'    => '帳號或密碼錯誤。',
        'account_disabled'       => '帳號已停用，請聯繫管理員。',
        'insufficient_permissions' => '權限不足，無法登入後台。',
        'super_admin_only'         => '僅總管理員可執行此操作。',
        'invalid_otp'            => '驗證碼錯誤或已過期。',
        'otp_sent'               => '驗證碼已傳送成功。',
    ],
 
    /*
     * 組句用（actions × subjects × results + template）
     * 使用方式：ApiMessageBuilder::build('create', 'order', 'success')
     *           → 「建立訂單成功」
     */
    'template' => ':action:subject:result',
 
    'actions' => [
        'create' => '建立',
        'update' => '更新',
        'delete' => '刪除',
        'query'  => '查詢',
        'upload' => '上傳',
        'cancel' => '取消',
        'adjust' => '調整',
        'export' => '匯出',
    ],
 
    'subjects' => [
        'order'      => '訂單',
        'course'     => '課程',
        'enrollment' => '報名紀錄',
        'member'     => '上課人資料',
        'user'       => '購買人資料',
        'image'      => '圖片',
        'batch'      => '梯次',
        'payment'    => '付款狀態',
    ],
 
    'results' => [
        'success' => '成功',
        'fail'    => '失敗',
    ],
 
];
