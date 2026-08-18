<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

/**
 * Works out what namespace a generated test stub should declare.
 *
 * Preferred source of truth is the host project's own composer.json PSR-4
 * mapping (autoload-dev, falling back to autoload), since that's what
 * actually determines whether the generated file will autoload correctly.
 * When no mapping covers the chosen test folder (or there's no folder at
 * all, as with --dump-output-without-write), falls back to a namespace
 * mirroring the mocked class's own namespace with a "Tests" segment
 * inserted, matching this library's own test/src convention.
 */
final class NamespaceResolver
{
    public static function forTestFolder(string $projectRoot, string $relativeTestFolder, string $classNameToMock): string
    {
        return self::psr4NamespaceForFolder($projectRoot, $relativeTestFolder)
            ?? self::mirroredNamespace($classNameToMock);
    }

    public static function mirroredNamespace(string $classNameToMock): string
    {
        $lastBackslashPosition = strrpos($classNameToMock, '\\');

        if ($lastBackslashPosition === false) {
            return 'Tests';
        }

        $segments = explode('\\', substr($classNameToMock, 0, $lastBackslashPosition));
        array_splice($segments, min(2, count($segments)), 0, 'Tests');

        return implode('\\', $segments);
    }

    private static function psr4NamespaceForFolder(string $projectRoot, string $relativeTestFolder): ?string
    {
        $composerJsonPath = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composerJsonPath)) {
            return null;
        }

        $composerJson = json_decode((string) file_get_contents($composerJsonPath), true);

        if (!is_array($composerJson)) {
            return null;
        }

        $relativeTestFolder = str_replace('\\', '/', trim($relativeTestFolder, '/\\'));

        $bestNamespace = null;
        $bestMatchLength = -1;

        foreach (['autoload-dev', 'autoload'] as $section) {
            $psr4 = $composerJson[$section]['psr-4'] ?? null;

            if (!is_array($psr4)) {
                continue;
            }

            foreach ($psr4 as $namespacePrefix => $mappedDirs) {
                foreach ((array) $mappedDirs as $mappedDir) {
                    $match = self::matchFolderAgainstMappedDir($relativeTestFolder, (string) $mappedDir);

                    if ($match === null || $match['matchLength'] <= $bestMatchLength) {
                        continue;
                    }

                    $bestMatchLength = $match['matchLength'];
                    $bestNamespace = rtrim((string) $namespacePrefix, '\\')
                        . ($match['extraSegments'] === [] ? '' : '\\' . implode('\\', $match['extraSegments']));
                }
            }
        }

        return $bestNamespace;
    }

    /**
     * @return array{matchLength: int, extraSegments: list<string>}|null
     */
    private static function matchFolderAgainstMappedDir(string $relativeTestFolder, string $mappedDir): ?array
    {
        $mappedDir = str_replace('\\', '/', trim($mappedDir, '/\\'));

        if ($mappedDir === '') {
            return [
                'matchLength' => 0,
                'extraSegments' => $relativeTestFolder === '' ? [] : explode('/', $relativeTestFolder),
            ];
        }

        if ($mappedDir === $relativeTestFolder) {
            return ['matchLength' => strlen($mappedDir), 'extraSegments' => []];
        }

        if (str_starts_with($relativeTestFolder . '/', $mappedDir . '/')) {
            $extra = substr($relativeTestFolder, strlen($mappedDir) + 1);

            return [
                'matchLength' => strlen($mappedDir),
                'extraSegments' => $extra === '' ? [] : explode('/', $extra),
            ];
        }

        return null;
    }
}
