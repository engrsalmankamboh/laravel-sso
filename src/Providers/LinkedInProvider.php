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
 * LinkedIn OAuth Provider:
 * - Builds auth URL
 * - Exchanges code for access token
 * - Fetches user info from LinkedIn API
 */
class LinkedInProvider implements SocialProvider
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
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'linkedin', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'r_liteprofile r_emailaddress',
            'state'         => bin2hex(random_bytes(16)),
        ];

        return 'https://www.linkedin.com/oauth/v2/authorization?'.http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $http = new Client(['verify' => false]);

        if ($platform === 'web') {
            $redirectUri = $this->cfg['redirect'];
        } else {
            $redirectUri = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);
        }

        // Token exchange
        try {
            $token = json_decode((string)$http->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting LinkedIn token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'linkedin'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for LinkedIn access token.', 0, [
                'provider'=>'linkedin','token_response'=>$this->safe($token)
            ]);
        }

        // ✅ Fetch userinfo from OIDC endpoint
        try {
            $ui = json_decode((string)$http->get('https://api.linkedin.com/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting LinkedIn userinfo endpoint.', 0, [
                'endpoint' => 'userinfo','provider' => 'linkedin'
            ], $ge);
        }

        if (empty($ui) || !isset($ui['sub'])) {
            throw new UserInfoFetchException('Failed to retrieve LinkedIn user information.', 0, [
                'provider'=>'linkedin','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'linkedin',
            'oauth'    => [
                'access_token' => $token['access_token'] ?? null,
                'token_type'   => $token['token_type'] ?? 'Bearer',
                'expires_in'   => $token['expires_in'] ?? null,
            ],
            'userinfo' => [
                'id'             => $ui['sub'] ?? null,
                'email'          => $ui['email'] ?? null,
                'name'           => $ui['name'] ?? null,
                'first_name'     => $ui['given_name'] ?? null,
                'last_name'      => $ui['family_name'] ?? null,
                'avatar'         => $ui['picture'] ?? null,
                'email_verified' => $ui['email_verified'] ?? false,
                'profile_url'    => null, // OIDC me profile_url directly nahi milta
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }


    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "LinkedIn provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'linkedin'
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
