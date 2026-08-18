<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\CommandHelpers;

use PatrickMaynard\MockItAll\CommandHelpers\ClassSelector;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the shared interactive behavior in AutocompleteSelector via its
 * concrete ClassSelector subclass. Keystrokes are fed through an in-memory
 * stream on a StreamableInputInterface, mirroring how a real terminal would
 * deliver them one byte at a time.
 */
class AutocompleteSelectorTest extends TestCase
{
    private const ITEMS = [
        'Foo\\Bar\\President',
        'Foo\\Bar\\FryCook',
        'Foo\\Bar\\SecretaryOfCommerce',
        'Foo\\Bar\\SecretaryOfState',
        'Foo\\Bar\\SecretaryOfDefense',
    ];

    public function testReturnsNullWhenInputEndsBeforeAnyKeyIsPressed(): void
    {
        self::assertNull($this->ask(self::ITEMS, ''));
    }

    public function testReturnsNullOnCtrlC(): void
    {
        self::assertNull($this->ask(self::ITEMS, "\x03"));
    }

    public function testTypingAUniqueMatchThenEnterSelectsIt(): void
    {
        self::assertSame(
            'Foo\\Bar\\President',
            $this->ask(self::ITEMS, "President\r"),
        );
    }

    public function testEnterWithNoMatchesReturnsTheTypedQuery(): void
    {
        self::assertSame('Zzz', $this->ask(self::ITEMS, "Zzz\r"));
    }

    public function testEnterWithNoQueryAndNoMatchesReturnsNull(): void
    {
        // Backspacing back down to an empty query, then hitting Enter with
        // no candidate selected, should behave like never having typed
        // anything.
        self::assertNull($this->ask(self::ITEMS, "P\x7f\r"));
    }

    public function testArrowDownMovesTheSelectionThroughMultipleMatches(): void
    {
        // "Secretary" matches Commerce, State and Defense, in that order.
        // Pressing down twice should move the selection onto the third one.
        self::assertSame(
            'Foo\\Bar\\SecretaryOfDefense',
            $this->ask(self::ITEMS, "Secretary\x1b[B\x1b[B\r"),
        );
    }

    public function testArrowDownDoesNotOverrunTheLastMatch(): void
    {
        // Only two matches; pressing down three times should clamp at the
        // last one instead of wrapping or erroring.
        self::assertSame(
            'Foo\\Bar\\FryCook',
            $this->ask(self::ITEMS, "Fry\x1b[B\x1b[B\x1b[B\r"),
        );
    }

    public function testArrowUpDoesNotGoAboveTheFirstMatch(): void
    {
        self::assertSame(
            'Foo\\Bar\\SecretaryOfCommerce',
            $this->ask(self::ITEMS, "Secretary\x1b[A\x1b[A\r"),
        );
    }

    public function testBackspaceReFiltersTheMatchList(): void
    {
        // "Frx" has no matches; backspacing to "Fr" narrows it back down to
        // the single FryCook match.
        self::assertSame(
            'Foo\\Bar\\FryCook',
            $this->ask(self::ITEMS, "Frx\x7f\r"),
        );
    }

    public function testBackspaceOnEmptyQueryIsANoOp(): void
    {
        // Backspacing before typing anything shouldn't error or consume
        // more input than it should.
        self::assertSame(
            'Foo\\Bar\\President',
            $this->ask(self::ITEMS, "\x7fPresident\r"),
        );
    }

    public function testRedrawShowsTheLabelBeforeAnyKeyIsPressed(): void
    {
        $output = new BufferedOutput();
        $this->ask(self::ITEMS, '', $output);

        self::assertStringContainsString('Class: ', $output->fetch());
    }

    public function testEmptyQueryMessageIsShownBeforeTyping(): void
    {
        $output = new BufferedOutput();
        $this->ask(self::ITEMS, '', $output);

        self::assertStringContainsString('Start typing to search classes.', $output->fetch());
    }

    public function testNoMatchesMessageIsShownForAnUnmatchedQuery(): void
    {
        $output = new BufferedOutput();
        $this->ask(self::ITEMS, "Zzz\r", $output);

        self::assertStringContainsString('No matching classes.', $output->fetch());
    }

    public function testLongMatchListsAreCappedAndScrollable(): void
    {
        $items = array_map(static fn (int $n): string => sprintf('Foo\\Bar\\Item%02d', $n), range(1, 15));

        $output = new BufferedOutput();
        // "Item" matches all 15 candidates. Before any navigation, the
        // first 10 should render with an indicator for the 5 that don't fit.
        $this->ask($items, "Item", $output);

        $rendered = $output->fetch();
        self::assertStringContainsString('Item01', $rendered);
        self::assertStringContainsString('Item10', $rendered);
        self::assertStringNotContainsString('Item11', $rendered);
        self::assertStringContainsString('5 more below', $rendered);

        // Scrolling past the bottom edge (11 downs from the first match)
        // should bring the list's tail into view and show what's above it.
        $output = new BufferedOutput();
        $this->ask($items, "Item" . str_repeat("\x1b[B", 11) . "\r", $output);

        self::assertStringContainsString('more above', $output->fetch());
    }

    private function ask(array $items, string $keystrokes, ?BufferedOutput $output = null): ?string
    {
        $selector = new ClassSelector($items);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $keystrokes);
        rewind($stream);

        $input = new ArrayInput([]);
        $input->setStream($stream);

        return $selector->ask($input, $output ?? new BufferedOutput());
    }
}
