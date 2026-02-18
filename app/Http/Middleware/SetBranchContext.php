<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Branch;

class SetBranchContext
{
    /**
     * Handle an incoming request.
     * Sets branch context from X-Branch-ID header.
     * 
     * If header is present:
     *   - Validates branch belongs to user's business
     *   - Sets branch in request attributes
     * If header is missing:
     *   - Defaults to main branch (if available)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->isBusiness()) {
            return $next($request);
        }

        $business = $user->currentBusiness();
        
        if (!$business) {
            return $next($request);
        }

        $branchId = $request->header('X-Branch-ID');
        
        if ($branchId) {
            // Validate branch belongs to this business
            $branch = Branch::where('id', $branchId)
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive branch.',
                    'error_code' => 'INVALID_BRANCH',
                    'branch_id' => $branchId,
                ], 400);
            }

            // Set branch context
            $request->attributes->set('branch', $branch);
            $request->attributes->set('branch_id', $branch->id);
        } else {
            // Default to main branch
            $mainBranch = $business->mainBranch();
            if ($mainBranch) {
                $request->attributes->set('branch', $mainBranch);
                $request->attributes->set('branch_id', $mainBranch->id);
            }
        }

        return $next($request);
    }
}
