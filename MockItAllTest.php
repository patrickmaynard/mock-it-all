<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Codict;

use PatrickMaynard\MockItAll\MockLogicCreator;
use App\Service\Codict\OrganSyncer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MockItAllTest extends KernelTestCase
{
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
        $mockLogicCreator = new MockLogicCreator();

        $mockLogicCreator->createTestClassStub(OrganSyncer::class);
    }

    public function testTheTruthyThing(): void
    {
        self::assertTrue(true);
    }


}

