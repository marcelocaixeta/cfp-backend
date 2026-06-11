<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'perfil' => ['required', Rule::in([User::PROFILE_ADMIN, User::PROFILE_USER])],
        ]);

        $user->update([
            'perfil' => $data['perfil'],
        ]);

        return response()->json(['data' => $user->refresh()]);
    }
}
