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
            'default_currency' => ['sometimes', 'string', 'size:3'],
            'timezone' => ['sometimes', 'string', 'max:255'],
            'dashboard_preferences' => ['sometimes', 'array'],
            'notification_preferences' => ['sometimes', 'array'],
        ]);

        $settings = $this->settings($request);
        $settings->update($data);

        return response()->json(['data' => $settings->refresh()]);
    }

    private function settings(Request $request): UserSetting
    {
        return $request->user()->setting()->firstOrCreate([], [
            'default_currency' => 'BRL',
            'timezone' => 'America/Sao_Paulo',
            'dashboard_preferences' => [],
            'notification_preferences' => [],
        ]);
    }
}
