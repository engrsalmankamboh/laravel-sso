<?php
namespace Muhammadsalman\LaravelSso\Core;

use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Exceptions\InvalidPlatformException;

/**
 * High-level API: build redirect URLs + exchange code for provider data.
 * NOTE: No app user or token coupling — consumers handle that themselves.
 */
class SSOManager
{
    private SocialLoginFactory $factory;

    public function __construct(
        private PlatformService $platforms,
        private array $providersCfg
    ) {
        $this->factory = new SocialLoginFactory($providersCfg, $platforms);
    }

    /** Build OAuth authorization URL for a provider/platform. */
    public function redirectUrl(string $provider, ?string $platform = null): string
    {
        $platform = $platform ?: $this->platforms->default();
        $this->assertPlatform($platform);
        return $this->factory->make($provider)->getRedirectUrl($platform);
    }

    /**
     * Exchange auth code for provider tokens + userinfo.
     * Returns normalized provider-only payload.
     */
    public function verifyCode(string $provider, string $code, ?string $platform = null): array
    {
        $platform = $platform ?: $this->platforms->default();
        $this->assertPlatform($platform);

        $prov = $this->factory->make($provider)->loginUsingCode($code, $platform);

        return [
            'provider' => $prov['provider'],
            'oauth'    => $prov['oauth'] ?? [],
            'userinfo' => $prov['userinfo'] ?? [],
            'raw'      => $prov['raw'] ?? null,
        ];
    }

    /** Ensure platform is one of supported values. */
    private function assertPlatform(string $platform): void
    {
        if (!$this->platforms->isSupported($platform)) {
            throw new InvalidPlatformException(
                "Platform '{$platform}' is not supported. Allowed: " . implode(', ', $this->platforms->getSupportedPlatforms()),
                0,
                ['platform' => $platform]
            );
        }
    }
}
