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
 * Discord OAuth Provider:
 * - Builds auth URL
 * - Exchanges code for access token
 * - Fetches user info from Discord API
 */
class DiscordProvider implements SocialProvider
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
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'discord', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'identify email',
            'state'         => bin2hex(random_bytes(16)),
        ];

        return 'https://discord.com/api/oauth2/authorize?'.http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $redirectUri = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);
        $http = new Client(['verify' => false]);

        // Token exchange
        try {
            $token = json_decode((string)$http->post('https://discord.com/api/oauth2/token', [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Discord token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'discord'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Discord access token.', 0, [
                'provider'=>'discord','token_response'=>$this->safe($token)
            ]);
        }

        // User info fetch
        try {
            $ui = json_decode((string)$http->get('https://discord.com/api/users/@me', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Discord userinfo endpoint.', 0, [
                'endpoint' => 'user','provider' => 'discord'
            ], $ge);
        }

        if (empty($ui) || !isset($ui['id'])) {
            throw new UserInfoFetchException('Failed to retrieve Discord user information.', 0, [
                'provider'=>'discord','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'discord',
            'oauth'    => [
                'access_token'  => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type'    => $token['token_type'] ?? 'Bearer',
                'expires_in'    => $token['expires_in'] ?? null,
                'scope'         => $token['scope'] ?? null,
            ],
            'userinfo' => [
                'id'             => $ui['id'] ?? null,
                'email'          => $ui['email'] ?? null,
                'name'           => $ui['username'] ?? 'Discord User',
                'username'       => $ui['username'] ?? null,
                'discriminator'  => $ui['discriminator'] ?? null,
                'avatar'         => $ui['avatar'] ? 'https://cdn.discordapp.com/avatars/' . $ui['id'] . '/' . $ui['avatar'] . '.png' : null,
                'email_verified' => $ui['verified'] ?? false,
                'verified'       => $ui['verified'] ?? false,
                'locale'         => $ui['locale'] ?? null,
                'mfa_enabled'    => $ui['mfa_enabled'] ?? false,
                'premium_type'   => $ui['premium_type'] ?? null,
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Discord provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'discord'
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
}
