<?php

use PHPUnit\Framework\TestCase;

class CampaignAppealTest extends TestCase
{
    private ?ReflectionProperty $databaseInstanceProperty = null;
    private mixed $originalDatabaseInstance = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseInstanceProperty = new ReflectionProperty(Database::class, 'instance');
        $this->databaseInstanceProperty->setAccessible(true);
        $this->originalDatabaseInstance = $this->databaseInstanceProperty->getValue();

        $this->resetCampaignAppealStatics();
    }

    protected function tearDown(): void
    {
        if ($this->databaseInstanceProperty instanceof ReflectionProperty) {
            $this->databaseInstanceProperty->setValue($this->originalDatabaseInstance);
        }

        $this->resetCampaignAppealStatics();

        parent::tearDown();
    }

    public function testAttachEvidenceFilesSkipsWhenFilesTableMissing(): void
    {
        $databaseMock = $this->createDatabaseMock([
            'campaign_appeal_files' => false,
            'campaigns' => false,
        ]);

        $databaseMock->expects($this->never())
            ->method('insert');

        $this->setDatabaseInstance($databaseMock);

        $appeal = new CampaignAppeal();

        $appeal->attachEvidenceFiles(42, [
            [
                'path' => 'storage/private/test.pdf',
                'filename' => 'test.pdf',
                'mime' => 'application/pdf',
                'size' => 512,
                'uploaded_by' => 5,
            ],
        ]);
    }

    public function testAttachEvidenceFilesPersistsRowsWhenTableExists(): void
    {
        $databaseMock = $this->createDatabaseMock([
            'campaign_appeal_files' => true,
            'campaigns' => false,
        ]);

        $insertCalls = [];

        $databaseMock->expects($this->exactly(2))
            ->method('insert')
            ->with($this->equalTo('campaign_appeal_files'), $this->isType('array'))
            ->willReturnCallback(function (string $table, array $data) use (&$insertCalls): int {
                $insertCalls[] = ['table' => $table, 'data' => $data];
                return count($insertCalls);
            });

        $this->setDatabaseInstance($databaseMock);

        $appeal = new CampaignAppeal();

        $appeal->attachEvidenceFiles(12, [
            [
                'path' => 'storage/private/file-one.pdf',
                'filename' => 'file-one.pdf',
                'mime' => 'application/pdf',
                'size' => 1024,
                'uploaded_by' => 9,
            ],
            [
                'path' => 'storage/private/file-two.jpg',
                'filename' => 'file-two.jpg',
                'mime' => 'image/jpeg',
                'size' => 2048,
                'uploaded_by' => 11,
            ],
        ]);

        $this->assertCount(2, $insertCalls);

        $firstCall = $insertCalls[0]['data'];
        $this->assertSame(12, $firstCall['appeal_id']);
        $this->assertSame('storage/private/file-one.pdf', $firstCall['storage_path']);
        $this->assertSame('file-one.pdf', $firstCall['original_name']);
        $this->assertSame('application/pdf', $firstCall['mime_type']);
        $this->assertSame(1024, $firstCall['size_bytes']);
        $this->assertSame(9, $firstCall['uploaded_by']);
        $this->assertArrayHasKey('created_at', $firstCall);

        $secondCall = $insertCalls[1]['data'];
        $this->assertSame(12, $secondCall['appeal_id']);
        $this->assertSame('storage/private/file-two.jpg', $secondCall['storage_path']);
        $this->assertSame('file-two.jpg', $secondCall['original_name']);
        $this->assertSame('image/jpeg', $secondCall['mime_type']);
        $this->assertSame(2048, $secondCall['size_bytes']);
        $this->assertSame(11, $secondCall['uploaded_by']);
        $this->assertArrayHasKey('created_at', $secondCall);
    }

    public function testGetFilesForAppealsGroupsResultsByAppealId(): void
    {
        $expectedRows = [
            [
                'id' => 1,
                'appeal_id' => 55,
                'storage_path' => 'storage/private/doc1.pdf',
                'original_name' => 'doc1.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 300,
                'uploaded_by' => 7,
                'created_at' => '2025-10-17 12:00:00',
            ],
            [
                'id' => 2,
                'appeal_id' => 55,
                'storage_path' => 'storage/private/doc2.pdf',
                'original_name' => 'doc2.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 450,
                'uploaded_by' => 7,
                'created_at' => '2025-10-17 12:05:00',
            ],
            [
                'id' => 3,
                'appeal_id' => 57,
                'storage_path' => 'storage/private/photo.png',
                'original_name' => 'photo.png',
                'mime_type' => 'image/png',
                'size_bytes' => 512,
                'uploaded_by' => 8,
                'created_at' => '2025-10-17 12:10:00',
            ],
        ];

        $databaseMock = $this->createDatabaseMock([
            'campaign_appeal_files' => true,
            'campaigns' => false,
        ]);

        $databaseMock->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->stringContains('FROM campaign_appeal_files'),
                $this->equalTo([55, 57])
            )
            ->willReturn($expectedRows);

        $this->setDatabaseInstance($databaseMock);

        $appeal = new CampaignAppeal();

        $grouped = $appeal->getFilesForAppeals([55, 57, 55]);

        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey(55, $grouped);
        $this->assertArrayHasKey(57, $grouped);

        $this->assertCount(2, $grouped[55]);
        $this->assertSame('doc1.pdf', $grouped[55][0]['original_name']);
        $this->assertSame('doc2.pdf', $grouped[55][1]['original_name']);
        $this->assertSame('photo.png', $grouped[57][0]['original_name']);
    }

    /**
     * @param array<string, bool> $tableExistsMap
     * @return \PHPUnit\Framework\MockObject\MockObject&Database
     */
    private function createDatabaseMock(array $tableExistsMap): PHPUnit\Framework\MockObject\MockObject
    {
        /** @var PHPUnit\Framework\MockObject\MockObject&Database $databaseMock */
        $databaseMock = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tableExists', 'fetchAll', 'insert'])
            ->getMock();

        $databaseMock->method('tableExists')
            ->willReturnCallback(static function (string $table) use ($tableExistsMap): bool {
                return $tableExistsMap[$table] ?? false;
            });

        return $databaseMock;
    }

    private function setDatabaseInstance(Database $database): void
    {
        if ($this->databaseInstanceProperty === null) {
            $this->databaseInstanceProperty = new ReflectionProperty(Database::class, 'instance');
            $this->databaseInstanceProperty->setAccessible(true);
        }

        $this->databaseInstanceProperty->setValue($database);
    }

    private function resetCampaignAppealStatics(): void
    {
        $properties = [
            'hasFilesTable' => null,
            'campaignTableChecked' => false,
            'campaignTableExists' => false,
            'campaignColumns' => [],
        ];

        foreach ($properties as $property => $value) {
            $reflection = new ReflectionProperty(CampaignAppeal::class, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($value);
        }
    }
}
