<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProfileController extends BaseController
{
    /**
     * Update user profile
     * PUT /api/v1/profile
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $data = $validator->validated();

        if (isset($data['name'])) {
            $nameParts = explode(' ', $data['name']);
            $data['first_name'] = $nameParts[0] ?? $data['name'];
            $data['last_name'] = $nameParts[1] ?? '';
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ], 'Profile updated successfully');
    }
}
