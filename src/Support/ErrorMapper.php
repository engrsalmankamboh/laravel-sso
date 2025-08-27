<?php
namespace Muhammadsalman\LaravelSso\Support;

use Illuminate\Support\Facades\Log;
use Muhammadsalman\LaravelSso\Exceptions\SSOException;

/**
 * Maps exceptions to client-safe payloads and logs detail for diagnostics.
 */
class ErrorMapper
{
    public static function map(\Throwable $e): array
    {
        if ($e instanceof SSOException) {
            Log::warning('[SSO] '.$e::class.': '.$e->getMessage(), [
                'code'    => $e->getCode(),
                'context' => $e->context(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            return $e->toArray();
        }

        Log::error('[SSO] Unhandled exception: '.$e->getMessage(), [
            'exception' => get_class($e),
            'code'      => $e->getCode(),
            'trace'     => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        return [
            'error'   => 'InternalError',
            'message' => 'Unexpected error occurred during social login.',
            'code'    => 0,
        ];
    }
}
