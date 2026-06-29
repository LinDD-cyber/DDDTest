<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use App\Models\CoachProfile;
use App\Models\FrontIdentity;
use App\Support\ServiceResult;
use App\Support\ApiMessageBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService
{
    protected ApiMessageBuilder $messageBuilder;

    public function __construct(ApiMessageBuilder $messageBuilder)
    {
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * Create a new student (No identities assigned yet).
     */
    public function createStudent(array $userData): User
    {
        return DB::transaction(function () use ($userData) {
            $user = User::create([
                'uuid' => Str::uuid(),
                'account' => $userData['account'],
                'email' => $userData['email'] ?? null,
                'phone' => $userData['phone'] ?? null,
                'password' => Hash::make($userData['password']),
                'status' => $userData['status'] ?? 1,
            ]);

            return $user;
        });
    }

    /**
     * Create or upgrade a user to a Member.
     */
    public function makeMember(User $user, array $profileData): void
    {
        DB::transaction(function () use ($user, $profileData) {
            // Assign member identity
            $memberIdentity = FrontIdentity::where('code', 'member')->firstOrFail();
            $user->frontIdentities()->syncWithoutDetaching([$memberIdentity->id]);

            // Create or update profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        });
    }

    /**
     * Create or upgrade a user to a Coach.
     * Note: A coach must also be a member.
     */
    public function makeCoach(User $user, array $coachProfileData, array $profileData = []): ServiceResult
    {
        try {
            DB::transaction(function () use ($user, $coachProfileData, $profileData) {
                // Ensure they are a member first
                if (!empty($profileData) || !$user->profile) {
                    $this->makeMember($user, $profileData);
                } else {
                    // Just assign member identity if not already
                    $memberIdentity = FrontIdentity::where('code', 'member')->firstOrFail();
                    $user->frontIdentities()->syncWithoutDetaching([$memberIdentity->id]);
                }

                // Assign coach identity
                $coachIdentity = FrontIdentity::where('code', 'coach')->firstOrFail();
                $user->frontIdentities()->syncWithoutDetaching([$coachIdentity->id]);

                // Create or update coach profile
                $user->coachProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $coachProfileData
                );
            });

            return ServiceResult::success(
                [$this->messageBuilder->build('update', 'member', 'success')],
                ['user' => $user->load('profile', 'coachProfile', 'frontIdentities')]
            );
        } catch (Exception $e) {
            return ServiceResult::fail(
                [$this->messageBuilder->build('update', 'member', 'fail'), $e->getMessage()],
                ['reason' => 'server_error']
            );
        }
    }

    /**
     * Assign backend role to user
     */
    public function assignRole(User $user, string $roleName): void
    {
        $user->assignRole($roleName);
    }

    /**
     * Assign venue data scope to user (appends without detaching)
     */
    public function assignVenueScope(User $user, array $venueIds): void
    {
        $user->venues()->syncWithoutDetaching($venueIds);
    }

    /**
     * Synchronize venue data scope (replaces existing with new array using venue_user pivot table)
     */
    public function syncVenueScope(User $currentUser, User $targetUser, array $venueIds): ServiceResult
    {
        try {
            // Check 1: Only System Admin can use this function
            if (!$currentUser->hasRole('System Admin')) {
                throw new Exception('只有系統管理員可以使用此功能。');
            }

            // Check 2: Can only bind venue permissions to backend users
            if ($targetUser->roles()->count() === 0) {
                throw new Exception('只能指派場館給後台人員。');
            }

            $targetUser->venues()->sync($venueIds);
            
            return ServiceResult::success(
                [$this->messageBuilder->build('update', 'user', 'success')],
                ['venues' => $targetUser->venues()->get()]
            );
        } catch (Exception $e) {
            $reason = str_contains($e->getMessage(), '只能') || str_contains($e->getMessage(), '系統') || str_contains($e->getMessage(), '權限') ? 'forbidden' : 'server_error';
            return ServiceResult::fail(
                [$e->getMessage()],
                ['reason' => $reason]
            );
        }
    }

    /**
     * Check if the current user has permission to manage the target roles and venues.
     * System Admin can manage anything.
     * Venue Manager can only manage Venue Staff and only for venues they manage.
     * Throws Exception if unauthorized.
     */
    private function authorizeBackendUserAction(User $currentUser, array $rolesToAssign, array $venueIdsToAssign): void
    {
        if ($currentUser->hasRole('System Admin')) {
            return; // System Admin has full control
        }

        // Must be a Venue Manager to reach here (as per backend_user.manage permission)
        // Venue Manager can ONLY assign 'Venue Staff' role, nothing else.
        $allowedRolesForManager = ['Venue Staff'];
        foreach ($rolesToAssign as $role) {
            if (!in_array($role, $allowedRolesForManager)) {
                throw new Exception('您只能建立或編輯「場館員工 (Venue Staff)」的角色。');
            }
        }

        // Venue Manager can ONLY assign venues they already manage
        $managerVenues = $currentUser->venues()->pluck('venues.id')->toArray();
        $unauthorizedVenues = array_diff($venueIdsToAssign, $managerVenues);
        
        if (!empty($unauthorizedVenues)) {
            throw new Exception('您只能指派您所管理的場館。');
        }
    }

    /**
     * Create a new backend admin user.
     */
    public function createBackendUser(User $currentUser, array $data): ServiceResult
    {
        try {
            $roles = $data['roles'];
            $venueIds = $data['venue_ids'] ?? [];

            // 1. Authorization Check
            $this->authorizeBackendUserAction($currentUser, $roles, $venueIds);

            $user = DB::transaction(function () use ($data, $roles, $venueIds) {
                // 2. Create the basic user
                $user = User::create([
                    'uuid' => Str::uuid(),
                    'account' => $data['account'],
                    'email' => $data['email'] ?? null,
                    'password' => Hash::make($data['password']),
                    'status' => 1,
                ]);

                // 3. Assign Spatie Roles
                $user->syncRoles($roles);

                // 4. Assign Venues
                if (!empty($venueIds)) {
                    $user->venues()->sync($venueIds);
                }

                return $user;
            });

            return ServiceResult::success(
                [$this->messageBuilder->build('create', 'user', 'success')],
                ['user' => $user->load('roles', 'venues')]
            );
        } catch (Exception $e) {
            // For custom authorization exceptions, we can treat it as a forbidden action
            $reason = str_contains($e->getMessage(), '您只能') ? 'forbidden' : 'server_error';
            return ServiceResult::fail(
                [$e->getMessage()],
                ['reason' => $reason]
            );
        }
    }

    /**
     * Update an existing backend admin user.
     */
    public function updateBackendUser(User $currentUser, User $targetUser, array $data): ServiceResult
    {
        try {
            // Check if the target user is a System Admin, preventing Venue Managers from editing them.
            if (!$currentUser->hasRole('System Admin') && $targetUser->hasRole('System Admin')) {
                throw new Exception('您無法編輯系統管理員。');
            }

            $roles = $data['roles'] ?? $targetUser->roles->pluck('name')->toArray();
            $venueIds = array_key_exists('venue_ids', $data) ? $data['venue_ids'] : $targetUser->venues->pluck('id')->toArray();

            // 1. Authorization Check
            $this->authorizeBackendUserAction($currentUser, $roles, $venueIds);

            DB::transaction(function () use ($targetUser, $data, $roles, $venueIds) {
                // 2. Update basic user data
                $updateData = [];
                if (isset($data['account'])) $updateData['account'] = $data['account'];
                if (isset($data['email'])) $updateData['email'] = $data['email'];
                if (!empty($data['password'])) $updateData['password'] = Hash::make($data['password']);

                if (!empty($updateData)) {
                    $targetUser->update($updateData);
                }

                // 3. Sync Roles
                if (isset($data['roles'])) {
                    $targetUser->syncRoles($roles);
                }

                // 4. Sync Venues
                if (array_key_exists('venue_ids', $data)) {
                    $targetUser->venues()->sync($venueIds);
                }
            });

            return ServiceResult::success(
                [$this->messageBuilder->build('update', 'user', 'success')],
                ['user' => $targetUser->fresh('roles', 'venues')]
            );
        } catch (Exception $e) {
            $reason = str_contains($e->getMessage(), '您無法') || str_contains($e->getMessage(), '您只能') ? 'forbidden' : 'server_error';
            return ServiceResult::fail(
                [$e->getMessage()],
                ['reason' => $reason]
            );
        }
    }

    /**
     * Delete a backend admin user.
     */
    public function deleteBackendUser(User $currentUser, User $targetUser): ServiceResult
    {
        try {
            // Venue Manager can only delete Venue Staff
            if (!$currentUser->hasRole('System Admin')) {
                if ($targetUser->hasRole('System Admin') || $targetUser->hasRole('Venue Manager')) {
                    throw new Exception('權限不足：您只能刪除場館員工。');
                }
            }

            if ($currentUser->id === $targetUser->id) {
                throw new Exception('您無法刪除自己的帳號。');
            }

            $targetUser->delete(); // Uses SoftDeletes

            return ServiceResult::success(
                [$this->messageBuilder->build('delete', 'user', 'success')],
                []
            );
        } catch (Exception $e) {
            $reason = str_contains($e->getMessage(), '權限不足') || str_contains($e->getMessage(), '自己') ? 'forbidden' : 'server_error';
            return ServiceResult::fail(
                [$e->getMessage()],
                ['reason' => $reason]
            );
        }
    }

    /**
     * Get paginated list of frontend users (Users without Spatie backend roles)
     */
    public function getFrontendUsers(int $perPage = 15): ServiceResult
    {
        $users = User::whereDoesntHave('roles')
            ->with(['profile', 'coachProfile', 'frontIdentities'])
            ->paginate($perPage);

        return ServiceResult::success(
            [$this->messageBuilder->build('query', 'user', 'success')],
            ['paginator' => $users]
        );
    }

    /**
     * Get paginated list of backend users (Users with at least one Spatie backend role)
     */
    public function getBackendUsers(int $perPage = 15): ServiceResult
    {
        $users = User::whereHas('roles')
            ->with(['profile', 'roles', 'venues']) // Also load venues for data scope check
            ->paginate($perPage);

        return ServiceResult::success(
            [$this->messageBuilder->build('query', 'user', 'success')],
            ['paginator' => $users]
        );
    }
}
