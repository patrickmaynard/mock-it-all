<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

/**
 * Carries a pre-formatted, user-facing failure message for a command runner
 * to print before exiting with Command::FAILURE. Centralizes the blank-line
 * padding these console messages share instead of repeating it at every
 * failure site.
 */
final class CommandFailure extends \RuntimeException
{
    public static function withMessage(string $message): self
    {
        return new self(PHP_EOL . PHP_EOL . $message . PHP_EOL . PHP_EOL);
    }
}
