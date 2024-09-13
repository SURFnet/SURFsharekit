<?php

namespace SurfSharekit\notifications;

class NotificationAction {

    const APPROVED = "Approved";
    const DECLINED = "Declined";
    const SUBMITTED = "Submitted";
    const FILL_REQUEST = "FillRequest";
    const REVIEW_REQUEST = "ReviewRequest";
    const RECOVER_REQUEST = "RecoverRequest";
    const RECOVER_REQUEST_APPROVED = "RecoverRequestApproved";
    const RECOVER_REQUEST_DECLINED = "RecoverRequestDeclined";

    /**
     * @return string[]
     */
    public static function getAll(): array {
        return [
            self::APPROVED,
            self::DECLINED,
            self::SUBMITTED,
            self::FILL_REQUEST,
            self::REVIEW_REQUEST,
            self::RECOVER_REQUEST,
        ];
    }
}