<?php
namespace Muhammadsalman\LaravelSso\Contracts;

interface UserResolverInterface
{
    public function resolve(array $providerData): array;
}
