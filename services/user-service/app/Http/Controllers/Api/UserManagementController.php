<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBackendUserRequest;
use App\Http\Requests\Admin\UpdateBackendUserRequest;
use App\Http\Requests\Admin\UpdateUserVenuesRequest;
use App\Http\Requests\Admin\UpgradeToCoachRequest;
use App\Domains\User\Models\User;
use App\Domains\User\Services\UserService;
use Shared\Traits\MapsServiceResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserManagementController extends Controller
{
    use MapsServiceResult;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get paginated list of frontend users
     */
    public function getFrontendUsers(): JsonResponse
    {
        return $this->respond($this->userService->getFrontendUsers());
    }

    /**
     * Get paginated list of backend users
     */
    public function getBackendUsers(): JsonResponse
    {
        return $this->respond($this->userService->getBackendUsers());
    }

    /**
     * Sync data scope (venues) for a specific user
     */
    public function syncUserVenues(User $user, UpdateUserVenuesRequest $request): JsonResponse
    {
        $venueIds = $request->validated('venue_ids');

        return $this->respond($this->userService->syncVenueScope($request->user(), $user, $venueIds));
    }

    /**
     * Upgrade a frontend member/student to a coach
     */
    public function upgradeToCoach(User $user, UpgradeToCoachRequest $request): JsonResponse
    {
        $data = $request->validated();

        $coachProfileData = [
            'introduction'    => $data['introduction'] ?? null,
            'experience'      => $data['experience'] ?? null,
            'license'         => $data['license'] ?? null,
            'bank_name'       => $data['bank_name'] ?? null,
            'bank_code'       => $data['bank_code'] ?? null,
            'bank_account'    => $data['bank_account'] ?? null,
            'commission_type' => $data['commission_type'] ?? 1,
            'commission_rate' => $data['commission_rate'] ?? 0.00,
        ];

        // If they are upgrading but lack a base profile, extract the provided name and phone
        $profileData = [];
        if (isset($data['name'])) {
            $profileData['name'] = $data['name'];
        }
        if (isset($data['phone'])) {
            $profileData['phone'] = $data['phone'];
        }

        return $this->respond($this->userService->makeCoach($user, $coachProfileData, $profileData));
    }

    /**
     * Create a new backend admin user
     */
    public function createBackendUser(CreateBackendUserRequest $request): JsonResponse
    {
        return $this->respond($this->userService->createBackendUser($request->user(), $request->validated()));
    }

    /**
     * Update an existing backend admin user
     */
    public function updateBackendUser(User $user, UpdateBackendUserRequest $request): JsonResponse
    {
        return $this->respond($this->userService->updateBackendUser($request->user(), $user, $request->validated()));
    }

    /**
     * Delete a backend admin user
     */
    public function deleteBackendUser(User $user, Request $request): JsonResponse
    {
        return $this->respond($this->userService->deleteBackendUser($request->user(), $user));
    }
}
