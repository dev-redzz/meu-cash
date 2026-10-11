<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USER_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }

    public function authorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ]);
    }

    public function newState(): string
    {
        return Str::random(40);
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    public function userFromCode(string $code): array
    {
        $token = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $token->successful() || blank($token->json('access_token'))) {
            throw new RuntimeException('O Google recusou o login. Tente novamente.');
        }

        $profile = Http::withToken($token->json('access_token'))->timeout(15)->get(self::USER_URL);

        if (! $profile->successful() || blank($profile->json('email')) || blank($profile->json('sub'))) {
            throw new RuntimeException('Não foi possível ler os dados da sua conta Google.');
        }

        if (! filter_var($profile->json('email_verified'), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('O e-mail desta conta Google não está verificado.');
        }

        return [
            'google_id' => (string) $profile->json('sub'),
            'email' => mb_strtolower((string) $profile->json('email')),
            'name' => (string) ($profile->json('name') ?: str((string) $profile->json('email'))->before('@')),
            'avatar' => $profile->json('picture'),
        ];
    }
}
