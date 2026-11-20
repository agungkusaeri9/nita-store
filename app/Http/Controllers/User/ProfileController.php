<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\User\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the user's storefront profile page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load('customer');

        return Inertia::render('user/Profile', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->customer?->phone ?? '',
                'gender' => $user->customer?->gender ?? '',
                'avatar_url' => $user->customer?->avatar_url,
            ],
        ]);
    }

    /**
     * Update the user's storefront profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? $user->email,
        ]);
        $user->save();

        $user->customer()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil berhasil diperbarui.']);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the user's password from the storefront profile page.
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kata sandi berhasil diperbarui.']);

        return redirect()->back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
