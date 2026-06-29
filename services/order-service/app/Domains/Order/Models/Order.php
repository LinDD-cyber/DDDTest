<?php

namespace App\Domains\Order\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'course_id', 'amount', 'status'];

    public static function getMockOrders()
    {
        return [
            ['id' => 101, 'user_id' => 1, 'course_id' => 201, 'amount' => 1500, 'status' => 'paid'],
            ['id' => 102, 'user_id' => 2, 'course_id' => 202, 'amount' => 2400, 'status' => 'pending'],
        ];
    }
}
