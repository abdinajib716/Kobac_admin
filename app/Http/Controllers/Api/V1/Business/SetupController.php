<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Branch;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SetupController extends BaseController
{
    /**
     * First-time business setup
     * POST /api/v1/business/setup
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->user();

        if ($user->business) {
            return $this->error('Business already set up', 'ALREADY_SETUP', 400);
        }

        $validator = Validator::make($request->all(), [
            'business.name' => 'required|string|max:255',
            'business.legal_name' => 'nullable|string|max:255',
            'business.phone' => 'nullable|string|max:20',
            'business.email' => 'nullable|email|max:255',
            'business.address' => 'nullable|string|max:1000',
            'business.currency' => 'nullable|string|max:3',
            'main_branch.name' => 'required|string|max:255',
            'main_branch.code' => 'nullable|string|max:20',
            'main_branch.address' => 'nullable|string|max:1000',
            'initial_accounts' => 'nullable|array',
            'initial_accounts.*.name' => 'required|string|max:255',
            'initial_accounts.*.type' => 'required|in:cash,mobile_money,bank',
            'initial_accounts.*.provider' => 'nullable|string|max:100',
            'initial_accounts.*.initial_balance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        $result = DB::transaction(function () use ($user, $data) {
            // Create business
            $business = Business::create([
                'user_id' => $user->id,
                'name' => $data['business']['name'],
                'legal_name' => $data['business']['legal_name'] ?? null,
                'phone' => $data['business']['phone'] ?? null,
                'email' => $data['business']['email'] ?? null,
                'address' => $data['business']['address'] ?? null,
                'currency' => $data['business']['currency'] ?? 'USD',
            ]);

            // Create owner as BusinessUser
            BusinessUser::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => BusinessUser::ROLE_OWNER,
                'branch_id' => null, // Owner has access to all branches
                'permissions' => [], // Owner has full permissions by default
                'is_active' => true,
            ]);

            // Create main branch
            $branch = Branch::create([
                'business_id' => $business->id,
                'name' => $data['main_branch']['name'],
                'code' => $data['main_branch']['code'] ?? 'HQ',
                'address' => $data['main_branch']['address'] ?? null,
                'is_main' => true,
                'is_active' => true,
            ]);

            // Create initial accounts
            $accounts = [];
            if (!empty($data['initial_accounts'])) {
                foreach ($data['initial_accounts'] as $accountData) {
                    $accounts[] = Account::create([
                        'business_id' => $business->id,
                        'branch_id' => $branch->id,
                        'name' => $accountData['name'],
                        'type' => $accountData['type'],
                        'provider' => $accountData['provider'] ?? null,
                        'balance' => $accountData['initial_balance'] ?? 0,
                        'currency' => $business->currency,
                        'is_active' => true,
                    ]);
                }
            }

            return [
                'business' => $business,
                'branch' => $branch,
                'accounts' => $accounts,
            ];
        });

        return $this->success([
            'business' => [
                'id' => $result['business']->id,
                'name' => $result['business']->name,
                'currency' => $result['business']->currency,
            ],
            'branch' => [
                'id' => $result['branch']->id,
                'name' => $result['branch']->name,
                'is_main' => true,
            ],
            'accounts' => collect($result['accounts'])->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'balance' => (float) $account->balance,
                ];
            }),
        ], 'Business setup completed', 201);
    }

    /**
     * Get business profile
     * GET /api/v1/business/profile
     */
    public function show(Request $request): JsonResponse
    {
        $business = $this->business();

        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        return $this->success([
            'id' => $business->id,
            'name' => $business->name,
            'legal_name' => $business->legal_name,
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
            'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
            'currency' => $business->currency,
            'created_at' => $business->created_at->toIso8601String(),
        ]);
    }

    /**
     * Update business profile
     * PUT /api/v1/business/profile
     */
    public function update(Request $request): JsonResponse
    {
        $business = $this->business();

        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'legal_name' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'address' => 'sometimes|nullable|string|max:1000',
            'logo' => 'sometimes|nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business-logos', 'public');
            $data['logo'] = $path;
        }

        $business->update($data);

        return $this->success([
            'id' => $business->id,
            'name' => $business->name,
            'legal_name' => $business->legal_name,
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
            'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
            'currency' => $business->currency,
        ], 'Business updated successfully');
    }
}
