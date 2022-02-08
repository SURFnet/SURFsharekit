<?php

namespace SurfSharekit\Api;

use DataObjectJsonApiBodyDecoder;
use DataObjectJsonApiDecoder;
use DataObjectJsonApiEncoder;
use DefaultMetaFieldOptionPartJsonApiDescription;
use GroupJsonApiDescription;
use InstituteJsonApiDescription;
use PersonJsonApiDescription;
use MetaFieldJsonApiDescription;
use MetaFieldOptionJsonApiDescription;
use MetaFieldTypeJsonApiDescription;
use RepoItemJsonApiDescription;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\Template;
use SurfSharekit\Models\TemplateMetaField;
use TemplateJsonApiDescription;
use TemplateMetaFieldJsonApiDescription;
use Throwable;

/**
 * Class OperationsJsonApiController
 * @package SurfSharekit\Api
 * @deprecated Currently not in use
 * Controller to handle the Operation Extension of JsonApi
 */
class OperationsJsonApiController extends CORSController {
    var $classToDescriptionMap;

    public function __construct() {
        parent::__construct();

        $this->classToDescriptionMap = [Group::class => new GroupJsonApiDescription(),
            Institute::class => new InstituteJsonApiDescription(),
            Template::class => new TemplateJsonApiDescription(),
            TemplateMetaField::class => new TemplateMetaFieldJsonApiDescription(),
            MetaField::class => new MetaFieldJsonApiDescription(),
            Person::class => new PersonJsonApiDescription(),
            MetaFieldType::class => new MetaFieldTypeJsonApiDescription(),
            MetaFieldOption::class => new MetaFieldOptionJsonApiDescription(),
            DefaultMetaFieldOptionPart::class => new DefaultMetaFieldOptionPartJsonApiDescription(),
            RepoItem::class => new RepoItemJsonApiDescription()];
    }

    private static $url_handlers = [
        '' => 'postOperations',
    ];

    private static $allowed_actions = [
        'postOperations',
    ];

    public function postOperations() {
        $request = $this->getRequest();

        if ($request->getHeader('Content-Type') != JsonApiOperations::CONTENT_TYPE) {
            $this->getResponse()->setStatusCode(415);
            $response = static::unsupportedTypeError();
        } else {
            if ($requestBody = json_decode($request->getBody(), true)) {
                if (isset($requestBody[JsonApiOperations::TAG_ATOMIC_OPERATION]) && $postedOperationsJson = $requestBody[JsonApiOperations::TAG_ATOMIC_OPERATION]) {
                    try {
                        DB::get_conn()->transactionStart();
                        $decoder = new DataObjectJsonApiDecoder($this->classToDescriptionMap);
                        $encoder = new DataObjectJsonApiEncoder($this->classToDescriptionMap);
                        $response = [JsonApiOperations::TAG_ATOMIC_RESULTS => []];
                        $runIntoErrors = false;
                        foreach ($postedOperationsJson as $postedOperationJson) {
                            $operationResult = DataObjectJsonApiBodyDecoder::handleOperation($postedOperationJson, $decoder);
                            if (is_object($operationResult)) {//created or updated an object
                                $response[JsonApiOperations::TAG_ATOMIC_RESULTS][] = $encoder->describeDataObjectAsData($operationResult);
                            } else if (is_array($operationResult)) { // errors
                                unset($response[JsonApiOperations::TAG_ATOMIC_RESULTS]);
                                $runIntoErrors = true;
                                $response = $operationResult;
                                break;
                            }
                        }
                        if (!$runIntoErrors) {
                            DB::get_conn()->transactionEnd();
                            $this->getResponse()->setStatusCode(200);
                        } else {
                            DB::get_conn()->transactionRollback();
                        }
                    } catch (Throwable $exception) {
                        DB::get_conn()->transactionRollback();
                    }
                } else {
                    $response = static::missingOperationsError();
                    $this->getResponse()->setStatusCode(400);
                }
            } else {
                $response = InternalJsonApiController::missingRequestBodyError();
                $this->getResponse()->setStatusCode(400);
            }
        }

        return InternalJsonApiController::createJsonApiBodyResponseFrom($response, 200);
    }

    private static function unsupportedTypeError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Unsupported Content-Type',
                    JsonApi::TAG_ERROR_DETAIL => 'Please specify Content-Type in your header as "application/vnd.api+json;ext=https://jsonapi.org/ext/atomic"',
                    JsonApi::TAG_ERROR_CODE => 'OJAC_001',
                    JsonApi::TAG_LINKS => [
                        'href' => 'https://github.com/json-api/json-api/pull/1437/files?short_path=3258e77#diff-3258e77d8c1edf3edfa2dda4b957adce',
                        'meta' => [
                            'description' => 'A link describing the of the JSON;API operations extensions protocol this API uses to communicate'
                        ]
                    ]
                ]
            ]
        ];
    }

    private static function missingOperationsError() {
        return [
            JsonApi::TAG_ERRORS => [
                [
                    JsonApi::TAG_ERROR_TITLE => 'Missing operations',
                    JsonApi::TAG_ERROR_DETAIL => "Missing 'atomic:operations' operations array in POST body, or is empty",
                    JsonApi::TAG_ERROR_CODE => 'OJAC_002'
                ]
            ]
        ];
    }
}