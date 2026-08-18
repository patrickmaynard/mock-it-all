<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests;

use PatrickMaynard\MockItAll\MockLogicCreator;
use PatrickMaynard\MockItAll\Stubs\ChiefOfStaff;
use PatrickMaynard\MockItAll\Stubs\FryCook;
use PatrickMaynard\MockItAll\Stubs\Janitor;
use PatrickMaynard\MockItAll\Stubs\PersonalChef;
use PatrickMaynard\MockItAll\Stubs\President;
use PatrickMaynard\MockItAll\Stubs\SecretaryOfCommerce;
use PatrickMaynard\MockItAll\Stubs\SecretaryOfDefense;
use PatrickMaynard\MockItAll\Stubs\SecretaryOfState;
use PHPUnit\Framework\TestCase;

class MockItAllTest extends TestCase 
{
    private string $output; 

    private MockLogicCreator $mockLogicCreator;

    public function setUp(): void
    {
        $this->mockLogicCreator = new MockLogicCreator();

        $janitor = new Janitor;
        $fryCook = new FryCook;
        $personalChef = new PersonalChef($fryCook);
        $chiefOfStaff = new ChiefOfStaff;
        $state = new SecretaryOfState($chiefOfStaff, $janitor, $personalChef);
        $defense = new SecretaryOfDefense($chiefOfStaff);
        $commerce = new SecretaryOfCommerce($chiefOfStaff);
        $president = new President($state, $defense, $commerce);


        $this->output = $this->mockLogicCreator->createTestClassStub(President::class, 'PatrickMaynard\MockItAll\Tests\Stubs');
    }

    public function testThatExpectedStubIsReturned(): void
    {
        //file_put_contents('./test/expectedOutput.txt', $this->output);

        $expectedOutput = file_get_contents('./test/expectedOutput.txt');
        self::assertEquals($expectedOutput, $this->output);
    }

    public function testThatBadClassNameYieldsException()
    {
        $this->expectException(\TypeError::class);
        $this->output = $this->mockLogicCreator->createTestClassStub('PatrickMaynard\Foo\Bar\Baz\Qux', 'Irrelevant\Namespace');
    }
}

