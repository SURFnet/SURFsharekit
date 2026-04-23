<?php

namespace ShareControl;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\LmsJsonApiController;
use SurfSharekit\Models\Institute;
use SurfSharekit\ShareControl\ShareControlApiCommunicator;

class LMSSearchApiTest extends SapphireTest {
    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        $_GET['flush'] = 1;
        parent::__construct($name, $data, $dataName);
    }

    private static function updateInstitute(Institute $institute) {
        $institute->IBronEnabled = true;
        $institute->IBronName = "test";
        $institute->write();
    }

    public function setUp(): void {
        parent::setUp();

        ShareControlApiCommunicatorMock::reset();
    }

    public function testApiController() {
        DB::get_conn()->transactionStart();
        $controller = LmsJsonApiController::create();
        $getVars = [];
        $institute = Institute::get()->first();
        static::updateInstitute($institute);
        parse_str("page[number]=1&page[size]=20&filter[search]=Test&institute=$institute->Uuid", $getVars);
        $controller->setRequest(new HTTPRequest("GET", "/lms/item", $getVars));
        Injector::inst()->registerService(ShareControlApiCommunicatorMock::create(), ShareControlApiCommunicator::class);

        $result = json_decode($controller->getJsonApiRequest(), true);
        $expectedResult = json_decode(
            '{"data":[{"type":"item","id":"20fcb4b2-8ff7-48a8-80d4-cab1fd986f22","attributes":{"title":"Big data in 60 minuten","subTitle":"","firstHarvested":{"date":"2025-05-20 08:01:31.693951","timezone_type":2,"timezone":"Z"},"keywords":["facility management","informatiemaatschappij","information society"],"authors":[{"fullName":"","institute":"Institute"}]}},{"type":"item","id":"22712826-eaae-49b8-85c7-41ed2e5f5a70","attributes":{"title":"Vernieuwing in de bewegingsregistratie van mensen","subTitle":"advies op het gebruik van Machine Learning in combinatie met het Awinda-meetsysteem","firstHarvested":{"date":"2025-05-20 08:00:09.726628","timezone_type":2,"timezone":"Z"},"keywords":["data-analyse","machinaal leren","meetinstrumenten","voetbalblessures","voetbaltraining"],"authors":[{"fullName":"Shane Lagerweij","institute":"Institute"},{"fullName":"Erik Wilmes","institute":"Institute"}]}}],"meta":{"totalCount":2},"links":{"self":"items","first":"items?page[number]=1&page[size]=20","last":"items?page[number]=1&page[size]=20"}}',
            true
        );
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals("Test", ShareControlApiCommunicatorMock::$lastSerachTerm);
        $this->assertEquals(1, ShareControlApiCommunicatorMock::$lastPage);
        $this->assertEquals(20, ShareControlApiCommunicatorMock::$lastPageSize);
        DB::get_conn()->transactionRollback();
    }

    /**
     * @dataProvider pageSizes
     */
    public function testPageSizes($pageSize, $expectedPageSize) {
        DB::get_conn()->transactionStart();
        $controller = LmsJsonApiController::create();
        $getVars = [];
        Injector::inst()->registerService(ShareControlApiCommunicatorMock::create(), ShareControlApiCommunicator::class);

        $institute = Institute::get()->first();
        static::updateInstitute($institute);
        parse_str("page[number]=1&page[size]=$pageSize&filter[search]=Test&institute=$institute->Uuid", $getVars);
        $controller->setRequest(new HTTPRequest("GET", "/lms/item", $getVars));
        $controller->getJsonApiRequest();
        $this->assertEquals(1, ShareControlApiCommunicatorMock::$lastPage);
        $this->assertEquals($expectedPageSize, ShareControlApiCommunicatorMock::$lastPageSize);
        DB::get_conn()->transactionRollback();
    }

    /**
     * @dataProvider pageNumbers
     */
    public function testPageNumbers($pageNumber, $expectedPageNumber) {
        DB::get_conn()->transactionStart();
        $controller = LmsJsonApiController::create();
        $getVars = [];
        Injector::inst()->registerService(ShareControlApiCommunicatorMock::create(), ShareControlApiCommunicator::class);

        $institute = Institute::get()->first();
        static::updateInstitute($institute);
        parse_str("page[number]=$pageNumber&page[size]=20&filter[search]=Test&institute=$institute->Uuid", $getVars);
        $controller->setRequest(new HTTPRequest("GET", "/lms/item", $getVars));
        $controller->getJsonApiRequest();
        $this->assertEquals($expectedPageNumber, ShareControlApiCommunicatorMock::$lastPage);
        DB::get_conn()->transactionRollback();
    }

    public function testNoInstitute() {
        $this->expectException(BadRequestException::class);

        Injector::inst()->registerService(ShareControlApiCommunicatorMock::create(), ShareControlApiCommunicator::class);
        $getVars = [];
        parse_str("page[number]=1&page[size]=20&filter[search]=Test&institute=Fake", $getVars);
        $controller = LmsJsonApiController::create();
        $controller->setRequest(new HTTPRequest("GET", "/lms/item", $getVars));
        $controller->getJsonApiRequest();
    }

    public function testInvalidInstitute() {
        $institute = Institute::get()->first();
        Injector::inst()->registerService(ShareControlApiCommunicatorMock::create(), ShareControlApiCommunicator::class);
        $getVars = [];
        parse_str("page[number]=1&page[size]=20&filter[search]=Test&institute=$institute->Uuid", $getVars);
        $controller = LmsJsonApiController::create();
        $controller->setRequest(new HTTPRequest("GET", "/lms/item", $getVars));

        $result = json_decode($controller->getJsonApiRequest(), true);
        $expectedResult = json_decode(
            '{"data":[],"meta":{"totalCount":0},"links":{"self":"items","first":"items?page[number]=1&page[size]=20","last":"items?page[number]=0&page[size]=20"}}',
            true
        );
        $this->assertEquals($expectedResult, $result);
        $this->assertNull(ShareControlApiCommunicatorMock::$lastSerachTerm);
    }

    public function pageSizes() {
        return [[null, 20], [0, 20], [1, 1], [10, 10], [20, 20], [30, 20]];
    }

    public function pageNumbers() {
        return [[null, 1], [0, 1], [1, 1], [2, 2]];
    }
}