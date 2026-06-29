<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'gender',
        'birthday',
        'phone',
        'avatar',
        'address',
        'emergency_contact',
        'emergency_phone',
    ];

    protected function casts(): array
    {
        return [
            'gender' => 'integer',
            'birthday' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
