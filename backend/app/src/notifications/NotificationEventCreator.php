<?php

namespace SurfSharekit\Models\Helper;

use Exception;
use OpenApi\Annotations\Contact;
use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\BulkAction;
use SurfSharekit\Models\Claim;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\Task;

class NotificationEventCreator {

    private static $instance = null;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new NotificationEventCreator();
        }

        return self::$instance;
    }

    public function create(String $type, DataObject $object) {
        switch ($type) {
            case Constants::REPO_ITEM_STATUS_CHANGED_EVENT:
                $this->handleRepoItemStatusChangeEvent($type, $object);
                break;
            case Constants::SANITIZATION_PROCESS_END_EVENT:
                $this->handleSanitizationProcessEndEvent($type, $object);
                break;
            case Constants::CLAIM_STATUS_CHANGED_EVENT:
                $this->handleClaimProcessChangedEvent($type, $object);
                break;
            case Constants::FILL_REQUEST_CREATED_EVENT:
                $this->handleFillRequestCreatedEvent($type, $object);
                break;
            case Constants::RECOVER_REQUEST_CREATED_EVENT:
                $this->handleRecoverRequestCreatedEvent($type, $object);
                break;
            case Constants::RECOVER_REQUEST_APPROVED_EVENT:
                $this->handleRecoverRequestApprovedEvent($type, $object);
                break;
            case Constants::RECOVER_REQUEST_DECLINED_EVENT:
                $this->handleRecoverRequestDeclinedEvent($type, $object);
                break;
            default: throw new Exception("Unknown EventType '$type'");
        }
    }

    private function createEvent(String $type, DataObject $object) {
        $comparisonUuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, $type . '-' . $object->getClassName() . '-' . $object->ID);

        // Invalidate all events of the same type for the same object that came before
        DB::query("
                UPDATE SurfSharekit_Event
                SET Invalidated = true
                WHERE ID IN (SELECT ID FROM SurfSharekit_Event WHERE ComparisonUuid = '$comparisonUuid')
        ");

        $newEvent = new Event();
        $newEvent->Type = $type;
        $newEvent->ObjectID = $object->ID;
        $newEvent->ObjectClass = $object->getClassName();
        $newEvent->ComparisonUuid = $comparisonUuid->toString();
        $newEvent->write();
    }

    private function handleClaimProcessChangedEvent(String $type, DataObject $object) {
        if (!$object instanceof Claim) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $statusesToCreateEventFor = [
            'Submitted',
            'Declined',
            'Approved'
        ];

        if ($object->isChanged('Status') && in_array($object->Status, $statusesToCreateEventFor)) {
            $this->createEvent($type, $object);
        }
    }

    private function handleRepoItemStatusChangeEvent(String $type, DataObject $object) {
        if (!$object instanceof RepoItem) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $statusesToCreateEventFor = [
            'Submitted',
            'Declined',
            'Approved'
        ];

        $oldStatus = $object->getChangedFields()['Status']['before'] ?? null;

        if ($oldStatus && $oldStatus == 'Revising') {
            // do not create any notification events for RepoItems coming from the 'Revising' Status
            return;
        }

        if ($object->isChanged('Status') && in_array($object->Status, $statusesToCreateEventFor)) {
            $this->createEvent($type, $object);
        }
    }

    private function handleSanitizationProcessEndEvent(string $type, DataObject $object) {
        if (!$object instanceof BulkAction) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $this->createEvent($type, $object);
    }

    private function handleRecoverRequestCreatedEvent(string $type, DataObject $object) {
        if (!$object instanceof RepoItem) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $this->createEvent($type, $object);
    }

    private function handleRecoverRequestApprovedEvent(string $type, DataObject $object) {
        if (!$object instanceof RepoItem) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $this->createEvent($type, $object);
    }

    private function handleRecoverRequestDeclinedEvent(string $type, DataObject $object) {
        if (!$object instanceof RepoItem) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $this->createEvent($type, $object);
    }

    private function handleFillRequestCreatedEvent(string $type, DataObject $object) {
        if (!$object instanceof RepoItem) {
            throw new Exception("EventType '$type' cannot be used for DataObject '$object->ClassName'");
        }

        $this->createEvent($type, $object);
    }
}