<?php

namespace App\Services\User;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleOAuthService
{
    /**
     * Check whether Google OAuth credentials are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));
    }

    /**
     * Build the Google OAuth 2.0 authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$params;
    }

    /**
     * Exchange the authorization code for an access token and fetch the Google user profile.
     *
     * @return array{id: string, name: string, email: string, avatar: string|null}
     */
    public function fetchUserFromCode(string $code): array
    {
        $tokenResponse = Http::timeout(15)
            ->connectTimeout(5)
            ->asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

        if ($tokenResponse->failed()) {
            throw new RuntimeException('Gagal menukar kode otorisasi Google.');
        }

        $accessToken = (string) $tokenResponse->json('access_token', '');
        if ($accessToken === '') {
            throw new RuntimeException('Access token Google tidak ditemukan.');
        }

        $userInfoResponse = Http::timeout(15)
            ->connectTimeout(5)
            ->withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($userInfoResponse->failed()) {
            throw new RuntimeException('Gagal mengambil data profil akun Google.');
        }

        $profile = $userInfoResponse->json();
        $googleId = (string) ($profile['sub'] ?? '');
        $email = (string) ($profile['email'] ?? '');

        if ($googleId === '' || $email === '') {
            throw new RuntimeException('Data akun Google tidak lengkap.');
        }

        return [
            'id' => $googleId,
            'name' => (string) ($profile['name'] ?? explode('@', $email)[0]),
            'email' => strtolower($email),
            'avatar' => isset($profile['picture']) ? (string) $profile['picture'] : null,
        ];
    }

    /**
     * Resolve the absolute Google OAuth callback URL.
     */
    public function getRedirectUri(): string
    {
        $configured = (string) config('services.google.redirect', '/auth/google/callback');

        if (str_starts_with($configured, 'http://') || str_starts_with($configured, 'https://')) {
            return $configured;
        }

        return url($configured);
    }
}
