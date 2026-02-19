<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\BusinessUser;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class BusinessUserController extends BaseController
{
    /**
     * List business users (staff/team members)
     * GET /api/v1/business/users
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        if (!$business) {
            return $this->error('Business not set up. Please complete business setup first.', 'NOT_SETUP', 404);
        }

        $query = $business->users()
            ->with(['user', 'branch'])
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->when($request->has('role'), fn ($q) => $q->where('role', $request->get('role')))
            ->when($request->has('branch_id'), fn ($q) => $q->where('branch_id', $request->get('branch_id')));

        $users = $query->orderBy('role')->orderBy('created_at', 'desc')->get();

        $formatted = $users->map(fn ($bu) => $this->formatBusinessUser($bu));

        return $this->success([
            'users' => $formatted,
            'summary' => [
                'total' => $users->count(),
                'owners' => $users->where('role', BusinessUser::ROLE_OWNER)->count(),
                'admins' => $users->where('role', BusinessUser::ROLE_ADMIN)->count(),
                'staff' => $users->where('role', BusinessUser::ROLE_STAFF)->count(),
            ],
        ]);
    }

    /**
     * Invite a new user to the business
     * POST /api/v1/business/users
     * 
     * Creates a new user account OR links existing user to business
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,staff',
            'branch_id' => 'nullable|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();

        if (!$business) {
            return $this->error('Business not set up. Please complete business setup first.', 'NOT_SETUP', 404);
        }

        $data = $validator->validated();

        // Check if branch belongs to business
        if (!empty($data['branch_id'])) {
            $branch = Branch::find($data['branch_id']);
            if (!$branch || $branch->business_id !== $business->id) {
                return $this->error('Invalid branch', 'INVALID_BRANCH', 400);
            }
        }

        // Check if user already exists
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            // Check if already a member of this business
            $existingMembership = BusinessUser::where('business_id', $business->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMembership) {
                return $this->error('User is already a member of this business', 'ALREADY_MEMBER', 400);
            }

            // Check if user is already a business owner elsewhere
            if ($existingUser->isBusiness() && $existingUser->business) {
                return $this->error('User already owns another business', 'OWNS_BUSINESS', 400);
            }
        }

        DB::beginTransaction();
        try {
            $tempPassword = null;
            $isNewUser = false;

            // Create new user if doesn't exist
            if (!$existingUser) {
                $tempPassword = Str::random(12);
                $existingUser = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($tempPassword),
                    'user_type' => User::TYPE_BUSINESS,
                    'is_active' => true,
                ]);
                $isNewUser = true;
            }

            // Create business user relationship
            $businessUser = BusinessUser::create([
                'business_id' => $business->id,
                'user_id' => $existingUser->id,
                'role' => $data['role'],
                'branch_id' => $data['branch_id'] ?? null,
                'permissions' => $data['permissions'] ?? $this->getDefaultPermissions($data['role']),
                'is_active' => true,
            ]);

            $businessUser->load(['user', 'branch']);

            DB::commit();

            // Send invitation email
            NotificationService::sendStaffInvitation(
                $existingUser,
                $business,
                $data['role'],
                $businessUser->branch?->name,
                $isNewUser ? $tempPassword : null
            );

            return $this->success(
                $this->formatBusinessUser($businessUser),
                'User invited successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to invite user: ' . $e->getMessage(), 'INVITE_FAILED', 500);
        }
    }

    /**
     * Show business user details
     * GET /api/v1/business/users/{businessUser}
     */
    public function show(BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        $businessUser->load(['user', 'branch']);

        return $this->success($this->formatBusinessUser($businessUser));
    }

    /**
     * Update business user (role, permissions, branch)
     * PUT /api/v1/business/users/{businessUser}
     */
    public function update(Request $request, BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        // Cannot modify owner
        if ($businessUser->isOwner()) {
            return $this->error('Cannot modify business owner', 'CANNOT_MODIFY_OWNER', 400);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:admin,staff',
            'branch_id' => 'sometimes|nullable|exists:branches,id',
            'permissions' => 'sometimes|nullable|array',
            'permissions.*' => 'boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        // Check if branch belongs to business
        if (isset($data['branch_id']) && $data['branch_id']) {
            $branch = Branch::find($data['branch_id']);
            if (!$branch || $branch->business_id !== $this->business()->id) {
                return $this->error('Invalid branch', 'INVALID_BRANCH', 400);
            }
        }

        $businessUser->update($data);
        $businessUser->load(['user', 'branch']);

        return $this->success($this->formatBusinessUser($businessUser), 'User updated successfully');
    }

    /**
     * Remove user from business
     * DELETE /api/v1/business/users/{businessUser}
     */
    public function destroy(BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        // Cannot remove owner
        if ($businessUser->isOwner()) {
            return $this->error('Cannot remove business owner', 'CANNOT_REMOVE_OWNER', 400);
        }

        // Cannot remove yourself
        if ($businessUser->user_id === $this->user()->id) {
            return $this->error('Cannot remove yourself from the business', 'CANNOT_REMOVE_SELF', 400);
        }

        $businessUser->delete();

        return $this->success(null, 'User removed from business successfully');
    }

    /**
     * Deactivate business user
     * POST /api/v1/business/users/{businessUser}/deactivate
     */
    public function deactivate(BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        if ($businessUser->isOwner()) {
            return $this->error('Cannot deactivate business owner', 'CANNOT_DEACTIVATE_OWNER', 400);
        }

        $businessUser->update(['is_active' => false]);
        $businessUser->load(['user', 'branch']);

        return $this->success($this->formatBusinessUser($businessUser), 'User deactivated successfully');
    }

    /**
     * Activate business user
     * POST /api/v1/business/users/{businessUser}/activate
     */
    public function activate(BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        $businessUser->update(['is_active' => true]);
        $businessUser->load(['user', 'branch']);

        return $this->success($this->formatBusinessUser($businessUser), 'User activated successfully');
    }

    /**
     * Resend invitation email to staff user
     * POST /api/v1/business/users/{businessUser}/resend-invitation
     */
    public function resendInvitation(BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        // Cannot resend to owner
        if ($businessUser->isOwner()) {
            return $this->error('Cannot resend invitation to business owner', 'CANNOT_RESEND_TO_OWNER', 400);
        }

        $user = $businessUser->user;
        $business = $this->business();

        if (!$user) {
            return $this->error('User not found', 'USER_NOT_FOUND', 404);
        }

        // Generate new temporary password
        $tempPassword = Str::random(12);
        $user->update(['password' => Hash::make($tempPassword)]);

        // Send invitation email
        NotificationService::sendStaffInvitation(
            $user,
            $business,
            $businessUser->role,
            $businessUser->branch?->name,
            $tempPassword
        );

        return $this->success([
            'email' => $user->email,
            'resent_at' => now()->toIso8601String(),
        ], 'Invitation resent successfully');
    }

    /**
     * Reset staff user password (for owner/admin to reset staff password)
     * POST /api/v1/business/users/{businessUser}/reset-password
     */
    public function resetPassword(Request $request, BusinessUser $businessUser): JsonResponse
    {
        $this->authorizeBusinessUser($businessUser);

        // Cannot reset owner password
        if ($businessUser->isOwner()) {
            return $this->error('Cannot reset business owner password', 'CANNOT_RESET_OWNER', 400);
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'sometimes|string|min:8|max:100',
            'send_email' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        $user = $businessUser->user;
        $business = $this->business();

        // Generate or use provided password
        $newPassword = $data['new_password'] ?? Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        // Optionally send email with new password
        $sendEmail = $data['send_email'] ?? true;
        if ($sendEmail) {
            NotificationService::sendPasswordResetNotification(
                $user,
                $newPassword,
                $business->name
            );
        }

        return $this->success([
            'user_id' => $user->id,
            'email' => $user->email,
            'password_reset_at' => now()->toIso8601String(),
            'email_sent' => $sendEmail,
            'temporary_password' => $sendEmail ? null : $newPassword,
        ], 'Password reset successfully');
    }

    /**
     * Get available permissions list
     * GET /api/v1/business/users/permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = [
            'customers' => [
                'name' => 'Customers',
                'description' => 'View and manage customers (receivables)',
            ],
            'vendors' => [
                'name' => 'Vendors',
                'description' => 'View and manage vendors (payables)',
            ],
            'income' => [
                'name' => 'Income',
                'description' => 'Record income transactions',
            ],
            'expense' => [
                'name' => 'Expenses',
                'description' => 'Record expense transactions',
            ],
            'stock' => [
                'name' => 'Stock',
                'description' => 'Manage stock/inventory',
            ],
            'accounts' => [
                'name' => 'Accounts',
                'description' => 'View and manage accounts',
            ],
            'reports' => [
                'name' => 'Reports',
                'description' => 'View profit & loss and other reports',
            ],
        ];

        return $this->success([
            'permissions' => $permissions,
            'roles' => [
                BusinessUser::ROLE_OWNER => [
                    'name' => 'Owner',
                    'description' => 'Full access to everything. Cannot be modified.',
                ],
                BusinessUser::ROLE_ADMIN => [
                    'name' => 'Admin',
                    'description' => 'Full access to all features. Can manage staff.',
                ],
                BusinessUser::ROLE_STAFF => [
                    'name' => 'Staff',
                    'description' => 'Limited access based on assigned permissions.',
                ],
            ],
        ]);
    }

    private function authorizeBusinessUser(BusinessUser $businessUser): void
    {
        $business = $this->business();
        
        if (!$business || $businessUser->business_id !== $business->id) {
            abort(403, 'Unauthorized access');
        }

        // Only owner and admin can manage users
        $currentUserRole = BusinessUser::where('business_id', $business->id)
            ->where('user_id', $this->user()->id)
            ->first();

        if (!$currentUserRole || (!$currentUserRole->isOwner() && !$currentUserRole->isAdmin())) {
            abort(403, 'Only owners and admins can manage users');
        }
    }

    private function getDefaultPermissions(string $role): array
    {
        if ($role === BusinessUser::ROLE_ADMIN) {
            return [
                'customers' => true,
                'vendors' => true,
                'income' => true,
                'expense' => true,
                'stock' => true,
                'accounts' => true,
                'reports' => true,
            ];
        }

        // Staff gets basic permissions
        return [
            'customers' => true,
            'vendors' => true,
            'income' => true,
            'expense' => true,
            'stock' => false,
            'accounts' => false,
            'reports' => false,
        ];
    }

    private function formatBusinessUser(BusinessUser $businessUser): array
    {
        $user = $businessUser->user;
        
        return [
            'id' => $businessUser->id,
            'user_id' => $businessUser->user_id,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'avatar' => $user?->avatar ? asset('storage/' . $user->avatar) : null,
            'role' => $businessUser->role,
            'role_label' => ucfirst($businessUser->role),
            'branch_id' => $businessUser->branch_id,
            'branch_name' => $businessUser->branch?->name,
            'permissions' => $businessUser->permissions ?? [],
            'is_active' => $businessUser->is_active,
            'is_owner' => $businessUser->isOwner(),
            'is_admin' => $businessUser->isAdmin(),
            'created_at' => $businessUser->created_at->toIso8601String(),
        ];
    }
}
