<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * Creates user if not exists, or logs in existing user.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }

        DB::transaction(function () use ($googleUser) {
            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'              => $googleUser->getName(),
                    'avatar_url'        => $googleUser->getAvatar(),
                    'password'          => null,  // social-only — no password
                    'email_verified_at' => now(), // Google already verified their email
                ]
            );

            // Store / update OAuth token
            DB::table('oauth_tokens')->updateOrInsert(
                [
                    'user_id'  => $user->id,
                    'provider' => 'google',
                ],
                [
                    'provider_user_id' => $googleUser->getId(),
                    'access_token'     => $googleUser->token,
                    'refresh_token'    => $googleUser->refreshToken,
                    'token_expires_at' => $googleUser->expiresIn
                        ? now()->addSeconds($googleUser->expiresIn)
                        : null,
                    'updated_at'       => now(),
                ]
            );

            // Assign default student role if new user (no role yet)
            if ($user->roles->isEmpty()) {
                $user->assignRole('student');

                // Create credit wallet + settings for new users
                DB::table('user_credits')->insertOrIgnore([
                    'user_id'    => $user->id,
                    'balance'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('user_settings')->insertOrIgnore([
                    'user_id'    => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Auth::login($user, remember: true);
        });

        return redirect()->route('discovery.dashboard');
    }
}
