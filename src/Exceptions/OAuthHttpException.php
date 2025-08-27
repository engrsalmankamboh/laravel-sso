<?php
namespace Muhammadsalman\LaravelSso\Exceptions;

/** Wraps upstream HTTP failures (token/userinfo) with sanitized message. */
class OAuthHttpException extends SSOException {}
