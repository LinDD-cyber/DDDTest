<?php
 
return [
 
    'status' => [
        'success' => 0,
        'fail'    => -1,
    ],
 
    'http_code' => [
        'ok'                   => 200,
        'bad_request'          => 400,
        'unauthorized'         => 401,
        'forbidden'            => 403,
        'not_found'            => 404,
        'conflict'             => 409,
        'unprocessable_entity' => 422,
        'too_many_requests'    => 429,
        'server_error'         => 500,
        'service_unavailable'  => 503,
    ],
 
    /*
     * reason_map：Handler / Service 透過 ServiceResult::fail(['reason' => 'xxx'])
     * 攜帶業務原因，Controller（使用 MapsServiceResult）依此映射 HTTP 碼與 error.code。
     */
    'reason_map' => [
        'not_found'              => ['http' => 'not_found',   'code' => 'not_found'],
        'duplicate_registration' => ['http' => 'conflict',    'code' => 'duplicate_registration'],
        'registration_full'      => ['http' => 'conflict',    'code' => 'registration_full'],
        'unauthorized'           => ['http' => 'unauthorized','code' => 'unauthorized'],
        'forbidden'              => ['http' => 'forbidden',   'code' => 'forbidden'],
        'validation_failed'      => ['http' => 'unprocessable_entity', 'code' => 'validation_failed'],
        'batch_count_mismatch'   => ['http' => 'unprocessable_entity', 'code' => 'batch_count_mismatch'],
        'batch_date_group_mismatch' => ['http' => 'unprocessable_entity', 'code' => 'batch_date_group_mismatch'],
        'schedule_conflict'      => ['http' => 'conflict',    'code' => 'schedule_conflict'],
        'invalid_credentials'    => ['http' => 'unauthorized','code' => 'invalid_credentials'],
        'account_disabled'       => ['http' => 'forbidden',   'code' => 'account_disabled'],
        'server_error'           => ['http' => 'server_error', 'code' => 'server_error'],
        'insufficient_permissions'=> ['http' => 'forbidden',   'code' => 'insufficient_permissions'],
    ],
 
];
