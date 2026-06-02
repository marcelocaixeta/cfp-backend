<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketMessageController extends Controller
{
    public function store(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $message = $supportTicket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return response()->json(['data' => $message], 201);
    }
}
