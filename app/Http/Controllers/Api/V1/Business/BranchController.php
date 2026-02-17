<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BranchController extends BaseController
{
    /**
     * List branches
     * GET /api/v1/business/branches
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        if (!$business) {
            return $this->error('Business not set up. Please complete business setup first.', 'NOT_SETUP', 404);
        }

        $branches = $business->branches()
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get()
            ->map(fn ($branch) => $this->formatBranch($branch));

        return $this->success($branches);
    }

    /**
     * Create branch
     * POST /api/v1/business/branches
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
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

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_main' => false,
            'is_active' => true,
        ]);

        return $this->success($this->formatBranch($branch), 'Branch created successfully', 201);
    }

    /**
     * Show branch
     * GET /api/v1/business/branches/{branch}
     */
    public function show(Branch $branch): JsonResponse
    {
        $this->authorizeBranch($branch);

        return $this->success($this->formatBranch($branch));
    }

    /**
     * Update branch
     * PUT /api/v1/business/branches/{branch}
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeBranch($branch);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:1000',
            'phone' => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $branch->update($validator->validated());

        return $this->success($this->formatBranch($branch), 'Branch updated successfully');
    }

    /**
     * Delete branch
     * DELETE /api/v1/business/branches/{branch}
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorizeBranch($branch);

        if ($branch->is_main) {
            return $this->error('Cannot delete main branch', 'CANNOT_DELETE_MAIN', 400);
        }

        if ($branch->accounts()->exists() || $branch->customers()->exists()) {
            return $this->error('Cannot delete branch with data. Deactivate it instead.', 'HAS_DATA', 400);
        }

        $branch->delete();

        return $this->success(null, 'Branch deleted successfully');
    }

    private function authorizeBranch(Branch $branch): void
    {
        if ($branch->business_id !== $this->business()?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatBranch(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'address' => $branch->address,
            'phone' => $branch->phone,
            'is_main' => $branch->is_main,
            'is_active' => $branch->is_active,
            'created_at' => $branch->created_at->toIso8601String(),
        ];
    }
}
