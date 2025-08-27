<?php

namespace Muhammadsalman\LaravelSso\Facades;

use Illuminate\Support\Facades\Facade;
use Muhammadsalman\LaravelSso\Core\SSOManager;

/**
 * Facade surface for quick usage.
 *
 * @method static string redirectUrl(string $provider, string $platform = 'web')
 * @method static array  verifyCode(string $provider, string $code, string $platform = 'web')
 */
class SSO extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SSOManager::class;
    }
}
