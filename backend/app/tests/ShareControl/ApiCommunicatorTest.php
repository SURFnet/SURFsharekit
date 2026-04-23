<?php


namespace ShareControl;

use DateTime;
use GuzzleHttp\Client;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SurfSharekit\ShareControl\Model\Item;
use SurfSharekit\ShareControl\ShareControlApiCommunicatorImpl;
use SurfSharekit\ShareControl\ShareControlApiException;

include_once("ShareControlMockClient.php");

class ApiCommunicatorTest extends SapphireTest {
    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        $_GET['flush'] = 1;
        parent::__construct($name, $data, $dataName);
    }

    public function testSearch() {
        Injector::inst()->registerService(new ShareControlMockClient(), Client::class);
        ShareControlMockClient::setResponseToFile("SearchMockResponse.json");

        $result = ShareControlApiCommunicatorImpl::searchItems("bron", "Test");
        $this->assertCount(20, $result);

        $record = $result[6];
        $this->assertTrue($record instanceof Item);
        $this->assertEquals($record->uuid, "69048aec-869d-4bb4-86c3-86908385aa4e");
        $this->assertEquals($record->firstHarvested, new DateTime("2025-04-29T09:06:01.371991Z"));
        $this->assertEquals($record->keywords, [
            "TRAP-BATH split",
            "vowels"
        ]);

        $this->assertCount(1, $record->authors);
        $author = $record->authors[0];
        $this->assertEquals("36f65e8a-2f22-473a-8114-98a00c0d3ec0", $author->uuid);

        $record = $result[7];
        $this->assertCount(2, $record->authors);
        $this->assertCount(1, $record->files);
        $file = $record->files[0];
        $this->assertEquals("4f3dc0ce-ffaf-46f5-96d1-6d023760edd9", $file->uuid);

        $this->assertFalse($record->purged);
    }

    public function testSearchMalformed() {
        $this->expectException(ShareControlApiException::class);

        Injector::inst()->registerService(new ShareControlMockClient(), Client::class);
        ShareControlMockClient::setResponseToFile("SearchMockResponseMalformed.json");
        ShareControlApiCommunicatorImpl::searchItems("bron", "Test");
    }
}