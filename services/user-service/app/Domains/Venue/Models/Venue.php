<?php

namespace App\Domains\Venue\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Domains\User\Models\User;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'venue_user')->withTimestamps();
    }
}
