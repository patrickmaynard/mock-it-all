<?php

namespace App\Tests\Unit\Service\Codict;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Codict\CodictService;

class MockItAllTest extends KernelTestCase
{
    public function setUp(): void
    {
        //This is where we will set up our mock objects.
        //We want to do this in the most generic way possible.
        //First, we will define an array of mock relationships.
        //(These will eventually be object, but nested arrays are fine for now.)
        $mockRelationships = [];
        $topLevelClassNameToMock = CodictService::class;

        //Each nested mockRelationship we create will be an array of the form:
        //[
        //    'classNameToMock' => <the class name to mock as a string>,
        //    'children' => <more mockRelationships go here as an array>,
        //    'mockCreationCommand' => <the mock creation command as a string>,
        //]

        //There will be three steps to this process.
        //First, we will run $this->createMockRelationships($topLevelClassNameToMock, $mockRelationships).
        //That command will recursively call itself until we have a nice array of mock relationships.
        //However, the 'mockCreationCommand' values will be placeholder strings like '---' for now.
        //Then we will run $this->populateMockCreationCommands($mockRelationships).
        //That command will replace the 'mockCreationCommand' values with actual mock creation commands.
        //Finally, we will run $this->createFinalMockCreationLogic($mockRelationships).
        //That command will run recursively in order to assemble the order of operations for mock creation.
        //It is very important that the longest chain of dependencies run first,
        //and that it start at the dependency farthest from the top level class.
        //In other words, we assemble the little constituant pieces first, \
        //allowing us to later assemble the whole thing.

        //$relationships = $this->createMockRelationships($topLevelClassNameToMock, $mockRelationships);
        //$relationships = $this->populateMockCreationCommands($relationships);
        //$finalLogic    = $this->createFinalMockCreationLogic($relationships);

    }

    public function testTheTruthyThing(): void
    {
        self::assertTrue(true);
    }


}
