<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->supportTickets()->withCount('messages')->latest()->paginate(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'message' => ['required', 'string'],
        ]);

        $ticket = $request->user()->supportTickets()->create([
            'subject' => $data['subject'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return response()->json(['data' => $ticket->load('messages')], 201);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        return response()->json(['data' => $supportTicket->load('messages')]);
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);

        $supportTicket->update($request->validate([
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'status' => ['sometimes', Rule::in(['open', 'waiting_user', 'waiting_support', 'resolved', 'closed'])],
        ]));

        return response()->json(['data' => $supportTicket->refresh()]);
    }

    public function destroy(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorizeOwner($request, $supportTicket);
        $supportTicket->delete();

        return response()->json(status: 204);
    }

    private function authorizeOwner(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
    }
}
