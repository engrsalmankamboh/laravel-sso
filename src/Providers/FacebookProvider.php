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
 * Facebook OAuth (Graph API).
 */
class FacebookProvider implements SocialProvider
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
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'facebook', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'email,public_profile',
            'state'         => bin2hex(random_bytes(16)),
        ];
        $v = $this->cfg['api_version'] ?? 'v18.0';

        return "https://www.facebook.com/{$v}/dialog/oauth?".http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $redirect = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);
        $v = $this->cfg['api_version'] ?? 'v18.0';
        $http = new Client(['verify' => false]);

        // Token exchange
        try {
            $token = json_decode((string)$http->get("https://graph.facebook.com/{$v}/oauth/access_token", [
                'query' => [
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $redirect,
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Facebook token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'facebook'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Facebook access token.', 0, [
                'provider'=>'facebook','token_response'=>$this->safe($token)
            ]);
        }

        // Userinfo fetch
        try {
            $fields = 'id,name,email,first_name,last_name,picture';
            $ui = json_decode((string)$http->get("https://graph.facebook.com/{$v}/me", [
                'query' => [
                    'access_token' => $token['access_token'],
                    'fields'       => $fields
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Facebook userinfo endpoint.', 0, [
                'endpoint' => 'me','provider' => 'facebook'
            ], $ge);
        }

        if (empty($ui) || (!isset($ui['id']) && !isset($ui['email']))) {
            throw new UserInfoFetchException('Failed to retrieve Facebook user information.', 0, [
                'provider'=>'facebook','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'facebook',
            'oauth'    => [
                'access_token' => $token['access_token'] ?? null,
                'token_type'   => $token['token_type'] ?? 'Bearer',
                'expires_in'   => $token['expires_in'] ?? null,
            ],
            'userinfo' => [
                'id'             => $ui['id'] ?? null,
                'email'          => $ui['email'] ?? null,
                'name'           => $ui['name'] ?? 'Facebook User',
                'avatar'         => $ui['picture']['data']['url'] ?? null,
                'email_verified' => !empty($ui['email']),
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Facebook provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'facebook'
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
