<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFrontIdentity extends Model
{
    protected $table = 'user_front_identities';

    protected $fillable = [
        'user_id',
        'front_identity_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function frontIdentity(): BelongsTo
    {
        return $this->belongsTo(FrontIdentity::class);
    }
}
