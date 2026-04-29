<?php

namespace Modules\Payments\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Payments\app\Services\AdminMentorPayoutsService;

class PayoutsController extends Controller
{
    public function __construct(private readonly AdminMentorPayoutsService $payouts) {}

    public function index(Request $request): View
    {
        return view('payments::admin.payouts', [
            'adminPayoutsData' => $this->payouts->build($request->query()),
            'selectedPayout' => null,
        ]);
    }

    public function show(Request $request, int $id): View
    {
        return view('payments::admin.payouts', [
            'adminPayoutsData' => $this->payouts->build($request->query()),
            'selectedPayout' => $this->payouts->detail($id),
        ]);
    }
}
