<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CompleteProfileRequest;
use App\Services\User\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the complete profile form for newly registered users.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $user->load('customer');

        return Inertia::render('user/CompleteProfile', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->customer?->phone ?? '',
                'gender' => $user->customer?->gender ?? '',
                'avatar_url' => $user->customer?->avatar ? $user->customer->avatar_url : null,
            ],
        ]);
    }

    /**
     * Save the user's completed profile details (name, gender, phone, avatar).
     */
    public function store(CompleteProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $user->load('customer');

        $user->update([
            'name' => $validated['name'],
        ]);

        $customerData = [
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'is_active' => true,
        ];

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->customer?->avatar;
            if (
                $oldAvatar
                && ! str_starts_with($oldAvatar, 'http://')
                && ! str_starts_with($oldAvatar, 'https://')
                && Storage::disk('public')->exists($oldAvatar)
            ) {
                Storage::disk('public')->delete($oldAvatar);
            }

            $customerData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->customer()->updateOrCreate(
            ['user_id' => $user->id],
            $customerData
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil Anda berhasil dilengkapi. Selamat berbelanja!']);

        return redirect()->route('home');
    }
}
