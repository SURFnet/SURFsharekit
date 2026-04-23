<?php

namespace SilverStripe\api\internal;

use SilverStripe\api\BaseController;
use SilverStripe\api\ResponseHelper;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Piwik\Events\DownloadLinkEvent;
use SurfSharekit\Piwik\Tracker\PiwikTracker;
use SurfSharekit\ShareControl\ShareControlDownloadService;
use Throwable;

class LinkController extends BaseController {
    private static $url_handlers = [
        'GET $Uuid!' => 'getLink',
    ];
    private static $allowed_actions = [
        "getLink",
    ];
    protected $authenticationEnabled = false;

    public function getLink(HTTPRequest $request): HTTPResponse {
        $repoItemLinkUuid = $request->param("Uuid");
        $utmSource = $request->getVar("utm_source");
        $repoItemService = RepoItemService::create();

        /** @var null|RepoItem $repoItemLink */
        $repoItemLink = $repoItemService->findByUuid($repoItemLinkUuid);
        if (!$repoItemLink) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        /** @var RepoItem $parentRepoItem */
        $parentRepoItem = $repoItemLink->getActiveParent();
        if (!$parentRepoItem || $parentRepoItem->IsRemoved) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $this->setUserFromRequest($request);
        $member = Security::getCurrentUser();
        if ((!$parentRepoItem->IsPublic || $parentRepoItem->Status != "Published") && !$parentRepoItem->canView($member)) {
            throw new ForbiddenException(ApiErrorConstant::GA_FB_001);
        }

        $redirectUrlRepoItemMetaFieldValue = RepoItemMetaFieldValue::get()
            ->innerJoin("SurfSharekit_RepoItemMetaField", "SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = rimf.ID", "rimf")
            ->innerJoin("SurfSharekit_MetaField", "rimf.MetaFieldID = mf.ID AND mf.JsonKey = 'url'", "mf")
            ->innerJoin("SurfSharekit_RepoItem", "rimf.RepoItemID = ri.ID", "ri")
            ->where([
                "ri.ID" => $repoItemLink->ID,
                "SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => false
            ])->first();

        if (!$redirectUrlRepoItemMetaFieldValue) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $redirectUrl = $redirectUrlRepoItemMetaFieldValue->Value;
        if (!$redirectUrl) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002);
        }

        $rootInstituteId = $parentRepoItem->Institute->RootInstitute->Uuid;

        try {
            $downloadEvent = new DownloadLinkEvent(
                $repoItemLink->Uuid,
                $parentRepoItem->Uuid,
                $parentRepoItem->RepoType,
                $rootInstituteId,
                $utmSource ?? "",
                $redirectUrl
            );

            PiwikTracker::trackDownload(
                Controller::join_links(Environment::getEnv("SS_BASE_URL"), $this->getRequest()->getURL()),
                $downloadEvent
            );
        } catch (Throwable $e) {
            LogItem::warnLog("Failed to send Piwik event for RepoItemLink $repoItemLink->Uuid: {$e->getMessage()}", __CLASS__, __FUNCTION__);
        }

        $downloadService = ShareControlDownloadService::create();
        if ($downloadService->isShareControlUrl($redirectUrl)) {
            LogItem::debugLog("Streaming ShareControl file for RepoItemLink $repoItemLink->Uuid from $redirectUrl", __CLASS__, __FUNCTION__);
            return $downloadService->streamFile($redirectUrl, $repoItemLink);
        }

        return ResponseHelper::responsePermanentRedirect($redirectUrl);
    }
}