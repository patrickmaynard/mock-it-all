<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\CommandHelpers;

use PatrickMaynard\MockItAll\CommandHelpers\AutocompleteSelector;
use PatrickMaynard\MockItAll\CommandHelpers\TestFolderSelector;
use PatrickMaynard\MockItAll\Tests\CreatesTempDirectory;
use PHPUnit\Framework\TestCase;

class TestFolderSelectorTest extends TestCase
{
    use CreatesTempDirectory;

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
    }

    public function testReturnsNullWhenNoCandidateFolderExists(): void
    {
        $projectRoot = $this->createTempDirectory();

        self::assertNull(TestFolderSelector::forProjectRoot($projectRoot));
    }

    public function testListsTheRootFolderItselfWhenItHasNoDescendants(): void
    {
        $projectRoot = $this->createTempDirectory();
        mkdir($projectRoot . '/tests');

        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(['tests'], $this->itemsOf($selector));
    }

    public function testListsDescendantDirectoriesSorted(): void
    {
        $projectRoot = $this->createTempDirectory();
        mkdir($projectRoot . '/tests/Unit', 0777, true);
        mkdir($projectRoot . '/tests/Integration/Foo', 0777, true);

        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(
            ['tests', 'tests/Integration', 'tests/Integration/Foo', 'tests/Unit'],
            $this->itemsOf($selector),
        );
    }

    public function testCombinesAndSortsMultipleCandidateRoots(): void
    {
        $projectRoot = $this->createTempDirectory();
        mkdir($projectRoot . '/tests');
        mkdir($projectRoot . '/test/Feature', 0777, true);

        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(
            ['test', 'test/Feature', 'tests'],
            $this->itemsOf($selector),
        );
    }

    public function testIgnoresCandidateNamesThatAreNotExactRootMatches(): void
    {
        $projectRoot = $this->createTempDirectory();
        // "testing-fixtures" is not one of the exact candidate root names,
        // so it should not be picked up as its own root.
        mkdir($projectRoot . '/testing-fixtures');
        mkdir($projectRoot . '/testing');

        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(['testing'], $this->itemsOf($selector));
    }

    public function testLabelListsAllCandidateRootNames(): void
    {
        $projectRoot = $this->createTempDirectory();
        mkdir($projectRoot . '/tests');
        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(
            'Test folder (must begin with folder name tests, test, or testing)',
            $this->callProtected($selector, 'label'),
        );
    }

    public function testEmptyQueryAndNoMatchesMessages(): void
    {
        $projectRoot = $this->createTempDirectory();
        mkdir($projectRoot . '/tests');
        $selector = TestFolderSelector::forProjectRoot($projectRoot);

        self::assertNotNull($selector);
        self::assertSame(
            'Start typing to search test folders.',
            $this->callProtected($selector, 'emptyQueryMessage'),
        );
        self::assertSame(
            'No matching test folders.',
            $this->callProtected($selector, 'noMatchesMessage'),
        );
    }

    /**
     * @return list<string>
     */
    private function itemsOf(TestFolderSelector $selector): array
    {
        $property = new \ReflectionProperty(AutocompleteSelector::class, 'items');
        $property->setAccessible(true);

        return $property->getValue($selector);
    }

    private function callProtected(object $object, string $method): mixed
    {
        $reflectionMethod = new \ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke($object);
    }
}
