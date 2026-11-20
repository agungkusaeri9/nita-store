<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Show the public user registration page.
     */
    public function showRegister(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Register a new customer account via web form and redirect to complete profile.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'username' => strtolower($validated['username']),
                'email' => strtolower($validated['email']),
                'password' => $validated['password'],
            ]);

            $user->customer()->create([
                'is_active' => true,
            ]);

            $webCustomerRole = Role::findOrCreate(AuthService::CUSTOMER_ROLE, 'web');
            $apiCustomerRole = Role::findOrCreate(AuthService::CUSTOMER_ROLE, AuthService::API_GUARD);
            $user->assignRole($webCustomerRole, $apiCustomerRole);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('user.complete-profile.show');
    }
}
