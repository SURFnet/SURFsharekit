<?php

namespace SurfSharekit\Api;

use SilverStripe\api\BaseController;
use SilverStripe\api\ResponseHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Services\Institute\InstituteService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\ScopeCache;

class InstituteDashboardController extends BaseController
{
    private static $url_handlers = [
        'GET /' => 'handleGET',
    ];

    private static $allowed_actions = [
        'handleGET',
    ];

    public static function handleGET(HTTPRequest $request): HTTPResponse {
        $instituteService = new InstituteService();

        $instituteUuid = $request->getVar('instituteId');
        $fromDate = $request->getVar('from');
        $untilDate = $request->getVar('until');

        // Check if instituteUuid is missing
        if (!$instituteUuid) {
            throw new NotFoundException(ApiErrorConstant::GA_BR_002);
        }

        // Check if Institute exists
        /** @var Institute|null $institute */
        $institute = Institute::get()->filter(['Uuid' => $instituteUuid])->first();
        if (!$institute) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_003);
        }

        $member = Security::getCurrentUser();
        if (!$institute->canView($member) || !$member->hasPermissionToViewReports()) {
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001);
        }

        $instituteRangeData = $instituteService->getInstituteCountData($institute->ID, $fromDate, $untilDate);
        $instituteUtmSources = $instituteService->getInstituteUtmSourcesData($institute->ID, $fromDate, $untilDate);

        $genericFilters = [
            "IsRemoved" => false
        ];
        
        if (!empty($fromDate)) {
            $genericFilters['Created:GreaterThanOrEqual'] = $fromDate;
        }

        if (!empty($untilDate)) {
            $genericFilters['Created:LessThanOrEqual'] = $untilDate;
        }

        $instituteTree = Institute::getAllChildInstitutes($institute->Uuid, true);
        $instituteTreeIDs = $instituteTree->getIDList();

        $researchPublications = RepoItem::get()->filter(["RepoType" => RepoItemTypeConstant::RESEARCH_OBJECT, "InstituteID" => $instituteTreeIDs])->filter($genericFilters);
        $publicationRecords = RepoItem::get()->filter(["RepoType" => RepoItemTypeConstant::PUBLICATION_RECORD, "InstituteID" => $instituteTreeIDs])->filter($genericFilters);
        $datasets = RepoItem::get()->filter(["RepoType" => RepoItemTypeConstant::DATASET, "InstituteID" => $instituteTreeIDs])->filter($genericFilters);
        $learningMaterials = RepoItem::get()->filter(["RepoType" => RepoItemTypeConstant::LEARNING_OBJECT, "InstituteID" => $instituteTreeIDs])->filter($genericFilters);

        $allPrimaryRepoItemsWithinPeriod = RepoItem::get()->filter(["RepoType" => RepoItemTypeConstant::PRIMARY_TYPES, "InstituteID" => $instituteTreeIDs])->filter($genericFilters);
        $allPrimaryRepoItemsWithinPeriodExcludingProjects = RepoItem::get()->filter(["RepoType" => [RepoItemTypeConstant::PUBLICATION_RECORD, RepoItemTypeConstant::LEARNING_OBJECT, RepoItemTypeConstant::RESEARCH_OBJECT, RepoItemTypeConstant::DATASET], "InstituteID" => $instituteTreeIDs])->filter($genericFilters);
        $totalActivePersonCount = Institute::getActiveUsersForInstituteTree($institute->Uuid)->filter($genericFilters)->count();


        $jsonResponse = [
            'publicationCount' => $allPrimaryRepoItemsWithinPeriod->count(),
            'activeUsers' => $totalActivePersonCount,
            'downloads' => $instituteRangeData['Downloads'],
            'publicationTypes' => [
                [
                    'labelNL' => 'Afstudeerproducten',
                    'labelEN' => 'Publications',
                    'value' => $publicationRecords->count()
                ],
                [
                    'labelNL' => 'Leermaterialen',
                    'labelEN' => 'Learning materials',
                    'value' => $learningMaterials->count()
                ],
                [
                    'labelNL' => 'Onderzoekspublicaties',
                    'labelEN' => 'Research publications',
                    'value' => $researchPublications->count()
                ],
                [
                    'labelNL' => 'Datasets',
                    'labelEN' => 'Datasets',
                    'value' => $datasets->count()
                ]
            ],
            'publicationStatuses' => [
                [
                    'labelNL' => 'Embargo',
                    'labelEN' => 'Embargo',
                    'value' => $allPrimaryRepoItemsWithinPeriodExcludingProjects->filter(["Status" => "Embargo"])->count()
                ],
                [
                    'labelNL' => 'Concept',
                    'labelEN' => 'Draft',
                    'value' => $allPrimaryRepoItemsWithinPeriodExcludingProjects->filter(["Status" => "Draft"])->count()
                ],
                [
                    'labelNL' => 'Gearchiveerd',
                    'labelEN' => 'Archived',
                    'value' => $allPrimaryRepoItemsWithinPeriodExcludingProjects->filter(["Status" => "Archived"])->count()
                ],
                [
                    'labelNL' => 'Gepubliceerd',
                    'labelEN' => 'Published',
                    'value' => $allPrimaryRepoItemsWithinPeriodExcludingProjects->filter(["Status" => "Published"])->count()
                ]
            ],
            "downloadsPerChannel" => $instituteUtmSources
        ];

        return ResponseHelper::responseSuccess($jsonResponse);
    }
}
