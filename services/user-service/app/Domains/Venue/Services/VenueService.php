<?php

namespace App\Domains\Venue\Services;

use App\Domains\Venue\Models\Venue;
use Shared\Support\ServiceResult;
use App\Support\ApiMessageBuilder;
use Exception;

class VenueService
{
    protected ApiMessageBuilder $messageBuilder;

    public function __construct(ApiMessageBuilder $messageBuilder)
    {
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * Create a new venue.
     */
    public function createVenue(array $data): ServiceResult
    {
        try {
            $venue = Venue::create([
                'name'    => $data['name'],
                'phone'   => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status'  => $data['status'] ?? 1,
            ]);

            return ServiceResult::success(
                ['新增場館成功'],
                ['venue' => $venue]
            );
        } catch (Exception $e) {
            return ServiceResult::fail(
                ['新增場館失敗', $e->getMessage()],
                ['reason' => 'server_error']
            );
        }
    }
}
