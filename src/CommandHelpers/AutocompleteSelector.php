<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shared terminal autocomplete widget: draws a prompt, filters a fixed list
 * of string candidates as the user types, and lets them pick one with the
 * arrow keys. Subclasses just supply the candidate list and the label
 * printed in front of the prompt.
 */
abstract class AutocompleteSelector
{
    private const MAX_VISIBLE_LINES = 10;

    /**
     * @param list<string> $items
     */
    public function __construct(
        private readonly array $items,
    ) {
    }

    abstract protected function label(): string;

    /**
     * Message shown before the user has typed anything.
     */
    protected function emptyQueryMessage(): string
    {
        return 'Start typing to search.';
    }

    protected function noMatchesMessage(): string
    {
        return 'No matches.';
    }

    public function ask(
        InputInterface $input,
        OutputInterface $output,
    ): ?string {
        $stream = ($input instanceof StreamableInputInterface ? $input->getStream() : null)
            ?? STDIN;

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
                    $this->finishDisplay($output);

                    return null;
                }

                // Ctrl-C
                if ($key === "\x03") {
                    $this->finishDisplay($output);

                    return null;
                }

                // Enter
                if ($key === "\n" || $key === "\r") {
                    $this->finishDisplay($output);

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
     * @return list<string>
     */
    private function filter(string $query): array
    {
        // Require the user to type something before showing any
        // suggestions, rather than dumping an arbitrary slice of items
        // up front.
        if ($query === '') {
            return [];
        }

        $query = strtolower($query);

        return array_values(array_filter(
            $this->items,
            static fn (string $item): bool =>
                str_contains(strtolower($item), $query),
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
     * @param list<string> $matches
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

        $label = $this->label();

        $output->write("\033[2K\r");
        $output->write($label . ': ' . $query);

        // Erase everything below the cursor before redrawing the match
        // list. Without this, a shorter item name (or a shorter list)
        // leaves stray characters/lines from the previous render behind,
        // e.g. "FryCook" rendering as "FryCookOfCommerce" after
        // "SecretaryOfCommerce" was previously drawn on that row.
        $output->write("\033[0J");

        $output->write("\n");

        if ($matches === []) {
            $message = $query === ''
                ? '<comment>' . $this->emptyQueryMessage() . '</comment>'
                : '<error>' . $this->noMatchesMessage() . '</error>';
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

            foreach ($visible as $index => $item) {
                if ($index === $selected) {
                    $output->writeln("  \033[7m$item\033[0m");
                } else {
                    $output->writeln("  $item");
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
        $output->write($label . ': ' . $query);
    }

    /**
     * The last redraw() left the cursor sitting right after the label/query
     * text, with the match list still drawn on the lines below it (cursor
     * moved back up so the next keystroke can redraw in place). Clear that
     * leftover list and drop to a fresh line so whatever the caller prints
     * next doesn't get interleaved with stale rows.
     */
    private function finishDisplay(OutputInterface $output): void
    {
        $output->write("\033[0J");
        $output->write("\n");
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
