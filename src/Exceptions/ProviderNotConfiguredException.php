<?php
namespace Muhammadsalman\LaravelSso\Exceptions;

/** Thrown when provider config (client_id/secret/redirect) is missing/invalid. */
class ProviderNotConfiguredException extends SSOException {}
