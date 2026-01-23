<?php

namespace StuMason\Kick\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    public static function missingToken(): self
    {
        return new self(401, 'Authentication token required.');
    }

    public static function invalidToken(): self
    {
        return new self(401, 'Invalid authentication token.');
    }

    public static function insufficientScope(string $scope): self
    {
        return new self(403, "Token does not have required scope: {$scope}");
    }
}
