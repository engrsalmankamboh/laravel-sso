<?php
namespace Muhammadsalman\LaravelSso\Contracts;

/**
 * Minimal contract for all providers.
 * Implementations MUST:
 *  - Build provider auth URL
 *  - Exchange code for tokens and fetch userinfo
 */
interface SocialProvider
{
    public function getRedirectUrl(string $platform = 'web'): string;

    /**
     * @return array {
     *   provider: string,
     *   oauth: array,
     *   userinfo: array,
     *   raw?: array
     * }
     */
    public function loginUsingCode(string $code, string $platform = 'web'): array;
}
