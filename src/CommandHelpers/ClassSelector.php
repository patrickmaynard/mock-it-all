<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

final class ClassSelector extends AutocompleteSelector
{
    /**
     * @param list<class-string> $classes
     */
    public function __construct(array $classes)
    {
        parent::__construct($classes);
    }

    protected function label(): string
    {
        return 'Class';
    }

    protected function emptyQueryMessage(): string
    {
        return 'Start typing to search classes.';
    }

    protected function noMatchesMessage(): string
    {
        return 'No matching classes.';
    }
}
