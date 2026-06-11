<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $users = User::query()
            ->select(['id', 'nome', 'email', 'perfil'])
            ->orderBy('nome')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'nome' => $user->nome,
                'email' => $user->email,
                'perfil' => $user->perfil,
                'label' => "{$user->nome} ({$user->perfil})",
            ]);

        return response()->json(['data' => $users]);
    }

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
