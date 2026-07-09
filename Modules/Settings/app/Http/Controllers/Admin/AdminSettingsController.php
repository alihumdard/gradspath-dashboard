<?php

namespace Modules\Settings\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    /**
     * Display administrative settings.
     */
    public function index(): View
    {
        return view('settings::admin.index', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the administrator's email.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The provided current password does not match our records.'])
                ->withInput($request->only('email'));
        }

        $user->email = $request->input('email');
        $user->save();

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Email address updated successfully.');
    }

    /**
     * Update the administrator's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)->letters(), 'confirmed'],
        ]);

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        // Re-login the user to update the password hash in the session and keep them logged in
        Auth::login($user);

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Password updated successfully.');
    }
}
