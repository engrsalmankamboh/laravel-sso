<?php

namespace Muhammadsalman\LaravelSso\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Session;
use Muhammadsalman\LaravelSso\Contracts\SocialProvider;
use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Exceptions\ProviderNotConfiguredException;
use Muhammadsalman\LaravelSso\Exceptions\OAuthHttpException;
use Muhammadsalman\LaravelSso\Exceptions\TokenExchangeException;
use Muhammadsalman\LaravelSso\Exceptions\UserInfoFetchException;

class TwitterProvider implements SocialProvider
{
    public function __construct(
        private array $cfg,
        private PlatformService $platforms
    ) {}

    public function getRedirectUrl(string $platform = 'web'): string
    {
        // Confidential => require secret; Public => no secret required
        $this->assertConfigured(
            $this->isConfidential() ? ['client_id', 'client_secret', 'redirect'] : ['client_id', 'redirect']
        );

        $redirect = $platform === 'web'
            ? $this->cfg['redirect']
            : $this->platforms->getRedirectUrl($this->cfg['redirect'], 'twitter', $platform);

        // Generate state + PKCE
        $state         = bin2hex(random_bytes(16));
        $codeChallenge = $this->generateCodeChallenge(); // stores verifier in session
        Session::put('sso.twitter.state', $state);

        $params = [
            'client_id'             => $this->cfg['client_id'],
            'redirect_uri'          => $redirect,
            'response_type'         => 'code',
            'scope'                 => $this->cfg['scopes'] ?? 'tweet.read users.read offline.access',
            'state'                 => $state,
            'code_challenge_method' => 'S256',
            'code_challenge'        => $codeChallenge,
        ];

        // X (Twitter) authorize endpoint
        return 'https://x.com/i/oauth2/authorize?' . http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(
            $this->isConfidential() ? ['client_id', 'client_secret', 'redirect'] : ['client_id', 'redirect']
        );

        $http = new Client(['verify' => false]);

        $redirectUri = $platform === 'web'
            ? $this->cfg['redirect']
            : $this->cfg['redirect'] . '?' . http_build_query(['platform' => $platform]);

        // CSRF: validate & consume state
        $incomingState = request()->get('state');
        $storedState   = Session::pull('sso.twitter.state');
        if (!$incomingState || !$storedState || !hash_equals($storedState, $incomingState)) {
            throw new TokenExchangeException('Invalid or missing OAuth state. Session may have expired.', 0, [
                'provider' => 'twitter'
            ]);
        }

        // PKCE: retrieve & consume code_verifier
        $codeVerifier = $this->getStoredCodeVerifier();

        // Headers (Confidential => Basic auth)
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept'       => 'application/json',
        ];
        if ($this->isConfidential()) {
            $basic = base64_encode(($this->cfg['client_id'] ?? '') . ':' . ($this->cfg['client_secret'] ?? ''));
            $headers['Authorization'] = 'Basic ' . $basic;
        }

        // Body (NEVER include client_secret here)
        $form = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->cfg['client_id'],
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'code_verifier' => $codeVerifier,
        ];

        try {
            // X (Twitter) token endpoint
            $resp  = $http->post('https://api.x.com/2/oauth2/token', [
                'form_params' => $form,
                'headers'     => $headers,
            ]);
            $token = json_decode((string)$resp->getBody(), true);
        } catch (ClientException $ge) {
            $status = $ge->getResponse()?->getStatusCode();
            $body   = $ge->getResponse()?->getBody()?->getContents();
            throw new OAuthHttpException('Failed contacting Twitter token endpoint.', 0, [
                'provider' => 'twitter',
                'status'   => $status,
                'error'    => $body,
                'hint'     => 'If you see "Missing valid authorization header", your app is Confidential or config is cached. Ensure Basic auth is sent or switch to Public.',
            ], $ge);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Twitter token endpoint.', 0, [
                'provider' => 'twitter'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Twitter access token.', 0, [
                'provider'       => 'twitter',
                'token_response' => $this->safe($token),
            ]);
        }

        // Fetch user info
        try {
            $resp = $http->get(
                'https://api.x.com/2/users/me?user.fields=id,name,username,profile_image_url,verified',
                ['headers' => ['Authorization' => 'Bearer ' . $token['access_token']]]
            );
            $ui = json_decode((string)$resp->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Twitter userinfo endpoint.', 0, [
                'endpoint' => 'user',
                'provider' => 'twitter'
            ], $ge);
        }

        if (empty($ui['data']) || !isset($ui['data']['id'])) {
            throw new UserInfoFetchException('Failed to retrieve Twitter user information.', 0, [
                'provider' => 'twitter',
                'userinfo_response' => $this->safe($ui)
            ]);
        }

        $userData = $ui['data'];

        return [
            'provider' => 'twitter',
            'oauth'    => [
                'access_token'  => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type'    => $token['token_type'] ?? 'Bearer',
                'expires_in'    => $token['expires_in'] ?? null,
                'scope'         => $token['scope'] ?? null,
            ],
            'userinfo' => [
                'id'             => $userData['id'] ?? null,
                'email'          => null, // X OAuth2 typically doesn't return email
                'name'           => $userData['name'] ?? 'Twitter User',
                'username'       => $userData['username'] ?? null,
                'avatar'         => $userData['profile_image_url'] ?? null,
                'email_verified' => false,
                'verified'       => $userData['verified'] ?? false,
                'profile_url'    => 'https://x.com/' . ($userData['username'] ?? ''),
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function generateCodeChallenge(): string
    {
        $codeVerifier = bin2hex(random_bytes(32));
        $this->storeCodeVerifier($codeVerifier);

        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    private function storeCodeVerifier(string $codeVerifier): void
    {
        Session::put('sso.twitter.code_verifier', $codeVerifier);
    }

    private function getStoredCodeVerifier(): string
    {
        $verifier = Session::pull('sso.twitter.code_verifier');
        if ($verifier && is_string($verifier)) {
            return $verifier;
        }

        throw new TokenExchangeException('Twitter code verifier not found. Session may have expired.', 0, [
            'provider' => 'twitter'
        ]);
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (!array_key_exists($k, $this->cfg) || empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Twitter provider is not configured for key: {$k}", 0, [
                        'missing' => $k,
                        'provider' => 'twitter'
                    ]
                );
            }
        }
    }

    private function safe(?array $data): array
    {
        $data = $data ?? [];
        unset($data['client_secret'], $data['private_key']);
        return $data;
    }

    /**
     * Confidential if:
     * - public_client is explicitly false, OR
     * - client_secret is present (auto-detect).
     */
    private function isConfidential(): bool
    {
        if (array_key_exists('public_client', $this->cfg)) {
            return $this->cfg['public_client'] === false;
        }
        return !empty($this->cfg['client_secret']);
    }
}
