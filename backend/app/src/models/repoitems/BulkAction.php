<?php

namespace SurfSharekit\Models;

use BulkActionJsonApiDescription;
use Exception;
use OpenApi\Tests\LoggerTest;
use RelationaryPermissionProviderTrait;
use RepoItemJsonApiDescription;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Models\Helper\Logger;

class BulkAction extends DataObject implements PermissionProvider {
    use RelationaryPermissionProviderTrait;
    private static $singular_name = 'bulkAction';
    private static $plural_name = 'bulkActions';

    private static $table_name = 'SurfSharekit_BulkActions';

    private static $db = [
        'FilterJSON' => 'Text',                   // JSON encoded
        'Action' => 'Enum(array("DELETE" "ARCHIVE", "DEPUBLISH"), null)',
        'ProcessStatus' => 'Enum(array("WAITING", "ONGOING", "COMPLETED", "FAILED"), "WAITING")',
        'TotalCount' => 'Int(0)',
        'SuccessCount' => 'Int(0)',
        'FailCount' => 'Int(0)'
    ];

    function setFilterJSON($filters) {
        $jsonEncodedFilters = json_encode($filters);
        $this->setField('FilterJSON', $jsonEncodedFilters);
    }

    function getFilterJSON() {
        return json_decode($this->getField("FilterJSON"), true);
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if(!$this->isInDB()) {

            $repoItems = $this->getRepoItemsToPerformActionOn();
            $this->TotalCount = count($repoItems);
        }
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();

        if($this->isChanged('ID')) {
            $repoItems = $this->getRepoItemsToPerformActionOn();
            $this->TotalCount = count($repoItems);
        }
    }

    function getRepoItemsToPerformActionOn() : DataList {
        $instituteIDs = $this->createdBy()->getInstituteIDs();

        $repoItems = InstituteScoper::getDataListScopedTo(RepoItem::class, $instituteIDs);
        $repoItems = PermissionFilter::filterThroughCanViewPermissions($repoItems);

        $repoItemDescription = new RepoItemJsonApiDescription();
        $repoItems = $repoItemDescription->applyGeneralFilter($repoItems);

        $filters = $this->FilterJSON;
        if(is_string($filters)) {
            // Only json decode if the filters are json encoded.
            // This function is also used in onBeforeWrite() (for TotalCount calculation) where this is not yet the case
            $filters = json_decode($filters, true);
        }

        foreach ($filters as $field => $value) {
            if(is_array($value)) {
                $repoItems = $repoItemDescription->applyFilter($repoItems, $field, $value);
            }
        }
        return $repoItems;
    }

    public function validate() {
        $result = parent::validate();

        if(!$this->Action) {
            $result->addError("Please provide an action (DELETE, ARCHIVE, DEPUBLISH)");
        }

        return $result;
    }

    function canView($member = null, $context = []) {
        return $this->createdBy()->canSanitize();
    }

    function canCreate($member = null, $context = []) {
        return $this->createdBy()->canSanitize();
    }

}