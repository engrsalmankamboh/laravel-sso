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
 * Microsoft OAuth Provider:
 * - Builds auth URL
 * - Exchanges code for access token
 * - Fetches user info from Microsoft Graph API
 */
class MicrosoftProvider implements SocialProvider
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
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'microsoft', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'openid profile email User.Read',
            'state'         => bin2hex(random_bytes(16)),
            'response_mode' => 'query',
        ];

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $redirectUri = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);
        $http = new Client(['verify' => false]);

        // Token exchange
        try {
            $token = json_decode((string)$http->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
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
            throw new OAuthHttpException('Failed contacting Microsoft token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'microsoft'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Microsoft access token.', 0, [
                'provider'=>'microsoft','token_response'=>$this->safe($token)
            ]);
        }

        // User info fetch from Microsoft Graph
        try {
            $ui = json_decode((string)$http->get('https://graph.microsoft.com/v1.0/me?$select=id,displayName,givenName,surname,userPrincipalName,mail,jobTitle,officeLocation,preferredLanguage', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                    'Accept' => 'application/json',
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Microsoft Graph userinfo endpoint.', 0, [
                'endpoint' => 'user','provider' => 'microsoft'
            ], $ge);
        }

        if (empty($ui) || !isset($ui['id'])) {
            throw new UserInfoFetchException('Failed to retrieve Microsoft user information.', 0, [
                'provider'=>'microsoft','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'microsoft',
            'oauth'    => [
                'access_token'  => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type'    => $token['token_type'] ?? 'Bearer',
                'expires_in'    => $token['expires_in'] ?? null,
                'scope'         => $token['scope'] ?? null,
                'id_token'      => $token['id_token'] ?? null,
            ],
            'userinfo' => [
                'id'             => $ui['id'] ?? null,
                'email'          => $ui['mail'] ?? $ui['userPrincipalName'] ?? null,
                'name'           => $ui['displayName'] ?? 'Microsoft User',
                'first_name'     => $ui['givenName'] ?? null,
                'last_name'      => $ui['surname'] ?? null,
                'avatar'         => null, // Microsoft Graph doesn't provide avatar in basic profile
                'email_verified' => !empty($ui['mail']),
                'job_title'      => $ui['jobTitle'] ?? null,
                'office_location' => $ui['officeLocation'] ?? null,
                'preferred_language' => $ui['preferredLanguage'] ?? null,
                'upn'           => $ui['userPrincipalName'] ?? null,
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Microsoft provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'microsoft'
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
