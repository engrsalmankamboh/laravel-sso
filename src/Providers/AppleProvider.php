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
 * Sign in with Apple:
 * - Builds auth URL
 * - Signs ES256 client secret (JWT)
 * - Exchanges code and fetches OIDC userinfo
 */
class AppleProvider implements SocialProvider
{
    public function __construct(private array $cfg, private PlatformService $platforms) {}

    public function getRedirectUrl(string $platform = 'web'): string
    {
        $this->assertConfigured(['client_id','team_id','key_id','private_key','redirect']);

        // For web platform, use the configured redirect URI directly
        // For mobile platforms, generate deep link
        if ($platform === 'web') {
            $redirect = $this->cfg['redirect'];
        } else {
            $redirect = $this->platforms->getRedirectUrl($this->cfg['redirect'], 'apple', $platform);
    }

        $params = [
            'client_id'     => $this->cfg['client_id'],
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => 'name email',
            'response_mode' => 'form_post',
            'state'         => bin2hex(random_bytes(16)), // Add state parameter for security
        ];
        return 'https://appleid.apple.com/auth/authorize?'.http_build_query($params);
    }

    public function loginUsingCode(string $code, string $platform = 'web'): array
    {
        $this->assertConfigured(['client_id','team_id','key_id','private_key','redirect']);
        $http = new Client(['verify' => false]);
        $redirect = $this->cfg['redirect'].'?'.http_build_query(['platform' => $platform]);

        // Token exchange
        try {
            $token = json_decode((string)$http->post('https://appleid.apple.com/auth/token', [
                'form_params' => [
                    'client_id'     => $this->cfg['client_id'],
                    'client_secret' => $this->clientSecret(),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $redirect,
                ],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Apple token endpoint.', 0, [
                'endpoint'=>'token','provider'=>'apple'
            ], $ge);
        }

        if (empty($token['access_token'])) {
            throw new TokenExchangeException('Failed to exchange code for Apple access token.', 0, [
                'provider'=>'apple','token_response'=>$this->safe($token)
            ]);
        }

        // Userinfo fetch
        try {
            $ui = json_decode((string)$http->get('https://appleid.apple.com/auth/userinfo', [
                'headers' => ['Authorization' => 'Bearer '.$token['access_token']],
            ])->getBody(), true);
        } catch (GuzzleException $ge) {
            throw new OAuthHttpException('Failed contacting Apple userinfo endpoint.', 0, [
                'endpoint'=>'userinfo','provider'=>'apple'
            ], $ge);
        }

        if (empty($ui) || (!isset($ui['sub']) && !isset($ui['email']))) {
            throw new UserInfoFetchException('Failed to retrieve Apple user information.', 0, [
                'provider'=>'apple','userinfo_response'=>$this->safe($ui)
            ]);
        }

        return [
            'provider' => 'apple',
            'oauth'    => [
                'access_token'  => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'id_token'      => $token['id_token'] ?? null,
                'expires_in'    => $token['expires_in'] ?? null,
                'token_type'    => $token['token_type'] ?? 'Bearer',
            ],
            'userinfo' => [
                'sub'            => $ui['sub'] ?? null,
                'email'          => $ui['email'] ?? null,
                'name'           => $ui['name']['firstName'] ?? 'Apple User',
                'email_verified' => !empty($ui['email']),
            ],
            'raw' => ['token' => $this->safe($token), 'userinfo' => $this->safe($ui)],
        ];
    }

    /** Build ES256 client secret (JWT) for Apple token endpoint. */
    private function clientSecret(): string
    {
        $teamId = $this->cfg['team_id'];
        $keyId  = $this->cfg['key_id'];
        $sub    = $this->cfg['client_id'];
        $aud    = $this->cfg['aud'] ?? 'https://appleid.apple.com';
        $privateKey = $this->pem($this->cfg['private_key']);

        $header  = $this->b64(json_encode(['alg' => 'ES256', 'kid' => $keyId]));
        $payload = $this->b64(json_encode([
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'aud' => $aud,
            'sub' => $sub,
        ]));
        $data = $header.'.'.$payload;

        $signature = '';
        $ok = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok || $signature === '') {
            throw new TokenExchangeException('Failed to sign Apple client secret (ES256).', 0, [
                'provider'=>'apple','step'=>'client_secret_sign'
            ]);
        }
        return $data.'.'.$this->b64($signature);
    }

    private function pem(string $val)
    {
        return is_file($val) ? file_get_contents($val) : $val;
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function assertConfigured(array $required): void
    {
        foreach ($required as $k) {
            if (empty($this->cfg[$k])) {
                throw new ProviderNotConfiguredException(
                    "Apple provider is not configured for key: {$k}", 0, [
                        'missing'=>$k,'provider'=>'apple'
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
