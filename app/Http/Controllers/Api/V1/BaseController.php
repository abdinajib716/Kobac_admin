<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $code);
    }

    /**
     * Error response
     */
    protected function error(string $message, string $errorCode = 'ERROR', int $code = 400, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];
        
        if ($data !== null) {
            $response = array_merge($response, $data);
        }
        
        return response()->json($response, $code);
    }

    /**
     * Paginated response
     */
    protected function paginated($paginator, $data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get the authenticated user
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Get the user's business (for business users - owners AND staff)
     */
    protected function business()
    {
        return $this->user()?->currentBusiness();
    }

    /**
     * Get the current branch from request context
     */
    protected function branchId()
    {
        return request()->attributes->get('branch_id');
    }

    /**
     * Get the current branch model from request context
     */
    protected function branch()
    {
        return request()->attributes->get('branch');
    }
}
