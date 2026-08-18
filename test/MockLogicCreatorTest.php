<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests;

use PatrickMaynard\MockItAll\MockLogicCreator;
use PatrickMaynard\MockItAll\Stubs\ChiefOfStaff;
use PatrickMaynard\MockItAll\Stubs\FryCook;
use PatrickMaynard\MockItAll\Stubs\PressSecretary;
use PHPUnit\Framework\TestCase;

/**
 * Focused unit tests for the smaller pieces of MockLogicCreator's logic,
 * complementing MockItAllTest's full end-to-end fixture comparison.
 */
class MockLogicCreatorTest extends TestCase
{
    private MockLogicCreator $mockLogicCreator;

    protected function setUp(): void
    {
        $this->mockLogicCreator = new MockLogicCreator();
    }

    public function testPlaceholderLiteralForEachBuiltinType(): void
    {
        self::assertSame("''", $this->mockLogicCreator->placeholderLiteralForBuiltinType('string'));
        self::assertSame('0', $this->mockLogicCreator->placeholderLiteralForBuiltinType('int'));
        self::assertSame('0.0', $this->mockLogicCreator->placeholderLiteralForBuiltinType('float'));
        self::assertSame('false', $this->mockLogicCreator->placeholderLiteralForBuiltinType('bool'));
        self::assertSame('[]', $this->mockLogicCreator->placeholderLiteralForBuiltinType('array'));
        self::assertSame('[]', $this->mockLogicCreator->placeholderLiteralForBuiltinType('iterable'));
        self::assertSame('null', $this->mockLogicCreator->placeholderLiteralForBuiltinType('mixed'));
        self::assertSame('null', $this->mockLogicCreator->placeholderLiteralForBuiltinType('object'));
    }

    public function testGetShortClassNameFromClassName(): void
    {
        self::assertSame(
            'ChiefOfStaff',
            $this->mockLogicCreator->getShortClassNameFromClassName(ChiefOfStaff::class),
        );
    }

    public function testGetVariableNameFromClassNameLowercasesTheFirstLetter(): void
    {
        self::assertSame(
            'chiefOfStaffOne',
            $this->mockLogicCreator->getVariableNameFromClassName(ChiefOfStaff::class),
        );
    }

    public function testGetVariableNameFromClassNameIncrementsOnRepeatedCalls(): void
    {
        self::assertSame(
            'chiefOfStaffOne',
            $this->mockLogicCreator->getVariableNameFromClassName(ChiefOfStaff::class),
        );
        self::assertSame(
            'chiefOfStaffTwo',
            $this->mockLogicCreator->getVariableNameFromClassName(ChiefOfStaff::class),
        );
        self::assertSame(
            'chiefOfStaffThree',
            $this->mockLogicCreator->getVariableNameFromClassName(ChiefOfStaff::class),
        );
    }

    public function testGetVariableNameFromClassNameDoesNotShareCounterAcrossDifferentClasses(): void
    {
        self::assertSame(
            'chiefOfStaffOne',
            $this->mockLogicCreator->getVariableNameFromClassName(ChiefOfStaff::class),
        );
        self::assertSame(
            'fryCookOne',
            $this->mockLogicCreator->getVariableNameFromClassName(FryCook::class),
        );
    }

    /**
     * A builtin-typed constructor parameter (string, in PressSecretary's
     * case) isn't a class we can mock or recurse into. It should be inlined
     * as a placeholder literal directly in the constructor call, with no
     * mock property or use statement generated for it.
     */
    public function testScalarConstructorParametersAreInlinedAsPlaceholders(): void
    {
        $output = $this->mockLogicCreator->createTestClassStub(PressSecretary::class);

        $expectedOutput = file_get_contents(__DIR__ . '/expectedPressSecretaryOutput.txt');

        self::assertSame($expectedOutput, $output);
    }
}
