<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachProfile extends Model
{
    protected $fillable = [
        'user_id',
        'introduction',
        'experience',
        'license',
        'bank_name',
        'bank_code',
        'bank_account',
        'commission_type',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'commission_type' => 'integer',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
