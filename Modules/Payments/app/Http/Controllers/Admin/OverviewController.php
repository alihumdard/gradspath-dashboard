<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;
use Modules\Payments\app\Models\CreditTransaction;
use Modules\Support\app\Models\SupportTicket;

class OverviewController extends Controller
{
    public function index(): View
    {
        return view('discovery::admin.admin', [
            'metrics' => [
                'total_bookings' => Booking::query()->count(),
                'completed_bookings' => Booking::query()->where('status', 'completed')->count(),
                'total_feedback' => Feedback::query()->count(),
                'open_tickets' => SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
                'credit_transactions' => CreditTransaction::query()->count(),
            ],
        ]);
    }
}
