<?php

namespace StuMason\Kick\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \StuMason\Kick\Kick
 *
 * @method static bool enabled()
 * @method static string prefix()
 * @method static array tokens()
 * @method static array|null scopesForToken(string $token)
 * @method static bool tokenHasScope(string $token, string $scope)
 * @method static array allowedCommands()
 * @method static bool isCommandAllowed(string $command)
 * @method static array logConfig()
 */
class Kick extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \StuMason\Kick\Kick::class;
    }
}
