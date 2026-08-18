<?php

declare(strict_types=1);

namespace PatrickMaynard\MockItAll\CommandHelpers;

use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use PatrickMaynard\MockItAll\MockLogicCreator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the "create-test-stub-with-mocks" logic given an already-configured
 * InputInterface/OutputInterface. Deliberately holds no dependency on how
 * the command is wired up, so both the standalone bin/mock-it-all script
 * and (eventually) a Command class registered with a host Symfony
 * application can share this same implementation.
 */
final class CreateTestStubWithMocksRunner
{
    private readonly MockLogicCreator $mockLogicCreator;

    /**
     * @param string|false $vendorDir Absolute path to the consuming
     *   project's vendor/ directory, or false if it could not be resolved.
     *   Used only to decide which autocomplete classes count as "vendor".
     */
    public function __construct(
        private readonly ClassLoader $loader,
        private readonly string|false $vendorDir,
    ) {
        $this->mockLogicCreator = new MockLogicCreator();
    }

    /**
     * Adds this command's options to $command. Shared so that every host of
     * this logic (the standalone bin script today, a Symfony-integrated
     * command later) exposes the exact same CLI surface.
     */
    public static function configure(Command $command): void
    {
        $command
            ->addOption('show-class-list-and-exit', null, InputOption::VALUE_NONE, 'Just show the class list?')
            ->addOption('include-vendor-classes', null, InputOption::VALUE_NONE, 'Include vendor classes?')
            ->addOption('dump-output-without-write', null, InputOption::VALUE_NONE, 'Dump logic direct to stdout?')
            ->addOption('fqcn', null, InputOption::VALUE_REQUIRED, 'The fully-qualified class name to generate a test stub for. Omit to pick one interactively.')
            ->addOption('test-folder', null, InputOption::VALUE_REQUIRED, 'The folder (relative to the project root) that should hold the generated test stub. Omit to pick one interactively.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            $includeVendorClasses = (bool) $input->getOption('include-vendor-classes');
            $classesForAutoComplete = $this->buildAutocompleteClassList($includeVendorClasses);

            if ($input->getOption('show-class-list-and-exit')) {
                $this->printClassList($includeVendorClasses, $classesForAutoComplete);

                return Command::SUCCESS;
            }

            $projectRoot = $this->resolveProjectRoot();

            $fqcn = $this->resolveFqcn($input, $output, $classesForAutoComplete);

            $dumpOutputWithoutWrite = (bool) $input->getOption('dump-output-without-write');

            // A test folder is only needed when we're about to write a file
            // there, so skip resolving (and, for the wizard, asking about) one
            // entirely when just dumping the stub to stdout. Without a folder
            // to work from, the namespace falls back to a mirrored guess
            // rather than one derived from the project's PSR-4 mapping.
            if ($dumpOutputWithoutWrite) {
                $testFolderPath = '';
                $namespace = NamespaceResolver::mirroredNamespace($fqcn);
            } else {
                [$testFolder, $testFolderPath] = $this->resolveTestFolder($input, $output, $projectRoot);
                $namespace = NamespaceResolver::forTestFolder($projectRoot, $testFolder, $fqcn);
            }

            $stubLogic = $this->generateStub($fqcn, $namespace);

            $this->writeOrDumpStub(
                $output,
                $stubLogic,
                $fqcn,
                $testFolderPath,
                $dumpOutputWithoutWrite,
            );

            return Command::SUCCESS;
        } catch (CommandFailure $failure) {
            print $failure->getMessage();

            return Command::FAILURE;
        }
    }

    /**
     * Populates the list of classes that can be autocompleted.
     *
     * getClassMap() only returns classes registered via Composer's classmap
     * autoloading. Project classes autoloaded via PSR-4 (like this
     * library's own src/ classes) are resolved lazily and never appear
     * there unless the autoloader was dumped with
     * --optimize/--classmap-authoritative. So we also walk the loader's
     * registered PSR-4 prefixes ourselves to pick up project classes
     * regardless of how the autoloader was dumped.
     *
     * @return list<class-string>
     */
    private function buildAutocompleteClassList(bool $includeVendorClasses): array
    {
        $classMap = $this->loader->getClassMap();

        foreach ($this->loader->getPrefixesPsr4() as $namespacePrefix => $directories) {
            foreach ($directories as $directory) {
                $directory = rtrim($directory, '/');
                if (!is_dir($directory)) {
                    continue;
                }

                $files = new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                    ),
                    '/\.php$/',
                );

                foreach ($files as $file) {
                    $relativePath = substr($file->getPathname(), strlen($directory) + 1);
                    $relativeClass = substr($relativePath, 0, -4); // strip ".php"
                    $className = $namespacePrefix . str_replace('/', '\\', $relativeClass);
                    $classMap[$className] = $file->getPathname();
                }
            }
        }

        $classesForAutoComplete = [];
        foreach ($classMap as $className => $path) {
            $realPath = realpath($path) ?: $path;
            $isVendorClass = $this->vendorDir !== false
                && str_starts_with($realPath, $this->vendorDir . DIRECTORY_SEPARATOR);

            if (!$isVendorClass || $includeVendorClasses) {
                $classesForAutoComplete[] = $className;
            }
        }

        return $classesForAutoComplete;
    }

    /**
     * @param list<class-string> $classesForAutoComplete
     */
    private function printClassList(bool $includeVendorClasses, array $classesForAutoComplete): void
    {
        print PHP_EOL .
            ($includeVendorClasses ? 'Vendor classes are being included. ' : 'Vendor classes are being omitted. ') .
            'Here is a list of available classes: ' .
            PHP_EOL .
            PHP_EOL .
            implode(PHP_EOL, $classesForAutoComplete) .
            PHP_EOL .
            PHP_EOL;
    }

    /**
     * Both the wizard and the direct (--fqcn + --test-folder) path need to
     * know where to write the generated stub, which means we need to be
     * run from the root of a project (the folder containing composer.json).
     */
    private function resolveProjectRoot(): string
    {
        $projectRoot = getcwd();

        if ($projectRoot === false || !is_file($projectRoot . '/composer.json')) {
            throw CommandFailure::withMessage(
                'This command must be run from the root of your project ' .
                '(the folder containing composer.json). Exiting. ',
            );
        }

        return $projectRoot;
    }

    /**
     * @param list<class-string> $classesForAutoComplete
     */
    private function resolveFqcn(
        InputInterface $input,
        OutputInterface $output,
        array $classesForAutoComplete,
    ): string {
        $fqcnOption = $input->getOption('fqcn');

        if ($fqcnOption !== null && $fqcnOption !== '') {
            return $fqcnOption;
        }

        $selector = new ClassSelector($classesForAutoComplete);
        $class = $selector->ask($input, $output);

        if ($class === null || $class === '') {
            throw CommandFailure::withMessage('You did not choose a class. Exiting. ');
        }

        $output->writeln('');
        $output->writeln(sprintf('Generating stub for <info>%s</info>.', $class));

        return $class;
    }

    /**
     * Resolves which folder the stub should be written to, either via the
     * interactive selector (when --test-folder was omitted) or by
     * validating the --test-folder option directly. Only called when we're
     * actually about to write a file (i.e. not --dump-output-without-write).
     *
     * @return array{0: string, 1: string} [$testFolder (relative to
     *   $projectRoot), $testFolderPath (absolute)]
     */
    private function resolveTestFolder(
        InputInterface $input,
        OutputInterface $output,
        string $projectRoot,
    ): array {
        $testFolderOption = $input->getOption('test-folder');

        if ($testFolderOption === null || $testFolderOption === '') {
            $testFolderSelector = TestFolderSelector::forProjectRoot($projectRoot);

            if ($testFolderSelector === null) {
                throw CommandFailure::withMessage(
                    'No "tests" or "test" folder was found. ' .
                    'Please create one before using this command. ',
                );
            }

            $testFolder = $testFolderSelector->ask($input, $output);

            if ($testFolder === null || $testFolder === '') {
                throw CommandFailure::withMessage('You did not choose a test folder. Exiting. ');
            }

            $output->writeln('');
            $output->writeln(sprintf('Using test folder <info>%s</info>.', $testFolder));

            $testFolderPath = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $testFolder);

            return [$testFolder, $testFolderPath];
        }

        $testFolder = $testFolderOption;
        $testFolderRootSegment = explode('/', str_replace('\\', '/', trim($testFolder, '/\\')))[0];

        if (!in_array($testFolderRootSegment, TestFolderSelector::CANDIDATE_ROOT_NAMES, true)) {
            throw CommandFailure::withMessage(
                'The --test-folder "' . $testFolder . '" does not start with one of the ' .
                'allowed test folder names (' . implode(', ', TestFolderSelector::CANDIDATE_ROOT_NAMES) . '). ' .
                'Please choose (or create) a folder beginning with one of those names. ',
            );
        }

        $testFolderPath = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $testFolder);

        if (!is_dir($testFolderPath)) {
            throw CommandFailure::withMessage(
                'The --test-folder "' . $testFolder . '" does not exist. ' .
                'Please create it first, or choose an existing folder. ',
            );
        }

        return [$testFolder, $testFolderPath];
    }

    private function generateStub(string $fqcn, string $namespace): string
    {
        try {
            return $this->mockLogicCreator->createTestClassStub($fqcn, $namespace);
        } catch (\TypeError) {
            $message  = 'The class was not found or was improperly entered.' . PHP_EOL;
            $message .= 'Use the command with this type of formatting:  ' . PHP_EOL;
            $message .= PHP_EOL;
            $message .= 'php ./vendor/bin/mock-it-all create-test-stub-with-mocks ';
            $message .= '--fqcn="PatrickMaynard\MockItAll\Stubs\President" --test-folder=tests';

            throw CommandFailure::withMessage($message);
        } catch (\Throwable $throwable) {
            throw CommandFailure::withMessage(
                'An unexpected error occurred while generating the stub: ' . $throwable->getMessage() . PHP_EOL,
            );
        }
    }

    /**
     * Either dumps the generated stub straight to stdout, or writes it to a
     * new file in the chosen test folder, named after the class being
     * tested (e.g. President -> PresidentTest.php).
     */
    private function writeOrDumpStub(
        OutputInterface $output,
        string $stubLogic,
        string $fqcn,
        string $testFolderPath,
        bool $dumpOutputWithoutWrite,
    ): void {
        if ($dumpOutputWithoutWrite) {
            print PHP_EOL .
                PHP_EOL .
                $stubLogic .
                PHP_EOL .
                PHP_EOL;

            return;
        }

        $shortClassName = $fqcn;
        $lastBackslashPosition = strrpos($fqcn, '\\');
        if ($lastBackslashPosition !== false) {
            $shortClassName = substr($fqcn, $lastBackslashPosition + 1);
        }

        $testStubPath = rtrim($testFolderPath, '/\\') . DIRECTORY_SEPARATOR . $shortClassName . 'Test.php';

        if (file_exists($testStubPath)) {
            throw CommandFailure::withMessage(
                'A file already exists at "' . $testStubPath . '". Not overwriting it. Exiting. ',
            );
        }

        // The wizard's folder autocomplete allows freely-typed paths that
        // don't exist yet (e.g. a new descendant directory), so create the
        // folder here if needed. (The --test-folder path was already
        // required to exist in resolveTestFolder().)
        if (!is_dir($testFolderPath) && !mkdir($testFolderPath, 0777, true) && !is_dir($testFolderPath)) {
            throw CommandFailure::withMessage(
                'Could not create the test folder "' . $testFolderPath . '". Exiting. ',
            );
        }

        file_put_contents($testStubPath, $stubLogic);

        $output->writeln('');
        $output->writeln(sprintf('Test stub written to <info>%s</info>.', $testStubPath));
        $output->writeln('');
    }
}
