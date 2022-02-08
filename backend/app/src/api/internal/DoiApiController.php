<?php

namespace SurfSharekit\Api;

use Exception;
use Ramsey\Uuid\Uuid;
use SilverStripe\Security\Security;
use SurfSharekit\Models\GeneratedDoi;
use SurfSharekit\Models\RepoItem;
use UuidExtension;

class DoiApiController extends LoginProtectedApiController {
    private static $url_handlers = [
        'GET $Uuid/doi' => 'getOrCreateDoi'
    ];

    private static $allowed_actions = [
        'getOrCreateDoi'
    ];
    public function getOrCreateDoi() {
        $request = $this->getRequest();
        $uuid = $request->param('Uuid');
        $this->getResponse()->addHeader("content-type", "application/vnd.api+json");

        if (!Uuid::isValid($uuid)) {
            $this->response->setBody(json_encode([
                JsonApi::TAG_ERRORS => [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid ID',
                    JsonApi::TAG_ERROR_DETAIL => 'RepoItem ID is not a valid UUID',
                    JsonApi::TAG_ERROR_CODE => 'DOC_01'
                ]
            ]));
            $this->response->setStatusCode(404);
            return $this->response;
        }

        $repoItem = UuidExtension::getByUuid(RepoItem::class, $uuid);
        if (!$repoItem || !$repoItem->exists()) {
            $this->response->setBody(json_encode([
                JsonApi::TAG_ERRORS => [
                    JsonApi::TAG_ERROR_TITLE => 'Invalid object id',
                    JsonApi::TAG_ERROR_DETAIL => 'The object with the url-specified id could not be found',
                    JsonApi::TAG_ERROR_CODE => 'DOC_02'
                ]
            ]));
            $this->response->setStatusCode(404);
            return $this->response;
        }

        $canGenerateDoi = $repoItem->canGenerateDoi(Security::getCurrentUser());
        if (!$canGenerateDoi) {
            $this->response->setBody(json_encode([
                JsonApi::TAG_ERRORS => [
                    JsonApi::TAG_ERROR_TITLE => 'No permission',
                    JsonApi::TAG_ERROR_DETAIL => 'No permission to retrieve doi for this RepoItem',
                    JsonApi::TAG_ERROR_CODE => 'DOC_03'
                ]
            ]));
            $this->response->setStatusCode(403);
            return $this->response;
        }

        $doi = DoiCreator::getDoiOfRepoItem($repoItem);
        $doiIsGenerated = false;
        if ($doi){
            $generatedDoi = GeneratedDoi::get()->filter(['DOI' => $doi])->first();
            $doiIsGenerated = $generatedDoi && $generatedDoi->exists();
        }
        if (!$doi || !$doiIsGenerated) {
            try {
                $doi = DoiCreator::generateDoiFor($repoItem);
            } catch (Exception $e) {
                $doi = DoiCreator::getDoiOfRepoItem($repoItem);
            }
        }

        if (!$doi) {
            $this->response->setBody(json_encode([
                JsonApi::TAG_ERRORS => [
                    JsonApi::TAG_ERROR_TITLE => 'Something went wrong',
                    JsonApi::TAG_ERROR_DETAIL => 'Please contact your system admin',
                    JsonApi::TAG_ERROR_CODE => 'DOC_04'
                ]
            ]));
            $this->response->setStatusCode(500);
            return $this->response;
        }
        $this->response->setBody(json_encode([
            JsonApi::TAG_DATA => [
                JsonApi::TAG_ID => $doi,
                JsonApi::TAG_TYPE => 'doi',
                JsonApi::TAG_ATTRIBUTES => [
                    'doi' => $doi
                ]
            ]
        ]));
        return $this->response;
    }

}