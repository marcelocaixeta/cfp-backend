<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->supportTickets()
                ->with([
                    'messages' => fn ($query) => $query->with('user:id,nome,email,perfil')->oldest(),
                ])
                ->withCount('messages')
                ->latest()
                ->paginate(),
        ]);
    }

    public function indexAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return response()->json([
            'data' => SupportTicket::query()
                ->with([
                    'user:id,nome,email,perfil',
                    'messages' => fn ($query) => $query->with('user:id,nome,email,perfil')->oldest(),
                ])
                ->withCount('messages')
                ->latest()
                ->orderByDesc('id')
                ->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assunto' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'prioridade' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'mensagem' => ['required', 'string'],
        ]);

        $ticket = $request->user()->supportTickets()->create([
            'assunto' => $data['assunto'],
            'categoria' => $data['categoria'] ?? null,
            'prioridade' => $data['prioridade'] ?? 'normal',
            'situacao' => 'open',
        ]);

        $ticket->messages()->create([
            'usuario_id' => $request->user()->id,
            'mensagem' => $data['mensagem'],
        ]);

        return response()->json(['data' => $ticket->load('messages')], 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        return response()->json(['data' => $supportTicket->load('messages.user:id,nome,email,perfil')]);
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        $supportTicket->update($request->validate([
            'assunto' => ['sometimes', 'required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'prioridade' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'situacao' => ['sometimes', Rule::in(['open', 'waiting_user', 'waiting_support', 'resolved', 'closed'])],
        ]));

        return response()->json(['data' => $supportTicket->refresh()]);
    }

    public function resolve(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'mensagem' => ['sometimes', 'string'],
        ]);

        DB::transaction(function () use ($data, $request, $supportTicket): void {
            if (isset($data['mensagem']) && trim($data['mensagem']) !== '') {
                $supportTicket->messages()->create([
                    'usuario_id' => $request->user()->id,
                    'mensagem' => trim($data['mensagem']),
                ]);
            }

            $supportTicket->update([
                'situacao' => 'resolved',
            ]);
        });

        return response()->json(['data' => $supportTicket->refresh()->load('messages.user:id,nome,email,perfil')]);
    }

    public function destroy(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);
        $supportTicket->delete();

        return response()->json(status: 204);
    }

    private function authorizeOwner(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->usuario_id === $request->user()->id, 404);
    }
}
