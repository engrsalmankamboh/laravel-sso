<?php
namespace Muhammadsalman\LaravelSso\Exceptions;

use RuntimeException;

/**
 * Base exception for all SSO errors.
 * Includes user-friendly message + optional structured context.
 */
class SSOException extends RuntimeException
{
    protected array $context;

    public function __construct(string $message = 'SSO error', int $code = 0, array $context = [], ?\Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
        $this->context = $context;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error'   => class_basename(static::class),
            'message' => $this->getMessage(),
            'code'    => $this->getCode(),
        ];
    }
}
