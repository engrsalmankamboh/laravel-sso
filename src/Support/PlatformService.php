<?php
namespace Muhammadsalman\LaravelSso\Support;

/**
 * Platform helpers to keep providers/platform logic clean.
 */
class PlatformService
{
    public function __construct(private array $config) {}

    public function default(): string
    {
        return $this->config['default'] ?? 'web';
    }

    public function callbackPath(string $provider, string $platform): string
    {
        $tpl = $this->config[$platform]['callback_path'] ?? '/social/{provider}/callback';
        return str_replace('{provider}', $provider, $tpl);
    }

    public function deepLink(string $platform): ?string
    {
        return $this->config[$platform]['deep_link_scheme'] ?? null;
    }

    public function requiresPostMessage(string $platform): bool
    {
        return (bool) ($this->config[$platform]['requires_postmessage'] ?? false);
    }

    /**
     * Get platform-specific redirect URL with proper deep link handling
     */
    public function getRedirectUrl(string $baseUrl, string $provider, string $platform): string
    {
        if ($platform === 'web') {
            // For web, return the base URL as is (it should already be complete)
            return $baseUrl;
        }

        $deepLink = $this->deepLink($platform);
        if (!$deepLink) {
            throw new \InvalidArgumentException("Deep link scheme not configured for platform: {$platform}");
        }

        // For mobile platforms, extract the path from the base URL and create deep link
        $parsedUrl = parse_url($baseUrl);
        $path = $parsedUrl['path'] ?? '/social/' . $provider . '/callback';
        
        return $deepLink . '://' . ltrim($path, '/');
    }

    /**
     * Validate if a platform is supported
     */
    public function isSupported(string $platform): bool
    {
        return isset($this->config[$platform]);
    }

    /**
     * Get all supported platforms
     */
    public function getSupportedPlatforms(): array
    {
        return array_keys($this->config);
    }

    /**
     * Get platform configuration
     */
    public function getPlatformConfig(string $platform): ?array
    {
        return $this->config[$platform] ?? null;
    }
}
