<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailSignup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailSignupController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $signup = EmailSignup::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            [
                'source' => $data['source'] ?? 'registration-popup',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return response()->json([
            'data' => [
                'id' => $signup->id,
                'email' => $signup->email,
                'source' => $signup->source,
            ],
        ], $signup->wasRecentlyCreated ? 201 : 200);
    }
}
