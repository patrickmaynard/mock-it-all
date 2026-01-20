<?php

namespace App\Tests\Unit\Service\Codict;

use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Codict\OrganSyncer;
use ReflectionClass;

class MockItAllTest extends KernelTestCase
{
    public string $finalLogic;

    public string $finalUseStatements;

    public string $finalClassProprtyDefinitionStatements;

    public array $allVariableNames = [];

    //We'll assume we're not using more than 10 objects of the same type for now.
    //(For the purpose of variable naming, it feels sensible to skip Zero and be one-indexed.)
    public const ONE_TO_TEN = [
        'Zero',
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
        'Seven',
        'Eight',
        'Nine',
        'Ten'
    ];

    public function setUp(): void
    {
        //This is where we will set up our mock objects.
        //We want to do this in the most generic way possible.
        //First, we will define an array of mock relationships.
        //(These will eventually be object, but nested arrays are fine for now.)
        $mockRelationships = [];
        $topLevelClassNameToMock = OrganSyncer::class;
        $this->finalClassProprtyDefinitionStatements = '';
        $this->finalLogic = '';
        $this->finalUseStatements = '';

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
        $relationships        = $this->populateCreationCommands($relationships);
        $this->createFinalMockCreationLogic($relationships);
        //$finalUseStatements = $this->createFinalUseStatements($relationships);


        $output = '<?php declare(strict_types=1); ' . PHP_EOL . PHP_EOL .
            'namespace App\Your\Namespace\Here; ' . PHP_EOL . PHP_EOL .
            $this->finalUseStatements .
            'use PHPUnit\Framework\TestCase;'. PHP_EOL . PHP_EOL .
            'class YourClassNameTest extends TestCase ' . PHP_EOL . '{' . PHP_EOL .
            $this->finalClassProprtyDefinitionStatements . PHP_EOL . PHP_EOL . '    ' .
            'public function setUp(): void' . PHP_EOL . '    {' . PHP_EOL . '        ' .
            'parent::setUp();' . PHP_EOL . PHP_EOL .
            $this->finalLogic . PHP_EOL . '    ' .
            '}' . PHP_EOL . PHP_EOL .
            '    function testTheTruthyThing(): void' . PHP_EOL . '    {' . PHP_EOL . '        ' .
            '$this->assertTrue(true);' . PHP_EOL . '    ' .
            '}' . PHP_EOL .
            '}' . PHP_EOL;

        print PHP_EOL . 'Test class stub:' . PHP_EOL . $output . PHP_EOL;

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
        //Lowercase the first letter.
        $variableName = strtolower(substr($variableName, 0, 1)) . substr($variableName, 1);
        //Now iterate over our class constant ONE_TO_TEN and see if the variable name is already in use.
        for ($i = 0; $i <= count(self::ONE_TO_TEN) - 1; $i++) {
            $possibleVariableName = $variableName . self::ONE_TO_TEN[$i+1];
            if (!in_array($possibleVariableName, $this->allVariableNames)) {
                $variableName = $possibleVariableName;
                $this->allVariableNames[] = $variableName;
                break;
            }
        }
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

    public function createFinalMockCreationLogic(array $relationship): string
    {
        //We need to start at the deepest level of dependencies and work our way up.
        //That means calling createFinalMockCreationLogic() on each child relationship.
        if (!str_contains($this->finalUseStatements, $relationship['classNameToMock']) ) {
            $this->finalUseStatements .= 'use ' . $relationship['classNameToMock'] . ';' . PHP_EOL;
        }
        if (count($relationship['children']) === 0) {
            //We are at the deepest level. Add the mock creation command to the final logic.
            $this->finalLogic .= '        ' . $relationship['creationCommand'] . PHP_EOL;
            $shortName = $this->getShortClassNameFromClassName($relationship['classNameToMock']);
            $this->finalClassProprtyDefinitionStatements .= '    private ' . $shortName . ' $' . $relationship['variableName'] . ';' . PHP_EOL;
            return $relationship['variableName'];
        } else {
            //We are not at the deepest level.
            //Recurse to get variable names and then add the constructor for the class to the final logic.
            //Children have already been stored in the order of their use in the constructor, so this will be easy.
            $arguments = [];
            foreach ($relationship['children'] as $child) {
                $arguments[] = $this->createFinalMockCreationLogic($child);
            }
            $shortName = $this->getShortClassNameFromClassName($relationship['classNameToMock']);
            $creationCommand = '$this->' . $relationship['variableName'] . ' = new ' . $shortName . '($this->' . implode(', $this->', $arguments) . ');';
            $this->finalLogic .= '        ' . $creationCommand . PHP_EOL;
            $this->finalClassProprtyDefinitionStatements .= '    private ' . $shortName . ' $' . $relationship['variableName'] . ';' . PHP_EOL;
            return $relationship['variableName'];
        }
    }

    public function testTheTruthyThing(): void
    {
        self::assertTrue(true);
    }


}
