<?php

namespace App\Services;

use App\Models\Venue;
use App\Support\ServiceResult;
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

            // Assuming we don't have a specific 'venue' subject in the message builder yet,
            // we'll use a direct success response format if it's missing, but we'll try to build it.
            // If 'venue' subject doesn't exist in config/apiMessage.php, this will throw an exception.
            // But we know 'venue' isn't in subjects currently. Let's return a basic ServiceResult.
            
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
