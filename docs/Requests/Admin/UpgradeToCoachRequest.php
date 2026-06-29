<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiFormRequest;

class UpgradeToCoachRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'introduction'    => 'nullable|string',
            'experience'      => 'nullable|string',
            'license'         => 'nullable|string',
            'bank_name'       => 'nullable|string|max:100',
            'bank_code'       => 'nullable|string|max:20',
            'bank_account'    => 'nullable|string|max:50',
            'commission_type' => 'nullable|integer|in:1,2',
            'commission_rate' => 'nullable|numeric|between:0,100',
            // Basic profile info in case they are completely new students upgrading to coach
            'name'            => 'required_without:profile|string|max:100',
            'phone'           => 'nullable|string|max:20',
        ];
    }
}
