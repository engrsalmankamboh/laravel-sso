<?php
namespace Muhammadsalman\LaravelSso\Support;

/**
 * Handles deep link generation and validation for mobile platforms
 */
class DeepLinkHandler
{
    private PlatformService $platforms;

    public function __construct(PlatformService $platforms)
    {
        $this->platforms = $platforms;
    }

    /**
     * Generate a deep link URL for mobile platforms
     */
    public function generateDeepLink(string $provider, string $platform, array $params = []): string
    {
        if (!$this->platforms->isSupported($platform)) {
            throw new \InvalidArgumentException("Unsupported platform: {$platform}");
        }

        $deepLink = $this->platforms->deepLink($platform);
        if (!$deepLink) {
            throw new \InvalidArgumentException("Deep link scheme not configured for platform: {$platform}");
        }

        $callbackPath = $this->platforms->callbackPath($provider, $platform);
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';

        return $deepLink . '://' . $callbackPath . $queryString;
    }

    /**
     * Validate if a URL is a valid deep link for the given platform
     */
    public function isValidDeepLink(string $url, string $platform): bool
    {
        $deepLink = $this->platforms->deepLink($platform);
        if (!$deepLink) {
            return false;
        }

        $expectedScheme = $deepLink . '://';
        return str_starts_with($url, $expectedScheme);
    }

    /**
     * Extract parameters from a deep link URL
     */
    public function extractDeepLinkParams(string $url, string $platform): array
    {
        if (!$this->isValidDeepLink($url, $platform)) {
            return [];
        }

        $deepLink = $this->platforms->deepLink($platform);
        $expectedScheme = $deepLink . '://';
        $pathWithQuery = substr($url, strlen($expectedScheme));

        $parts = explode('?', $pathWithQuery, 2);
        if (count($parts) < 2) {
            return [];
        }

        parse_str($parts[1], $params);
        return $params;
    }

    /**
     * Get the callback path from a deep link URL
     */
    public function extractCallbackPath(string $url, string $platform): string
    {
        if (!$this->isValidDeepLink($url, $platform)) {
            return '';
        }

        $deepLink = $this->platforms->deepLink($platform);
        $expectedScheme = $deepLink . '://';
        $pathWithQuery = substr($url, strlen($expectedScheme));

        $parts = explode('?', $pathWithQuery, 2);
        return $parts[0] ?? '';
    }
}
