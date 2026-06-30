<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FrontIdentity extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
        'is_enabled',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_enabled' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_front_identities')->withTimestamps();
    }
}
