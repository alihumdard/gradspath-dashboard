<?php

namespace Modules\Support\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Support\app\Http\Requests\CreateSupportTicketRequest;
use Modules\Support\app\Models\SupportTicket;
use Modules\Support\app\Services\SupportTicketService;

class TicketsController extends Controller
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        return view('support::mentor.create', [
            'tickets' => SupportTicket::query()
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate((int) $request->integer('per_page', 20)),
        ]);
    }

    public function myTickets(Request $request): RedirectResponse
    {
        return redirect()->route('mentor.support.index', $request->query());
    }

    public function show(int $id): View
    {
        $ticket = SupportTicket::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('support::mentor.create', [
            'tickets' => SupportTicket::query()
                ->where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate(20),
            'selectedTicket' => $ticket,
        ]);
    }

    public function store(CreateSupportTicketRequest $request): RedirectResponse
    {
        $this->tickets->create(Auth::user(), $request->validated());

        return back()->with('success', 'Support ticket created successfully.');
    }
}
