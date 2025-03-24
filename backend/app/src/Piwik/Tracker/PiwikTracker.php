<?php

namespace SurfSharekit\Piwik\Tracker;
use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Piwik\PiwikCustomDimensionMapping;
use SurfSharekit\Piwik\CustomEventDimension;

class PiwikTracker {
    public static function trackEvent(
        $url,
        $category,
        $action,
        $name,
        $dimensions = [],
        $forceNewVisit = true,
        \DateTime $forceVisitDataTime = null,
        $forceUserId = false
    ) {
        $tracker = new \MatomoTracker(Environment::getEnv("PIWIK_SITE_ID"), Environment::getEnv("PIWIK_URL"));
        $tracker->setUrl($url);
        $tracker->setUrlReferrer(!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url);

        if ($forceVisitDataTime) {
            $tracker->setForceVisitDateTime($forceVisitDataTime->format("Y-m-d H:i:s"));
        }

        foreach ($dimensions as $eventName => $value) {
            if (!is_scalar($value)) {
                throw new \Exception("Value should be scalar");
            }
            $customDimension = PiwikCustomDimensionMapping::getCustomDimension($eventName);
            $tracker->setCustomDimension($customDimension->getId(), $value);
        }

        $visitorId = substr(md5(uniqid(rand(), true)), 0, \MatomoTracker::LENGTH_VISITOR_ID);
        if ($forceNewVisit) {
            $tracker->forcedVisitorId = $visitorId;
            $tracker->setForceNewVisit();
        }

        if ($forceUserId) {
            $tracker->setUserId($visitorId);
        }

        $tracker->doTrackEvent($category, $action, $name);
    }
}