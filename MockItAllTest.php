<?php

namespace App\Tests\Unit\Service\Codict;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Codict\OrganSyncer;
use ReflectionClass;

class MockItAllTest extends KernelTestCase
{
    public function setUp(): void
    {
        //This is where we will set up our mock objects.
        //We want to do this in the most generic way possible.
        //First, we will define an array of mock relationships.
        //(These will eventually be object, but nested arrays are fine for now.)
        $mockRelationships = [];
        $topLevelClassNameToMock = OrganSyncer::class;

        //Each nested mockRelationship we create will be an array of the form:
        //[
        //    'classNameToMock' => <the class name to mock as a string>,
        //    'children' => <more mockRelationships go here as an array>,
        //    'depth' => <the depth of the mockRelationship in the array as an integer>,
        //    'creationCommand' => <the mock creation command as a string>,
        //    'variableName' => <the variable name to use for the mock object as a string>
        //]

        //There will be three steps to this process.
        //First, we will run $this->createMockRelationships($topLevelClassNameToMock, $mockRelationships).
        //That command will recursively call itself until we have a nice array of mock relationships.
        //However, the 'creationCommand' values will be placeholder strings like '---' for now.
        //Then we will run $this->populateCreationCommands($mockRelationships).
        //That command will replace the 'creationCommand' values with actual mock creation commands.
        //Finally, we will run $this->createFinalMockCreationLogic($mockRelationships).
        //That command will run recursively in order to assemble the order of operations for mock creation.
        //It is very important that the longest chain of dependencies run first,
        //and that it start at the dependency farthest from the top level class.
        //In other words, we assemble the little constituent pieces first,
        //allowing us to later assemble the whole thing.

        $relationships        = $this->createMockRelationships($topLevelClassNameToMock, $mockRelationships, 0);
        $relationships      = $this->populateCreationCommands($relationships);
        //$finalLogic         = $this->createFinalMockCreationLogic($relationships);
        //$finalUseStatements = $this->createFinalUseStatements($relationships);

        print PHP_EOL . 'Mock relationships:' . PHP_EOL;
        print_r($relationships);
        print PHP_EOL;

    }

    public function createMockRelationships(
        string $classNameToMock,
        array  $mockRelationships,
        int    $depth
    ): array
    {
        //First, we check to see if the class has a constructor.
        $methods = get_class_methods($classNameToMock);
        $variableName = $this->getVariableNameFromClassName($classNameToMock);
        if ($methods[0] === '__construct') {
            $relationship = $this->createDefaultMockRelationship($classNameToMock, $depth, $variableName);;
            //If it does, we will recursively call this function with the constructor's arguments.
            $reflectionObject = new ReflectionClass($classNameToMock);
            foreach ($reflectionObject->getConstructor()->getParameters() as $parameter) {
                $relationship['children'][] = $this
                    ->createMockRelationships($parameter->getType()->getName(), $mockRelationships, $depth + 1);
            }
            return $relationship;
        }
        //Otherwise, we will return an array that contains only the top-level class name and some default values.
        $relationship = $this->createDefaultMockRelationship($classNameToMock, $depth, $variableName);;
        return $relationship;
    }

    public function populateCreationCommands(array $relationship): array
    {
        if (count($relationship['children']) > 0) {
            //If the class has children, we will recursively call this function on each child.
            foreach ($relationship['children'] as $key => $child) {
                $relationship['children'][$key] = $this->populateCreationCommands($child);
            }
        }
        if ($relationship['depth'] === 0) {
            //This is the top level. Use the actual constructor rather than createMock().
            $constructorArguments = implode(
                ', ',
                array_column($relationship['children'], 'variableName')
            );
            $relationship['creationCommand'] = '$this->' .
                $relationship['variableName'] .
                ' = new ' .
                $this->getShortClassNameFromClassName($relationship['classNameToMock']) .
                '(' .
                $constructorArguments .
                ');'
            ;
        } else {
            //This is a child. Use createMock() instead of new.
            $relationship['creationCommand'] = '$this->' .
                $relationship['variableName'] .
                ' = $this->createMock(' .
                $this->getShortClassNameFromClassName($relationship['classNameToMock']) .
                '::class);'
            ;
        }
        return $relationship;
    }

    public function createDefaultMockRelationship($className, $depth, $variableName): array
    {
        return [
            'classNameToMock' => $className,
            'children' => [],
            'depth' => $depth,
            'creationCommand' => '---',
            'variableName' => $variableName
        ];
    }

    public function getVariableNameFromClassName(string $className): string
    {
        $variableName = substr(
            $className,
            strrpos($className,
                '\\'
            )+1);
        $variableName = 'my' . $variableName;
        return $variableName;
    }

    public function getShortClassNameFromClassName(string $className): string
    {
        $variableName = substr(
            $className,
            strrpos($className,
                '\\'
            )+1);
        return $variableName;
    }

    public function testTheTruthyThing(): void
    {
        self::assertTrue(true);
    }


}
