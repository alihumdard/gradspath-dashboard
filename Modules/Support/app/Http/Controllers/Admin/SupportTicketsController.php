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
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $query = SupportTicket::query()
            ->with(['user:id,name,email', 'handler:id,name,email'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('ticket_ref', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at');

        $items = $query
            ->paginate((int) $request->integer('per_page', 30))
            ->withQueryString();

        return view('support::admin.index', [
            'supportTickets' => $items,
            'selectedSupportTicket' => null,
            'supportTicketCounts' => $this->statusCounts(),
            'selectedStatus' => $status,
            'searchTerm' => $search,
        ]);
    }

    public function show(int $id): View
    {
        $ticket = SupportTicket::query()
            ->with(['user:id,name,email', 'handler:id,name,email'])
            ->findOrFail($id);

        $items = SupportTicket::query()
            ->with(['user:id,name,email', 'handler:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('support::admin.index', [
            'supportTickets' => $items,
            'selectedSupportTicket' => $ticket,
            'supportTicketCounts' => $this->statusCounts(),
            'selectedStatus' => '',
            'searchTerm' => '',
        ]);
    }

    public function update(ReplySupportTicketRequest $request, int $id): RedirectResponse
    {
        $ticket = SupportTicket::query()->findOrFail($id);
        $this->tickets->reply(
            $ticket,
            Auth::user(),
            (string) ($request->validated()['admin_reply'] ?? ''),
            $request->validated()['status']
        );

        return back()->with('success', 'Support ticket updated successfully.');
    }

    private function statusCounts(): array
    {
        $counts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'all' => array_sum(array_map('intval', $counts)),
            'open' => (int) ($counts['open'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'more_information_required' => (int) ($counts['more_information_required'] ?? 0),
            'resolved' => (int) ($counts['resolved'] ?? 0),
            'closed' => (int) ($counts['closed'] ?? 0),
        ];
    }
}
