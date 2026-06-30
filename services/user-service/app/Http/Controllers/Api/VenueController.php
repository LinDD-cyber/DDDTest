<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateVenueRequest;
use App\Domains\Venue\Services\VenueService;
use App\Traits\MapsServiceResult;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    use MapsServiceResult;

    protected VenueService $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * Create a new venue (System Admin Only)
     */
    public function store(CreateVenueRequest $request): JsonResponse
    {
        return $this->respond($this->venueService->createVenue($request->validated()));
    }
}
