<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests;

/**
 * Shared helper for tests that need a throwaway directory tree on disk
 * (e.g. to stand in for a project root). Every directory handed out is
 * tracked so removeTempDirectories() can clean it all up in tearDown().
 */
trait CreatesTempDirectory
{
    /** @var list<string> */
    private array $tempDirectoriesToClean = [];

    private function createTempDirectory(string $prefix = 'mock-it-all-test-'): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->tempDirectoriesToClean[] = $directory;

        return $directory;
    }

    private function removeTempDirectories(): void
    {
        foreach ($this->tempDirectoriesToClean as $directory) {
            $this->removeDirectoryRecursively($directory);
        }

        $this->tempDirectoriesToClean = [];
    }

    private function removeDirectoryRecursively(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeDirectoryRecursively($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
