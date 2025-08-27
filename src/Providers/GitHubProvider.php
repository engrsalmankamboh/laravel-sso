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
 * GitHub OAuth Provider:
 * - Builds auth URL
 * - Exchanges code for access token
 * - Fetches user info from GitHub API
 */
class GitHubProvider implements SocialProvider
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
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'github', $platform);
        }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'read:user user:email',
            'state'         => bin2hex(random_bytes(16)),
        ];

        return 'https://github.com/login/oauth/authorize?'.http_build_query($params);
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
            $token = json_decode((string)$http->post('https://github.com/login/oauth/access_token', [
                'form_params' => [
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting GitHub token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'github'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for GitHub access token.', 0, [
                'provider'=>'github','token_response'=>$this->safe($token)
            ]);
        }

        // User info fetch
        try {
            $ui = json_decode((string)$http->get('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Laravel-SSO-Package',
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting GitHub userinfo endpoint.', 0, [
                'endpoint' => 'user','provider' => 'github'
            ], $ge);
        }

        // Fetch email if not included in user info
        $email = $ui['email'] ?? null;
        if (!$email) {
            try {
                $emails = json_decode((string)$http->get('https://api.github.com/user/emails', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$token['access_token'],
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'Laravel-SSO-Package',
                    ],
                ])->getBody(), true);
                
                foreach ($emails as $emailData) {
                    if ($emailData['primary'] && $emailData['verified']) {
                        $email = $emailData['email'];
                        break;
                    }
                }
            } catch (GuzzleException $ge) {
                // Email fetch failed, continue without it
            }
        }

        if (empty($ui) || !isset($ui['id'])) {
            throw new UserInfoFetchException('Failed to retrieve GitHub user information.', 0, [
                'provider'=>'github','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'github',
            'oauth'    => [
                'access_token' => $token['access_token'] ?? null,
                'token_type'   => $token['token_type'] ?? 'Bearer',
                'scope'        => $token['scope'] ?? null,
            ],
            'userinfo' => [
                'id'             => $ui['id'] ?? null,
                'email'          => $email,
                'name'           => $ui['name'] ?? $ui['login'] ?? 'GitHub User',
                'avatar'         => $ui['avatar_url'] ?? null,
                'email_verified' => !empty($email),
                'username'       => $ui['login'] ?? null,
                'location'       => $ui['location'] ?? null,
                'bio'            => $ui['bio'] ?? null,
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "GitHub provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'github'
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
