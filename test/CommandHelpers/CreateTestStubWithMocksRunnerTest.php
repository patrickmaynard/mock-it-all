<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\CommandHelpers;

use Composer\Autoload\ClassLoader;
use PatrickMaynard\MockItAll\CommandHelpers\CreateTestStubWithMocksRunner;
use PatrickMaynard\MockItAll\Stubs\President;
use PatrickMaynard\MockItAll\Tests\CreatesTempDirectory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use PHPUnit\Framework\TestCase;

/**
 * Drives CreateTestStubWithMocksRunner the same way bin/mock-it-all does:
 * through a Symfony Console Command built via ::configure(), with the
 * runner's ->run() wired up as its code callback. Interactive prompts are
 * driven by writing raw keystrokes to an in-memory stream, exactly like
 * AutocompleteSelectorTest does for the widget itself.
 *
 * Some failure/dump paths in the runner print directly to stdout via
 * print() rather than through the injected OutputInterface (see
 * CreateTestStubWithMocksRunner), so every invocation is wrapped in output
 * buffering to capture that too.
 */
class CreateTestStubWithMocksRunnerTest extends TestCase
{
    use CreatesTempDirectory;

    private string $originalWorkingDirectory;

    protected function setUp(): void
    {
        $this->originalWorkingDirectory = getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);
        $this->removeTempDirectories();
    }

    public function testDirectOptionsWriteTheExpectedTestStubFile(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, $display] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'tests'],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Test stub written to', $display);
        self::assertFileExists($projectRoot . '/tests/PresidentTest.php');
    }

    public function testDirectOptionsProduceExpectedStubContent(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'tests'],
        );

        $expectedOutput = file_get_contents($this->originalWorkingDirectory . '/test/expectedOutput.txt');

        self::assertSame($expectedOutput, file_get_contents($projectRoot . '/tests/PresidentTest.php'));
    }

    public function testDumpOutputWithoutWriteDoesNotCreateAFile(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--dump-output-without-write' => true],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('class YourClassNameTest extends TestCase', $printed);
        self::assertDirectoryDoesNotExist($projectRoot . '/tests');
    }

    public function testFailsWhenNotRunFromAProjectRoot(): void
    {
        $projectRoot = $this->createTempDirectory();
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'tests'],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            'This command must be run from the root of your project',
            $printed,
        );
    }

    public function testFailsOnAnUnresolvableFqcn(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => 'Totally\\Not\\A\\Real\\Class', '--test-folder' => 'tests'],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('was not found or was improperly entered', $printed);
    }

    public function testFailsWhenTestFolderOptionHasADisallowedRootName(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/notatestfolder');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'notatestfolder'],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString(
            'does not start with one of the allowed test folder names',
            $printed,
        );
    }

    public function testFailsWhenTestFolderOptionDoesNotExist(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'tests/DoesNotExist'],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('does not exist', $printed);
    }

    public function testFailsWhenTheTargetStubFileAlreadyExists(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        file_put_contents($projectRoot . '/tests/PresidentTest.php', '<?php // already here');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class, '--test-folder' => 'tests'],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Not overwriting it', $printed);
        self::assertSame('<?php // already here', file_get_contents($projectRoot . '/tests/PresidentTest.php'));
    }

    public function testFailsWhenNoTestFolderExistsAndNotDumping(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            ['--fqcn' => President::class],
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('No "tests" or "test" folder was found', $printed);
    }

    public function testShowClassListAndExitOmitsVendorClassesByDefault(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        chdir($projectRoot);

        $vendorDir = $this->createTempDirectory('mock-it-all-vendor-');
        mkdir($vendorDir . '/SomeLib', 0777, true);
        $vendorClassFile = $vendorDir . '/SomeLib/VendorWidget.php';
        file_put_contents($vendorClassFile, '<?php');

        $projectSrcDir = $this->createTempDirectory('mock-it-all-src-');
        $projectClassFile = $projectSrcDir . '/ProjectThing.php';
        file_put_contents($projectClassFile, '<?php');

        $loader = $this->loaderWithClassMap([]);
        $loader->addClassMap([
            'App\\VendorWidget' => $vendorClassFile,
            'App\\ProjectThing' => $projectClassFile,
        ]);

        [$exitCode, , $printed] = $this->runCommand($loader, $vendorDir, ['--show-class-list-and-exit' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Vendor classes are being omitted.', $printed);
        self::assertStringContainsString('App\\ProjectThing', $printed);
        self::assertStringNotContainsString('App\\VendorWidget', $printed);
    }

    public function testShowClassListAndExitIncludesVendorClassesWhenRequested(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        chdir($projectRoot);

        $vendorDir = $this->createTempDirectory('mock-it-all-vendor-');
        mkdir($vendorDir . '/SomeLib', 0777, true);
        $vendorClassFile = $vendorDir . '/SomeLib/VendorWidget.php';
        file_put_contents($vendorClassFile, '<?php');

        $projectSrcDir = $this->createTempDirectory('mock-it-all-src-');
        $projectClassFile = $projectSrcDir . '/ProjectThing.php';
        file_put_contents($projectClassFile, '<?php');

        $loader = $this->loaderWithClassMap([]);
        $loader->addClassMap([
            'App\\VendorWidget' => $vendorClassFile,
            'App\\ProjectThing' => $projectClassFile,
        ]);

        [$exitCode, , $printed] = $this->runCommand(
            $loader,
            $vendorDir,
            ['--show-class-list-and-exit' => true, '--include-vendor-classes' => true],
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Vendor classes are being included.', $printed);
        self::assertStringContainsString('App\\ProjectThing', $printed);
        self::assertStringContainsString('App\\VendorWidget', $printed);
    }

    public function testInteractiveWizardSelectsClassAndTestFolder(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, $display] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            [],
            "President\r" . "tests\r",
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Generating stub for', $display);
        self::assertStringContainsString('Using test folder', $display);
        self::assertFileExists($projectRoot . '/tests/PresidentTest.php');
    }

    public function testInteractiveWizardCreatesAFreelyTypedDescendantTestFolder(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            [],
            "President\r" . "tests/NewFolder\r",
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertDirectoryExists($projectRoot . '/tests/NewFolder');
        self::assertFileExists($projectRoot . '/tests/NewFolder/PresidentTest.php');
    }

    public function testInteractiveWizardFailsWhenNoClassIsChosen(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            [],
            "\x03",
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('You did not choose a class', $printed);
    }

    public function testInteractiveWizardFailsWhenNoTestFolderIsChosen(): void
    {
        $projectRoot = $this->createTempDirectory();
        file_put_contents($projectRoot . '/composer.json', '{}');
        mkdir($projectRoot . '/tests');
        chdir($projectRoot);

        [$exitCode, , $printed] = $this->runCommand(
            $this->loaderWithClassMap([President::class]),
            false,
            [],
            "President\r" . "\x03",
        );

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('You did not choose a test folder', $printed);
    }

    /**
     * @param list<class-string> $classes
     */
    private function loaderWithClassMap(array $classes): ClassLoader
    {
        $loader = new ClassLoader();

        foreach ($classes as $class) {
            $loader->addClassMap([$class => (new \ReflectionClass($class))->getFileName()]);
        }

        return $loader;
    }

    /**
     * @param array<string, bool|string> $options
     * @return array{0: int, 1: string, 2: string}
     */
    private function runCommand(
        ClassLoader $loader,
        string|false $vendorDir,
        array $options,
        ?string $stdin = null,
    ): array {
        $application = new Application();
        $command = $application->register('create-test-stub-with-mocks');
        CreateTestStubWithMocksRunner::configure($command);
        $command->setCode(function (InputInterface $input, OutputInterface $output) use ($loader, $vendorDir): int {
            $runner = new CreateTestStubWithMocksRunner($loader, $vendorDir);

            return $runner->run($input, $output);
        });

        $input = new ArrayInput($options, $command->getDefinition());

        if ($stdin !== null) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $stdin);
            rewind($stream);
            $input->setStream($stream);
        }

        $output = new BufferedOutput();

        ob_start();
        $exitCode = $command->run($input, $output);
        $printed = ob_get_clean();

        return [$exitCode, $output->fetch(), $printed];
    }
}
