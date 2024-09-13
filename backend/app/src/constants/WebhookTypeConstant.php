<?php

namespace SurfSharekit\constants;

class WebhookTypeConstant {
    const CREATE = "Create";
    const UPDATE = "Update";
    const DELETE = "Delete";

    /**
     * @return string[]
     */
    public static function getAll(): array {
        return [
            self::CREATE,
            self::UPDATE,
            self::DELETE
        ];
    }
}