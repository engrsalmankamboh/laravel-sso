<?php
namespace Muhammadsalman\LaravelSso\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Muhammadsalman\LaravelSso\Contracts\SocialProvider;
use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Exceptions\ProviderNotConfiguredException;
use Muhammadsalman\LaravelSso\Exceptions\OAuthHttpException;
use Muhammadsalman\LaravelSso\Exceptions\TokenExchangeException;
use Muhammadsalman\LaravelSso\Exceptions\UserInfoFetchException;

/**
 * Pure Google OAuth:
 * - Builds auth URL
 * - Exchanges code for access/ID token
 * - Fetches userinfo
 * No coupling to User/Passport; returns normalized arrays.
 */
class GoogleProvider implements SocialProvider
{
    public function __construct(private array $cfg, private PlatformService $platforms) {}

    public function getRedirectUrl(string $platform = 'web'): string
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);

        // For web platform, use the configured redirect URI directly
        // For mobile platforms, generate deep link
        if ($platform === 'web') {
            $redirect = $this->cfg['redirect'];
        } else {
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'google', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'openid email profile',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => bin2hex(random_bytes(16)), // Add state parameter for security
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $redirectUri = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);
        $http = new Client(['verify' => false]);

        try {
            $token = json_decode((string)$http->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code'          => $code,
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'redirect_uri'  => $redirectUri,
                    'grant_type'    => 'authorization_code',
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Google token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'google'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException(
                'Failed to exchange authorization code for access token.',
                0, ['provider'=>'google','token_response'=>$this->safe($token)]
            );
        }

        try {
            $ui = json_decode((string)$http->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => ['Authorization' => 'Bearer '.$token['access_token']],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Google userinfo endpoint.', 0, [
                'endpoint' => 'userinfo','provider' => 'google'
            ], $ge);
        }

        if (empty($ui) || (!isset($ui['id']) && !isset($ui['email']))) {
            throw new UserInfoFetchException('Failed to retrieve Google user information.', 0, [
                'provider'=>'google','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'google',
            'oauth'    => [
                'access_token'  => $token['access_token'] ?? null,
                'expires_in'    => $token['expires_in'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'id_token'      => $token['id_token'] ?? null,
                'scope'         => $token['scope'] ?? null,
                'token_type'    => $token['token_type'] ?? 'Bearer',
            ],
            'userinfo' => [
                'id'              => $ui['id'] ?? null,
                'email'           => $ui['email'] ?? null,
                'name'            => $ui['name'] ?? trim(($ui['given_name'] ?? '').' '.($ui['family_name'] ?? '')),
                'avatar'          => $ui['picture'] ?? null,
                'email_verified'  => !empty($ui['email']),
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException("Google provider is not configured for key: {$k}", 0, [
                    'missing' => $k, 'provider' => 'google'
                ]);
            }
        }
    }

    private function safe(?array $data): array
    {
        $data = $data ?? [];
        unset($data['client_secret'], $data['private_key']);
        return $data;
    }
}
