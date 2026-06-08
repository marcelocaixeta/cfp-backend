<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->settings($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'moeda_padrao' => ['sometimes', 'string', 'size:3'],
            'fuso_horario' => ['sometimes', 'string', 'max:255'],
            'preferencias_dashboard' => ['sometimes', 'array'],
            'preferencias_notificacao' => ['sometimes', 'array'],
        ]);

        $settings = $this->settings($request);
        $settings->update($data);

        return response()->json(['data' => $settings->refresh()]);
    }

    private function settings(Request $request): UserSetting
    {
        return $request->user()->setting()->firstOrCreate([], [
            'moeda_padrao' => 'BRL',
            'fuso_horario' => 'America/Sao_Paulo',
            'preferencias_dashboard' => [],
            'preferencias_notificacao' => [],
        ]);
    }
}
