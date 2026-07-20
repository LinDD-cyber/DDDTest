<?php

namespace App\Domains\Example\Models;

use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
    protected $fillable = ['name', 'description', 'user_id'];

    public static function getMockExamples()
    {
        return [
            ['id' => 1, 'name' => 'Example A', 'description' => 'Description of A', 'user_id' => 1],
            ['id' => 2, 'name' => 'Example B', 'description' => 'Description of B', 'user_id' => 2],
        ];
    }
}
