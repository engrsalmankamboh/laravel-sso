<?php
namespace Muhammadsalman\LaravelSso\Support;

/**
 * Manages redirect URLs for different platforms and providers
 */
class RedirectUrlManager
{
    private PlatformService $platforms;
    private DeepLinkHandler $deepLinkHandler;

    public function __construct(PlatformService $platforms, DeepLinkHandler $deepLinkHandler)
    {
        $this->platforms = $platforms;
        $this->deepLinkHandler = $deepLinkHandler;
    }

    /**
     * Generate redirect URL for a specific provider and platform
     */
    public function generateRedirectUrl(string $provider, string $platform, string $baseUrl): string
    {
        if (!$this->platforms->isSupported($platform)) {
            throw new \InvalidArgumentException("Unsupported platform: {$platform}");
        }

        if ($platform === 'web') {
            return $this->generateWebRedirectUrl($provider, $baseUrl);
        }

        return $this->generateMobileRedirectUrl($provider, $platform);
    }

    /**
     * Generate web redirect URL
     */
    private function generateWebRedirectUrl(string $provider, string $baseUrl): string
    {
        $callbackPath = $this->platforms->callbackPath($provider, 'web');
        return rtrim($baseUrl, '/') . $callbackPath;
    }

    /**
     * Generate mobile redirect URL with deep link
     */
    private function generateMobileRedirectUrl(string $provider, string $platform): string
    {
        return $this->deepLinkHandler->generateDeepLink($provider, $platform);
    }

    /**
     * Validate redirect URL for a platform
     */
    public function validateRedirectUrl(string $url, string $platform): bool
    {
        if ($platform === 'web') {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        return $this->deepLinkHandler->isValidDeepLink($url, $platform);
    }

    /**
     * Get all available redirect URLs for a provider
     */
    public function getAllRedirectUrls(string $provider, string $baseUrl): array
    {
        $urls = [];
        $platforms = $this->platforms->getSupportedPlatforms();

        foreach ($platforms as $platform) {
            try {
                $urls[$platform] = $this->generateRedirectUrl($provider, $platform, $baseUrl);
            } catch (\Exception $e) {
                $urls[$platform] = null; // Mark as unavailable
            }
        }

        return $urls;
    }

    /**
     * Check if a platform requires post message handling
     */
    public function requiresPostMessage(string $platform): bool
    {
        return $this->platforms->requiresPostMessage($platform);
    }

    /**
     * Get platform-specific configuration
     */
    public function getPlatformConfig(string $platform): ?array
    {
        return $this->platforms->getPlatformConfig($platform);
    }
}
