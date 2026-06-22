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
        abort_unless($supportTicket->usuario_id === $request->user()->id || $request->user()->isAdmin(), 404);

        $data = $request->validate([
            'mensagem' => ['required', 'string'],
        ]);

        $message = $supportTicket->messages()->create([
            'usuario_id' => $request->user()->id,
            'mensagem' => $data['mensagem'],
        ]);

        return response()->json(['data' => $message->load('user:id,nome,email,perfil')], 201);
    }
}
