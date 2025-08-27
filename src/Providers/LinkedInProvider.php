<?php
namespace Muhammadsalman\LaravelSso\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Contracts\SocialProvider;
use Muhammadsalman\LaravelSso\Exceptions\OAuthHttpException;
use Muhammadsalman\LaravelSso\Exceptions\TokenExchangeException;
use Muhammadsalman\LaravelSso\Exceptions\UserInfoFetchException;
use Muhammadsalman\LaravelSso\Exceptions\ProviderNotConfiguredException;

class TwitterProvider implements SocialProvider
{
    public function __construct(private array $cfg, private PlatformService $platforms) {}

    public function getRedirectUrl(string $platform = 'web'): string
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);

        $redirect = ($platform === 'web')
            ? $this->cfg['redirect']
            : $this->platforms->getRedirectUrl($this->cfg['redirect'], 'twitter', $platform);

        $codeVerifier  = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // state ke andar code_verifier bhi bhej rahe hain
        $statePayload = [
            'nonce'        => bin2hex(random_bytes(16)),
            'code_verifier'=> $codeVerifier,
        ];
        $state = base64_encode(json_encode($statePayload));

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => $this->cfg['scopes'] ?? 'tweet.read users.read offline.access',
            'state'         => $state,
            'code_challenge_method' => 'S256',
            'code_challenge' => $codeChallenge,
        ];

        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web', ?string $state = null): array
    {
        $this->assertConfigured(['client_id','client_secret','redirect']);
        $http = new Client(['verify' => false]);
        if ($platform === 'web') {
            $redirectUri = $this->cfg['redirect'];
        } else {
            $redirectUri = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'twitter', $platform);
        }

        // state se code_verifier nikalna
        if (!$state) {
            throw new TokenExchangeException('Missing state parameter in callback.');
        }
        $decoded = json_decode(base64_decode($state), true);
        $codeVerifier = $decoded['code_verifier'] ?? null;

        if (!$codeVerifier) {
            throw new TokenExchangeException('Twitter code verifier not found in state.');
        }

        // Token exchange
        try {
            $response = $http->post('https://api.twitter.com/2/oauth2/token', [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->cfg['client_secret'],
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                    'code_verifier' => $codeVerifier,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $token = json_decode((string)$response->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Twitter token endpoint.', 0, [
                'endpoint' => 'token','provider' => 'twitter'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Twitter access token.', 0, [
                'provider'=>'twitter','token_response'=>$this->safe($token)
            ]);
        }

        // User info fetch
        try {
            $response = $http->get('https://api.twitter.com/2/users/me?user.fields=id,name,username,profile_image_url,verified', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token['access_token'],
                ],
            ]);
            $ui = json_decode((string)$response->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Twitter userinfo endpoint.', 0, [
                'endpoint' => 'user','provider' => 'twitter'
            ], $ge);
        }

        if (empty($ui['data']) || !isset($ui['data']['id'])) {
            throw new UserInfoFetchException('Failed to retrieve Twitter user information.', 0, [
                'provider'=>'twitter','userinfo_response'=>$this->safe($ui)
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
                'email'          => null,
                'name'           => $userData['name'] ?? 'Twitter User',
                'username'       => $userData['username'] ?? null,
                'avatar'         => $userData['profile_image_url'] ?? null,
                'email_verified' => false,
                'verified'       => $userData['verified'] ?? false,
                'profile_url'    => 'https://twitter.com/' . ($userData['username'] ?? ''),
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Twitter provider is not configured for key: {$k}", 0, [
                        'missing'=>$k, 'provider'=>'twitter'
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
