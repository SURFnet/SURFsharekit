<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyDecoder;
use DataObjectJsonApiBodyEncoder;
use DataObjectJsonApiDecoder;
use DataObjectJsonApiEncoder;
use DefaultMetaFieldOptionPartJsonApiDescription;
use Exception;
use GroupJsonApiDescription;
use InstituteJsonApiDescription;
use MetaFieldJsonApiDescription;
use MetaFieldOptionJsonApiDescription;
use MetaFieldTypeJsonApiDescription;
use PersonImageJsonApiDescription;
use PersonJsonApiDescription;
use PersonSummaryJsonApiDescription;
use RepoItemJsonApiDescription;
use RepoItemSummaryJsonApiDescription;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\PersonSummary;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\Template;
use SurfSharekit\Models\TemplateMetaField;
use TemplateJsonApiDescription;
use TemplateMetaFieldJsonApiDescription;

/**
 * Class InternalJsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point for the internal json api to GET,POST and PATCH DataObjects inside the logged in member's scope
 */
class InternalJsonApiController extends JsonApiController {
    /**
     * @return mixed
     * returns the parts of the url added to base url to denominate this API
     */
    protected function getApiURLSuffix() {
        return '/api/v1';
    }

    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [Group::class => new GroupJsonApiDescription(),
            Institute::class => new InstituteJsonApiDescription(),
            Template::class => new TemplateJsonApiDescription(),
            TemplateMetaField::class => new TemplateMetaFieldJsonApiDescription(),
            MetaField::class => new MetaFieldJsonApiDescription(),
            Person::class => new PersonJsonApiDescription(),
            PersonImage::class => new PersonImageJsonApiDescription(),
            InstituteImage::class => new \InstituteImageJsonApiDescription(),
            RepoItemFile::class => new \RepoItemFileJsonApiDescription(),
            MetaFieldType::class => new MetaFieldTypeJsonApiDescription(),
            MetaFieldOption::class => new MetaFieldOptionJsonApiDescription(),
            DefaultMetaFieldOptionPart::class => new DefaultMetaFieldOptionPartJsonApiDescription(),
            RepoItem::class => new RepoItemJsonApiDescription(),
            RepoItemSummary::class => new RepoItemSummaryJsonApiDescription(),
            PersonSummary::class => new PersonSummaryJsonApiDescription()];
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPatch
     * @return mixed
     * Called when all error checks have been done
     */
    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        $decoder = new DataObjectJsonApiDecoder($this->classToDescriptionMap);
        $response = DataObjectJsonApiBodyDecoder::changeObjectWithTypeFromRequestBody($objectClass, $requestBody, $decoder, $prexistingObject, $relationshipToPatch, DataObjectJsonApiDecoder::$REPLACE);
        if ($response instanceof DataObject) {
            return $this->getJsonApiRequest();
        } else {
            return InternalJsonApiController::createJsonApiBodyResponseFrom($response, 200);
        }
    }

    /**
     * @param $objectClass
     * @param $requestBody
     * @param $prexistingObject
     * @param $relationshipToPost
     * @return mixed
     * called after all error checks have been done and the request is legitimate
     */
    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
        $decoder = new DataObjectJsonApiDecoder($this->classToDescriptionMap);
        $response = DataObjectJsonApiBodyDecoder::changeObjectWithTypeFromRequestBody($objectClass, $requestBody, $decoder, $prexistingObject, $relationshipToPost, DataObjectJsonApiDecoder::$ADD);
        return $this->createJsonApiBodyResponseFrom($response, 201);
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        $dataObjectJsonApiDescriptor = new DataObjectJsonApiEncoder($this->classToDescriptionMap, $this->listOfIncludedRelationships);
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToRelationUsingIdentifiersJsonApiBodyArray($objectToDescribe, $requestedRelationName, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    /**
     * @param $objectToDescribe
     * @return mixed
     * called when the user requested a single dataobject
     */
    protected function getDataObject($objectToDescribe) {
        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToSingleObjectJsonApiBodyArray($objectToDescribe, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    /**
     * @param DataObject|null $objectToDescribe
     * @param string $requestedRelationName
     * @return mixed
     * called when the client requested a description of the relation of a dataobject
     */
    function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        $dataObjectJsonApiDescriptor = $this->getDataObjectJsonApiEncoder();
        try {
            $response = DataObjectJsonApiBodyEncoder::dataObjectToRelationJsonApiBodyArray($objectToDescribe, $requestedRelationName, $dataObjectJsonApiDescriptor, (BASE_URL . $this->getApiURLSuffix()));
            return $this->createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    function getDataList($objectClass) {
        if (in_array($objectClass, [Person::class, Institute::class, RepoItemSummary::class, PersonSummary::class])) {
            if (isset($this->filters['scope'])) {
                if ($this->filters['scope'] == 'off') {
                    unset($this->filters['scope']);
                    if ($objectClass == RepoItemSummary::class) {
                        return PermissionFilter::filterThroughCanViewPermissions($objectClass::get());
                    }
                    if (property_exists($objectClass, 'overwriteCanView')) {
                        $objectClass::$overwriteCanView = true;
                    }
                    return $objectClass::get();
                } else if ($this->filters['scope'] == 'on') {
                    unset($this->filters['scope']);
                } else {
                    if ($objectClass == Person::class) {
                        throw new Exception("Only ?filter[scope]=off supported");
                    }
                }
            }
        }
        return InstituteScoper::getAll($objectClass);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        if (!$relationshipToModify) {
            $prexistingObject->delete();
            $this->getResponse()->setStatusCode(204);
            return;
        }

        $decoder = new DataObjectJsonApiDecoder($this->classToDescriptionMap);
        $response = DataObjectJsonApiBodyDecoder::changeObjectWithTypeFromRequestBody($objectClass, $requestBody, $decoder, $prexistingObject, $relationshipToModify, DataObjectJsonApiDecoder::$REMOVE);
        if (is_array($response)) {
            return $this->createJsonApiBodyResponseFrom($response, 403);
        }

        $this->getResponse()->setStatusCode(204);
        return;
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }
}