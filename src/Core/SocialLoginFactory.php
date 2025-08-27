<?php
namespace Muhammadsalman\LaravelSso\Core;

use Muhammadsalman\LaravelSso\Support\PlatformService;
use Muhammadsalman\LaravelSso\Providers\GoogleProvider;
use Muhammadsalman\LaravelSso\Providers\FacebookProvider;
use Muhammadsalman\LaravelSso\Providers\AppleProvider;
use Muhammadsalman\LaravelSso\Providers\GitHubProvider;
use Muhammadsalman\LaravelSso\Providers\LinkedInProvider;
use Muhammadsalman\LaravelSso\Providers\TwitterProvider;
use Muhammadsalman\LaravelSso\Providers\DiscordProvider;
use Muhammadsalman\LaravelSso\Providers\MicrosoftProvider;
use Muhammadsalman\LaravelSso\Exceptions\UnsupportedProviderException;

/**
 * Creates provider instances with validated config.
 */
class SocialLoginFactory
{
    public function __construct(private array $cfg, private PlatformService $platforms) {}

    /**
     * @throws UnsupportedProviderException
     */
    public function make(string $provider)
    {
        $p = $this->cfg[$provider] ?? null;
        if (!$p) {
            throw new UnsupportedProviderException(
                "Provider '{$provider}' is not supported or missing from config.",
                0,
                ['provider' => $provider]
            );
        }

        return match ($provider) {
            'google'    => new GoogleProvider($p, $this->platforms),
            'facebook'  => new FacebookProvider($p, $this->platforms),
            'apple'     => new AppleProvider($p, $this->platforms),
            'github'    => new GitHubProvider($p, $this->platforms),
            'linkedin'  => new LinkedInProvider($p, $this->platforms),
            'twitter'   => new TwitterProvider($p, $this->platforms),
            'discord'   => new DiscordProvider($p, $this->platforms),
            'microsoft' => new MicrosoftProvider($p, $this->platforms),
            default     => throw new UnsupportedProviderException(
                "Provider '{$provider}' is not supported.",
                0,
                ['provider' => $provider]
            ),
        };
    }
}
