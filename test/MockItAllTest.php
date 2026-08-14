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

    public function setUp(): void
    {
        $mockLogicCreator = new MockLogicCreator();

        $janitor = new Janitor;
        $fryCook = new FryCook;
        $personalChef = new PersonalChef($fryCook);
        $chiefOfStaff = new ChiefOfStaff;
        $state = new SecretaryOfState($chiefOfStaff, $janitor, $personalChef);
        $defense = new SecretaryOfDefense($chiefOfStaff);
        $commerce = new SecretaryOfCommerce($chiefOfStaff);
        $president = new President($state, $defense, $commerce);


        $this->output = $mockLogicCreator->createTestClassStub(President::class);
    }

    public function testTheTruthyThing(): void
    {
        //file_put_contents('./test/expectedOutput.txt', $this->output);

        $expectedOutput = file_get_contents('./test/expectedOutput.txt');
        self::assertEquals($expectedOutput, $this->output);
    }


}

