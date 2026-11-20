<?php

namespace App\Http\Responses;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        app(ActivityLogService::class)->log('Login', 'User logged in successfully');

        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        $user = $request->user();

        if ($user && ! $user->hasAnyRole(['super admin', 'admin'])) {
            $user->loadMissing('customer:id,user_id,phone,gender');
            $needsCompletion = ! $user->customer
                || empty($user->customer->phone)
                || empty($user->customer->gender);

            $target = $needsCompletion
                ? route('user.complete-profile.show')
                : route('home');

            return redirect()->intended($target);
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
