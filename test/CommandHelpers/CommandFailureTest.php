<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\CommandHelpers;

use PatrickMaynard\MockItAll\CommandHelpers\CommandFailure;
use PHPUnit\Framework\TestCase;

class CommandFailureTest extends TestCase
{
    public function testWithMessagePadsMessageWithBlankLines(): void
    {
        $failure = CommandFailure::withMessage('Something went wrong.');

        self::assertSame(
            PHP_EOL . PHP_EOL . 'Something went wrong.' . PHP_EOL . PHP_EOL,
            $failure->getMessage(),
        );
    }

    public function testItIsARuntimeException(): void
    {
        $failure = CommandFailure::withMessage('Boom.');

        self::assertInstanceOf(\RuntimeException::class, $failure);
    }

    public function testItCanBeThrownAndCaught(): void
    {
        try {
            throw CommandFailure::withMessage('Caught me.');
        } catch (CommandFailure $failure) {
            self::assertStringContainsString('Caught me.', $failure->getMessage());

            return;
        }

        self::fail('Expected CommandFailure to be thrown.');
    }
}
