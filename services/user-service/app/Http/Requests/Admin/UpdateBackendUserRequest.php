<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class UpdateBackendUserRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Account and Email ignore the current user ID. 
        // We get the user ID from the route.
        $userId = $this->route('user')->id ?? null;

        return [
            'account'  => 'sometimes|required|string|max:50|unique:users,account,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'email'    => 'nullable|email|max:150|unique:users,email,' . $userId,
            'roles'    => 'sometimes|required|array|min:1',
            'roles.*'  => 'exists:roles,name',
            'venue_ids'   => 'nullable|array',
            'venue_ids.*' => 'exists:venues,id',
        ];
    }
}
