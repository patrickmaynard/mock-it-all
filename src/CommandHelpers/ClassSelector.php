<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ClassSelector
{
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

        $matches = $this->filter($query);

        // Put the terminal into character-at-a-time mode.
        $this->setRawMode();

        // Draw the prompt and the initial list of matches before reading
        // any input, so the user can never hit Enter before seeing what
        // they're about to select.
        $this->redraw($output, $query, $matches, $selected);

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
                    }

                    $this->redraw($output, $query, $matches, $selected);
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

                    $this->redraw($output, $query, $matches, $selected);
                    continue;
                }

                // Ignore other control characters.
                if (ord($key) < 32) {
                    continue;
                }

                $query .= $key;
                $selected = 0;

                $matches = $this->filter($query);

                $this->redraw($output, $query, $matches, $selected);
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
        if ($query === '') {
            return array_slice($this->classes, 0, 10);
        }

        $query = strtolower($query);

        return array_values(array_filter(
            $this->classes,
            static fn (string $class): bool =>
                str_contains(strtolower($class), $query),
        ));
    }

    /**
     * @param list<class-string> $matches
     */
    private function redraw(
        OutputInterface $output,
        string $query,
        array $matches,
        int $selected,
    ): void {
        // For a first implementation, clear the previous output and
        // redraw the entire widget.
        //
        // In production I'd make this more sophisticated and track
        // exactly how many terminal lines were rendered.

        $output->write("\033[2K\r");
        $output->write('Class: ' . $query);

        if ($matches === []) {
            $output->write("\n<error>No matching classes.</error>");
            return;
        }

        $output->write("\n");

        foreach ($matches as $index => $class) {
            if ($index === $selected) {
                $output->writeln("  \033[7m$class\033[0m");
            } else {
                $output->writeln("  $class");
            }
        }

        // Move cursor back to the input line.
        $lines = count($matches) + 1;

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
