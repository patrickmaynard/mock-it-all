<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClassSelector
{
    private const MAX_VISIBLE_LINES = 10;

    /**
     * @param list<class-string> $classes
     */
    public function __construct(
        private readonly array $classes,
    ) {
    }

    /**
     * @return class-string|null
     */
    public function ask(
        InputInterface $input,
        OutputInterface $output,
    ): ?string {
        $stream = $input instanceof StreamableInputInterface
            ? $input->getStream()
            : STDIN;

        $query = '';
        $selected = 0;
        $scrollOffset = 0;

        $matches = $this->filter($query);

        // Put the terminal into character-at-a-time mode.
        $this->setRawMode();

        // Draw the prompt and the initial list of matches before reading
        // any input, so the user can never hit Enter before seeing what
        // they're about to select.
        $this->redraw($output, $query, $matches, $selected, $scrollOffset);

        try {
            while (true) {
                $key = fread($stream, 1);

                if ($key === false || $key === '') {
                    return null;
                }

                // Ctrl-C
                if ($key === "\x03") {
                    return null;
                }

                // Enter
                if ($key === "\n" || $key === "\r") {
                    return $matches[$selected] ?? ($query !== '' ? $query : null);
                }

                // Backspace
                if ($key === "\x7f" || $key === "\x08") {
                    if ($query !== '') {
                        $query = substr($query, 0, -1);
                        $selected = 0;
                        $scrollOffset = 0;
                        $matches = $this->filter($query);
                    }

                    $this->redraw($output, $query, $matches, $selected, $scrollOffset);
                    continue;
                }

                // Escape sequence (arrow keys etc.)
                if ($key === "\x1b") {
                    $key .= fread($stream, 2);

                    if ($key === "\x1b[A") {
                        $selected = max(0, $selected - 1);
                    } elseif ($key === "\x1b[B") {
                        $selected = min(
                            max(0, count($matches) - 1),
                            $selected + 1,
                        );
                    }

                    $scrollOffset = $this->clampScrollOffset(
                        $selected,
                        $scrollOffset,
                        count($matches),
                    );

                    $this->redraw($output, $query, $matches, $selected, $scrollOffset);
                    continue;
                }

                // Ignore other control characters.
                if (ord($key) < 32) {
                    continue;
                }

                $query .= $key;
                $selected = 0;
                $scrollOffset = 0;

                $matches = $this->filter($query);

                $this->redraw($output, $query, $matches, $selected, $scrollOffset);
            }
        } finally {
            $this->restoreTerminal();
        }
    }

    /**
     * @return list<class-string>
     */
    private function filter(string $query): array
    {
        // Require the user to type something before showing any
        // suggestions, rather than dumping an arbitrary slice of classes
        // up front.
        if ($query === '') {
            return [];
        }

        $query = strtolower($query);

        return array_values(array_filter(
            $this->classes,
            static fn (string $class): bool =>
                str_contains(strtolower($class), $query),
        ));
    }

    /**
     * Keeps $selected within the visible window, scrolling the minimum
     * amount necessary rather than re-centering, so the list only moves
     * once the selection reaches the top or bottom edge.
     */
    private function clampScrollOffset(
        int $selected,
        int $scrollOffset,
        int $totalMatches,
    ): int {
        if ($selected < $scrollOffset) {
            $scrollOffset = $selected;
        } elseif ($selected >= $scrollOffset + self::MAX_VISIBLE_LINES) {
            $scrollOffset = $selected - self::MAX_VISIBLE_LINES + 1;
        }

        $maxScrollOffset = max(0, $totalMatches - self::MAX_VISIBLE_LINES);

        return max(0, min($scrollOffset, $maxScrollOffset));
    }

    /**
     * @param list<class-string> $matches
     */
    private function redraw(
        OutputInterface $output,
        string $query,
        array $matches,
        int $selected,
        int $scrollOffset,
    ): void {
        // For a first implementation, clear the previous output and
        // redraw the entire widget.
        //
        // In production I'd make this more sophisticated and track
        // exactly how many terminal lines were rendered.

        $output->write("\033[2K\r");
        $output->write('Class: ' . $query);

        // Erase everything below the cursor before redrawing the match
        // list. Without this, a shorter class name (or a shorter list)
        // leaves stray characters/lines from the previous render behind,
        // e.g. "FryCook" rendering as "FryCookOfCommerce" after
        // "SecretaryOfCommerce" was previously drawn on that row.
        $output->write("\033[0J");

        $output->write("\n");

        if ($matches === []) {
            $message = $query === ''
                ? '<comment>Start typing to search classes.</comment>'
                : '<error>No matching classes.</error>';
            $output->writeln($message);
            $lines = 2;
        } else {
            // Cap the rendered list at MAX_VISIBLE_LINES so a long match
            // list can never exceed a fixed height; the rest scrolls into
            // view as the selection moves past either edge.
            $visible = array_slice(
                $matches,
                $scrollOffset,
                self::MAX_VISIBLE_LINES,
                preserve_keys: true,
            );

            $writtenLines = 0;

            if ($scrollOffset > 0) {
                $output->writeln("  <comment>↑ {$scrollOffset} more above</comment>");
                $writtenLines++;
            }

            foreach ($visible as $index => $class) {
                if ($index === $selected) {
                    $output->writeln("  \033[7m$class\033[0m");
                } else {
                    $output->writeln("  $class");
                }

                $writtenLines++;
            }

            $hiddenBelow = count($matches) - ($scrollOffset + count($visible));

            if ($hiddenBelow > 0) {
                $output->writeln("  <comment>↓ {$hiddenBelow} more below</comment>");
                $writtenLines++;
            }

            $lines = $writtenLines + 1;
        }

        // Move cursor back to the input line.
        $output->write("\033[{$lines}A");
        $output->write("\r");
        $output->write('Class: ' . $query);
    }

    private function setRawMode(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows requires a different mechanism. See below.
            return;
        }

        shell_exec('stty -icanon -echo');
    }

    private function restoreTerminal(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        shell_exec('stty sane');
    }
}
