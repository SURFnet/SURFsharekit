<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Helper\Logger;

/**
 * Dispatcher for change notifications. Routes objects to their specific notifiers.
 */
class ChangeNotifier {
    /**
     * Map a DataObject class (or parent class) to a notifier class.
     */
    private const NOTIFIER_MAP = [
        RepoItem::class => RepoItemChangeNotifier::class,
    ];

    public static function notify(DataObject $dataObject, array $changedFields): void {
        foreach (self::NOTIFIER_MAP as $class => $notifierClass) {
            if ($dataObject instanceof $class) {
                $notifier = new $notifierClass();
                $notifier->notify($dataObject, $changedFields);
                return;
            }
        }
        Logger::debugLog('No notifier registered for ' . get_class($dataObject));
    }
}
