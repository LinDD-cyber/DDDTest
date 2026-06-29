<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class CreateBackendUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account'  => 'required|string|max:50|unique:users,account',
            'password' => 'required|string|min:8|confirmed',
            'email'    => 'nullable|email|max:150|unique:users,email',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'exists:roles,name', // Must be valid Spatie roles
            'venue_ids'   => 'nullable|array',
            'venue_ids.*' => 'exists:venues,id',
        ];
    }
}
