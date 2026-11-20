<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\User\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    public function __construct(
        protected GoogleOAuthService $googleOAuthService
    ) {}

    /**
     * Redirect the user to Google's OAuth 2.0 consent screen.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->googleOAuthService->isConfigured()) {
            return redirect()->route('register')->with(
                'error',
                'Konfigurasi Google OAuth belum diatur di file .env.'
            );
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->googleOAuthService->getAuthorizationUrl($state));
    }

    /**
     * Handle the OAuth callback from Google and authenticate or register the user.
     */
    public function callback(Request $request): RedirectResponse
    {
        $sessionState = (string) $request->session()->pull('google_oauth_state', '');
        $incomingState = (string) $request->input('state', '');

        if ($sessionState === '' || $incomingState === '' || ! hash_equals($sessionState, $incomingState)) {
            return redirect()->route('login')->with(
                'error',
                'Sesi autentikasi Google tidak valid. Silakan coba lagi.'
            );
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with(
                'error',
                'Autentikasi Google dibatalkan.'
            );
        }

        try {
            $googleUser = $this->googleOAuthService->fetchUserFromCode((string) $request->input('code', ''));
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed: '.$e->getMessage());

            return redirect()->route('login')->with(
                'error',
                'Gagal masuk dengan Google: '.$e->getMessage()
            );
        }

        $isNewUser = false;

        $user = DB::transaction(function () use ($googleUser, &$isNewUser): User {
            $user = User::query()
                ->where('google_id', $googleUser['id'])
                ->orWhere('email', $googleUser['email'])
                ->first();

            if ($user) {
                $updates = [];
                if (! $user->google_id) {
                    $updates['google_id'] = $googleUser['id'];
                }
                if (! $user->email_verified_at) {
                    $updates['email_verified_at'] = now();
                }
                if ($updates !== []) {
                    $user->update($updates);
                }

                if (! $user->hasAnyRole(['super admin', 'admin']) && ! $user->customer) {
                    $user->customer()->create([
                        'avatar' => $googleUser['avatar'],
                        'is_active' => true,
                    ]);
                }

                return $user;
            }

            $isNewUser = true;
            $username = $this->generateUniqueUsername($googleUser['email'], $googleUser['name']);

            $user = User::create([
                'name' => $googleUser['name'],
                'username' => $username,
                'email' => $googleUser['email'],
                'google_id' => $googleUser['id'],
                'email_verified_at' => now(),
                'password' => Str::random(32),
            ]);

            $user->customer()->create([
                'avatar' => $googleUser['avatar'],
                'is_active' => true,
            ]);

            $webCustomerRole = Role::findOrCreate(AuthService::CUSTOMER_ROLE, 'web');
            $apiCustomerRole = Role::findOrCreate(AuthService::CUSTOMER_ROLE, AuthService::API_GUARD);
            $user->assignRole($webCustomerRole, $apiCustomerRole);

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->hasAnyRole(['super admin', 'admin'])) {
            return redirect()->route('dashboard');
        }

        $user->loadMissing('customer:id,user_id,phone,gender');
        $needsCompletion = $isNewUser
            || ! $user->customer
            || empty($user->customer->phone)
            || empty($user->customer->gender);

        if ($needsCompletion) {
            return redirect()->route('user.complete-profile.show');
        }

        return redirect()->route('home');
    }

    /**
     * Generate a unique username from the user's email or name.
     */
    protected function generateUniqueUsername(string $email, string $name): string
    {
        $base = Str::slug(explode('@', $email)[0] ?: $name, '_');
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $counter = 1;
        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $base.'_'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
