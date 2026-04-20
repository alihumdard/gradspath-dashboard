<?php

namespace Modules\Support\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Support\app\Http\Requests\ReplySupportTicketRequest;
use Modules\Support\app\Models\SupportTicket;
use Modules\Support\app\Services\SupportTicketService;

class SupportTicketsController extends Controller
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        $items = SupportTicket::query()
            ->with(['user:id,name,email', 'handler:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 30));

        return view('discovery::admin.admin', [
            'supportTickets' => $items,
        ]);
    }

    public function show(int $id): View
    {
        $ticket = SupportTicket::query()
            ->with(['user:id,name,email', 'handler:id,name,email'])
            ->findOrFail($id);

        return view('discovery::admin.admin', [
            'selectedSupportTicket' => $ticket,
        ]);
    }

    public function update(ReplySupportTicketRequest $request, int $id): RedirectResponse
    {
        $ticket = SupportTicket::query()->findOrFail($id);
        $this->tickets->reply(
            $ticket,
            Auth::user(),
            $request->validated()['admin_reply'],
            $request->validated()['status']
        );

        return back()->with('success', 'Support ticket updated successfully.');
    }
}
