<?php

namespace Modules\Support\app\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Support\app\Http\Requests\CreateSupportTicketRequest;
use Modules\Support\app\Services\SupportTicketService;

class TicketsController extends Controller
{
    public function __construct(private readonly SupportTicketService $tickets) {}

    public function index(): View
    {
        return view('support::mentor.create', [
            'supportTickets' => $this->ticketsForCurrentUser(),
        ]);
    }

    public function store(CreateSupportTicketRequest $request): RedirectResponse
    {
        $this->tickets->create(Auth::user(), $request->validated());

        return back()->with('success', 'Support ticket created successfully.');
    }

    private function ticketsForCurrentUser()
    {
        return Auth::user()
            ->supportTickets()
            ->with('handler:id,name,email')
            ->latest()
            ->limit(20)
            ->get();
    }
}
