<?php

namespace App\Tests\Unit\Service\Codict;

use App\Factory\Mepwall\OrganDescriptionFactory;
use App\Factory\Mepwall\OrganFactory;
use App\Repository\StationMepwallOrganeDescRepository;
use App\Repository\StationMepwallOrganeRepository;
use App\Service\Codict\Fetcher;
use App\Service\Codict\OrganSyncer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class OrganSyncerTest extends KernelTestCase
{
    /**
     * @param string[] $organIds
     *
     * @dataProvider provideSync
     *
     * @throws \Exception
     */
    public function testSync(
        array $organIds,
        int $validOrgansCount,
        int $validDescCount,
    ): void {
        self::bootKernel();

        $container = static::getContainer();

        $mepwallOrganeRepo = $this->createMock(StationMepwallOrganeRepository::class);
        $mepwallOrganeRepo->expects(self::once())->method('deleteAll');

        $mepwallOrganeDescRepo = $this->createMock(StationMepwallOrganeDescRepository::class);
        $mepwallOrganeDescRepo->expects(self::once())->method('deleteAll');

        $fetcher = $this->createMock(Fetcher::class);
        $fetcher->expects(self::once())->method('fetchAllOrganeIDs')->willReturn($organIds);
        $fetcher
            ->expects(self::exactly(count($organIds)))
            ->method('fetchOrganeDataByID')
            ->willReturnCallback(function ($organID) {
                return match ($organID) {
                    '1' => [],
                    '2' => $this->createOrganData(2, 0),
                    '3' => $this->createOrganData(1, 1),
                    '4' => $this->createOrganData(0, 2),
                };
            });

        $organFactory = $this->createMock(OrganFactory::class);
        $organFactory->expects(self::exactly($validOrgansCount))->method('createFromCodictDto');

        $organDescFactory = $this->createMock(OrganDescriptionFactory::class);
        $organDescFactory->expects(self::exactly($validDescCount))->method('createFromCodictDto');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly($validOrgansCount + $validDescCount))->method('persist');

        $subject = new OrganSyncer(
            $mepwallOrganeRepo,
            $mepwallOrganeDescRepo,
            $organFactory,
            $organDescFactory,
            $fetcher,
            $container->get('serializer'),
            $em,
        );

        $subject->sync(new NullLogger());
    }

    private function createOrganData(int $validDesc, int $invalidDesc): array
    {
        $organData = [
            'bodyId' => 2,
            'bodyType' => 'A',
            'activatedDate' => 'A',
            'updateDate' => 'A',
            'deletedDate' => 'A',
        ];

        for ($i = 0; $i < $validDesc; ++$i) {
            $organData['descriptions'][] = [
                'langIsoCode' => 'ss',
                'fullName' => 'ss',
                'shortName' => 'ss',
                'order' => 3,
            ];
        }

        for ($i = 0; $i < $invalidDesc; ++$i) {
            $organData['descriptions'][] = [
                'langIsoCode' => '',
                'fullName' => 'ss',
                'shortName' => 'ss',
                'order' => 3,
            ];
        }

        return $organData;
    }

    public function provideSync(): array
    {
        return [
            'All organs have data' => [
                ['2', '2'],
                2,
                4,
            ],
            'Some organs do not have data' => [
                ['1', '2'],
                1,
                2,
            ],
            'Some organs do not have valid description' => [
                ['1', '2', '3', '4'],
                3,
                3,
            ],
            'All organs have no data' => [
                ['1', '1'],
                0,
                0,
            ],
        ];
    }

    public function testSyncException(): void
    {
        $this->expectException(\Exception::class);

        $mepwallOrganeRepo = $this->createMock(StationMepwallOrganeRepository::class);
        $mepwallOrganeRepo->expects(self::once())->method('deleteAll');

        $mepwallOrganeDescRepo = $this->createMock(StationMepwallOrganeDescRepository::class);
        $mepwallOrganeDescRepo->expects(self::once())->method('deleteAll');

        $fetcher = $this->createMock(Fetcher::class);
        $fetcher->expects(self::once())->method('fetchAllOrganeIDs')->willReturn([]);

        $subject = new OrganSyncer(
            $mepwallOrganeRepo,
            $mepwallOrganeDescRepo,
            $this->createMock(OrganFactory::class),
            $this->createMock(OrganDescriptionFactory::class),
            $fetcher,
            $this->createMock(SerializerInterface::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $subject->sync(new NullLogger());
    }
}
