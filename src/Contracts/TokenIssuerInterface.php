<?php
namespace Muhammadsalman\LaravelSso\Contracts;

interface TokenIssuerInterface
{
    public function issueToken(array $user): array;
}
