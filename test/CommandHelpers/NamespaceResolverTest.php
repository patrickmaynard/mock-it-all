<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\CommandHelpers;

use PatrickMaynard\MockItAll\CommandHelpers\NamespaceResolver;
use PatrickMaynard\MockItAll\Stubs\President;
use PatrickMaynard\MockItAll\Tests\CreatesTempDirectory;
use PHPUnit\Framework\TestCase;

class NamespaceResolverTest extends TestCase
{
    use CreatesTempDirectory;

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
    }

    public function testUsesAutoloadDevPsr4MappingForAnExactFolderMatch(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload-dev' => ['psr-4' => ['App\\Tests\\' => 'tests/']],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'tests', President::class);

        self::assertSame('App\Tests', $namespace);
    }

    public function testUsesAutoloadDevPsr4MappingForADescendantFolder(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload-dev' => ['psr-4' => ['App\\Tests\\' => 'tests/']],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'tests/Unit/Service', President::class);

        self::assertSame('App\Tests\Unit\Service', $namespace);
    }

    public function testFallsBackToAutoloadPsr4MappingWhenAutoloadDevDoesNotMatch(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload' => ['psr-4' => ['App\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['App\\Tests\\' => 'tests/']],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'src/Service', President::class);

        self::assertSame('App\Service', $namespace);
    }

    public function testPrefersTheLongestMatchingPsr4Mapping(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload-dev' => ['psr-4' => [
                'App\\Tests\\' => 'tests/',
                'App\\Tests\\Integration\\' => 'tests/Integration/',
            ]],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'tests/Integration/Service', President::class);

        self::assertSame('App\Tests\Integration\Service', $namespace);
    }

    public function testMatchesARootMappedPsr4Directory(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload-dev' => ['psr-4' => ['App\\' => '']],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'tests/Unit', President::class);

        self::assertSame('App\tests\Unit', $namespace);
    }

    public function testFallsBackToAMirroredNamespaceWhenNoPsr4MappingCoversTheFolder(): void
    {
        $projectRoot = $this->createTempDirectory();
        $this->putComposerJson($projectRoot, [
            'autoload-dev' => ['psr-4' => ['App\\Tests\\' => 'tests/']],
        ]);

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'spec', President::class);

        self::assertSame('PatrickMaynard\MockItAll\Tests\Stubs', $namespace);
    }

    public function testFallsBackToAMirroredNamespaceWhenThereIsNoComposerJson(): void
    {
        $projectRoot = $this->createTempDirectory();

        $namespace = NamespaceResolver::forTestFolder($projectRoot, 'tests', President::class);

        self::assertSame('PatrickMaynard\MockItAll\Tests\Stubs', $namespace);
    }

    public function testMirroredNamespaceInsertsTestsAfterTheFirstTwoSegments(): void
    {
        self::assertSame(
            'PatrickMaynard\MockItAll\Tests\Stubs',
            NamespaceResolver::mirroredNamespace(President::class),
        );
    }

    public function testMirroredNamespaceAppendsTestsWhenThereAreFewerThanTwoSegments(): void
    {
        self::assertSame('App\Tests', NamespaceResolver::mirroredNamespace('App\Widget'));
    }

    public function testMirroredNamespaceIsJustTestsForAGloballyNamespacedClass(): void
    {
        self::assertSame('Tests', NamespaceResolver::mirroredNamespace('Widget'));
    }

    /**
     * @param array<string, mixed> $composerJson
     */
    private function putComposerJson(string $projectRoot, array $composerJson): void
    {
        file_put_contents($projectRoot . '/composer.json', json_encode($composerJson, JSON_THROW_ON_ERROR));
    }
}
