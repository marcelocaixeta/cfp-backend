<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['senha'], $user->senha)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($data);

        return response()->json([
            'data' => [
                'message' => 'Se o e-mail existir, enviaremos um link para redefinir a senha.',
            ],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset([
            'token' => $data['token'],
            'email' => $data['email'],
            'password' => $data['senha'],
            'password_confirmation' => $request->string('senha_confirmation')->toString(),
        ], function (User $user, string $password): void {
            $user->forceFill([
                'senha' => $password,
                'lembrar_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['O token de redefinicao de senha e invalido ou expirou.'],
            ]);
        }

        return response()->json([
            'data' => [
                'message' => 'Senha redefinida com sucesso.',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['data' => ['message' => 'Logged out.']]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:usuarios,email,'.$user->id],
        ]);

        $user->update($data);

        return response()->json(['data' => $user->refresh()]);
    }
}
