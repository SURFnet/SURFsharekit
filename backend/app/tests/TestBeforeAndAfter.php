<?php

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use SurfSharekit\ApiCache\ApiCacheController;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class TestBeforeAndAfter extends SapphireTest {
    public function __construct(?string $name = null, array $data = [], $dataName = '') {
        $_GET['flush'] = 1;
        parent::__construct($name, $data, $dataName);
    }

    private function doMutation() {
        Cache_RecordNode::get()->byID(169)->setField("Data", json_encode(["hoi" => "dag"]))->write();
    }

    public function testThingy() {
        $repoItem = RepoItem::get()->byID(21);
        $protocol = Protocol::get()->find("SystemKey", "CSV");

        DB::get_conn()->transactionStart();

        DB::get_conn()->transactionStart();
        $this->doMutation();
        $cacheA = DataObjectCSVFileEncoder::getCSVRowFor($repoItem, false, $protocol);
        DB::get_conn()->transactionRollback();

        DB::get_conn()->transactionStart();
        $this->doMutation();
        $cacheB = ApiCacheController::getRepoItemData($protocol, $repoItem, false);
        DB::get_conn()->transactionRollback();

        $this->assertEquals($cacheA, json_decode($cacheB, true));
        DB::get_conn()->transactionRollback();
    }
}