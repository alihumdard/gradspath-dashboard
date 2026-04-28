<?php

namespace Modules\Auth\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Auth\app\Models\User;
use Modules\Settings\app\Models\Mentor;

class AdminUserController extends Controller
{
    // ── User Management ──────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->role($request->role))
            ->latest()
            ->paginate(20);

        return view('discovery::admin.admin', compact('users'));
    }

    public function show(int $id): View
    {
        $user = User::with(['roles', 'mentor'])->findOrFail($id);
        return view('discovery::admin.admin', compact('user'));
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $user = User::findOrFail($id);
        abort_if((int) $user->id === (int) Auth::id(), 422, 'You cannot delete your own admin account.');
        $before = $user->toArray();
        $user->delete();

        $this->logAction('delete_user', 'users', $id, $before, null);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "User {$before['name']} deleted successfully.",
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$before['name']} deleted.");
    }

    public function destroyMentor(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $mentor = Mentor::query()->with('user')->findOrFail($id);
        $mentorName = $mentor->user?->name ?: "Mentor #{$mentor->id}";
        $before = [
            'mentor' => $mentor->toArray(),
            'user' => $mentor->user?->toArray(),
        ];

        if ($mentor->user) {
            abort_if((int) $mentor->user->id === (int) Auth::id(), 422, 'You cannot delete your own admin account.');
            $mentor->user->delete();
        } else {
            $mentor->delete();
        }

        $this->logAction('delete_mentor', 'mentors', $id, $before, null);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Mentor {$mentorName} deleted successfully.",
            ]);
        }

        return redirect()->route('admin.mentors')
            ->with('success', "Mentor {$mentorName} deleted.");
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $user   = User::findOrFail($id);
        $before = $user->is_active;
        $user->update(['is_active' => !$user->is_active]);

        $action = $user->is_active ? 'activate_user' : 'suspend_user';
        $this->logAction($action, 'users', $id, ['is_active' => $before], ['is_active' => $user->is_active]);

        $msg = $user->is_active ? 'User activated.' : 'User suspended.';
        return back()->with('success', $msg);
    }

    // ── Mentor Approval ──────────────────────────────────────────────────────

    public function pendingMentors(): View
    {
        $mentors = DB::table('mentors')
            ->join('users', 'mentors.user_id', '=', 'users.id')
            ->where('mentors.status', 'pending')
            ->select('mentors.*', 'users.name', 'users.email')
            ->paginate(20);

        return view('discovery::admin.admin', compact('mentors'));
    }

    public function approveMentor(int $id): RedirectResponse
    {
        $before = DB::table('mentors')->where('id', $id)->first();

        DB::table('mentors')->where('id', $id)->update([
            'status'      => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'updated_at'  => now(),
        ]);

        $this->logAction(
            'approve_mentor',
            'mentors',
            $id,
            ['status' => $before->status],
            ['status' => 'active']
        );

        return back()->with('success', 'Mentor approved successfully.');
    }

    public function rejectMentor(int $id): RedirectResponse
    {
        $before = DB::table('mentors')->where('id', $id)->first();
        DB::table('mentors')->where('id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);

        $this->logAction(
            'reject_mentor',
            'mentors',
            $id,
            ['status' => $before->status],
            ['status' => 'rejected']
        );

        return back()->with('success', 'Mentor application rejected.');
    }

    public function pauseMentor(int $id): RedirectResponse
    {
        $before = DB::table('mentors')->where('id', $id)->first();
        DB::table('mentors')->where('id', $id)->update(['status' => 'paused', 'updated_at' => now()]);

        $this->logAction(
            'pause_mentor',
            'mentors',
            $id,
            ['status' => $before->status],
            ['status' => 'paused']
        );

        return back()->with('success', 'Mentor paused.');
    }

    // ── Admin Logs ────────────────────────────────────────────────────────────

    public function logs(Request $request): View
    {
        $logs = DB::table('admin_logs')
            ->join('users', 'admin_logs.admin_id', '=', 'users.id')
            ->select('admin_logs.*', 'users.name as admin_name')
            ->when($request->action, fn($q) => $q->where('admin_logs.action', $request->action))
            ->orderByDesc('admin_logs.created_at')
            ->paginate(30);

        return view('discovery::admin.admin', compact('logs'));
    }

    // ── Private Helper ────────────────────────────────────────────────────────

    private function logAction(string $action, string $table, int $targetId, mixed $before, mixed $after): void
    {
        DB::table('admin_logs')->insert([
            'admin_id'     => Auth::id(),
            'action'       => $action,
            'target_table' => $table,
            'target_id'    => $targetId,
            'before_state' => $before ? json_encode($before) : null,
            'after_state'  => $after  ? json_encode($after)  : null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}
