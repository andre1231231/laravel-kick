<?php

namespace StuMason\Kick;

class Kick
{
    /**
     * Check if Kick is enabled.
     */
    public static function enabled(): bool
    {
        return (bool) config('kick.enabled', false);
    }

    /**
     * Get the configured route prefix.
     */
    public static function prefix(): string
    {
        return config('kick.prefix', 'kick');
    }

    /**
     * Get all configured tokens and their scopes.
     *
     * @return array<string, array<string>>
     */
    public static function tokens(): array
    {
        return collect(config('kick.tokens', []))
            ->filter(fn ($scopes, $token) => ! empty($token))
            ->all();
    }

    /**
     * Get scopes for a given token.
     *
     * @return array<string>|null
     */
    public static function scopesForToken(string $token): ?array
    {
        $tokens = static::tokens();

        return $tokens[$token] ?? null;
    }

    /**
     * Check if a token has a specific scope.
     */
    public static function tokenHasScope(string $token, string $scope): bool
    {
        $scopes = static::scopesForToken($token);

        if ($scopes === null) {
            return false;
        }

        // Wildcard grants all scopes
        if (in_array('*', $scopes, true)) {
            return true;
        }

        return in_array($scope, $scopes, true);
    }

    /**
     * Get the allowed artisan commands.
     *
     * @return array<string>
     */
    public static function allowedCommands(): array
    {
        return config('kick.allowed_commands', []);
    }

    /**
     * Check if a command is allowed.
     */
    public static function isCommandAllowed(string $command): bool
    {
        return in_array($command, static::allowedCommands(), true);
    }

    /**
     * Get the log reader configuration.
     *
     * @return array<string, mixed>
     */
    public static function logConfig(): array
    {
        return config('kick.logs', [
            'path' => storage_path('logs'),
            'allowed_extensions' => ['log'],
            'max_lines' => 500,
        ]);
    }
}
