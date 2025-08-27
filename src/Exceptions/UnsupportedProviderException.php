<?php
namespace Muhammadsalman\LaravelSso\Exceptions;

/** Thrown when a provider key (google/facebook/apple) is not supported or not enabled. */
class UnsupportedProviderException extends SSOException {}
