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
            'origem' => ['nullable', 'string', 'max:100'],
        ]);

        $signup = EmailSignup::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            [
                'origem' => $data['origem'] ?? 'registration-popup',
                'endereco_ip' => $request->ip(),
                'agente_usuario' => $request->userAgent(),
            ],
        );

        return response()->json([
            'data' => [
                'id' => $signup->id,
                'email' => $signup->email,
                'origem' => $signup->origem,
            ],
        ], $signup->wasRecentlyCreated ? 201 : 200);
    }
}
