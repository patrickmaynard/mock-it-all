<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TestFolderSelector extends AutocompleteSelector
{
    public const CANDIDATE_ROOT_NAMES = ['tests', 'test', 'testing'];

    /**
     * @param list<string> $folders
     */
    private function __construct(array $folders)
    {
        parent::__construct($folders);
    }

    /**
     * Looks for a "tests" folder and/or a "test" folder directly beneath
     * $projectRoot and, for whichever exist, builds the list of candidate
     * folders: the root folder itself plus every descendant directory, so
     * the user can autocomplete something like "tests/Integration/Service".
     *
     * Returns null when neither folder exists.
     */
    public static function forProjectRoot(string $projectRoot): ?self
    {
        $folders = [];

        foreach (self::CANDIDATE_ROOT_NAMES as $rootName) {
            $rootPath = $projectRoot . DIRECTORY_SEPARATOR . $rootName;

            if (!is_dir($rootPath)) {
                continue;
            }

            $folders[] = $rootName;

            foreach (self::descendantDirectories($rootPath) as $relative) {
                $folders[] = $rootName . '/' . $relative;
            }
        }

        if ($folders === []) {
            return null;
        }

        sort($folders);

        return new self($folders);
    }

    /**
     * @return list<string>
     */
    private static function descendantDirectories(string $rootPath): array
    {
        $directories = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isDir()) {
                continue;
            }

            $relative = substr($fileInfo->getPathname(), strlen($rootPath) + 1);
            $directories[] = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        }

        return $directories;
    }

    protected function label(): string
    { 
        $stringified = '';
        foreach (self::CANDIDATE_ROOT_NAMES as $index => $name) {
            if ($index === count(self::CANDIDATE_ROOT_NAMES) - 1) {
                $stringified .= 'or ' . $name;
            } else {
                $stringified .= $name . ', ';
            }
        }
        return "Test folder (must begin with folder name $stringified)";
    }

    protected function emptyQueryMessage(): string
    {
        return 'Start typing to search test folders.';
    }

    protected function noMatchesMessage(): string
    {
        return 'No matching test folders.';
    }
}
