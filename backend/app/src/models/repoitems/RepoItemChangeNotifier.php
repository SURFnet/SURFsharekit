<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

class RepoItemChangeNotifier extends AbstractChangeNotifier {
    private const ELIGIBLE_TYPES = ['LearningObject', 'PublicationRecord', 'ResearchObject'];

    /**
     * @param RepoItem|DataObject $dataObject
     */
    public function buildPayload(DataObject $dataObject, array $changedFields): ?array {
        $repoItem = $dataObject;

        if (!in_array($repoItem->RepoType, self::ELIGIBLE_TYPES, true)) {
            return null;
        }

        $previousStatus = $changedFields['Status']['before'] ?? null;
        $previousIsRemoved = $changedFields['IsRemoved']['before'] ?? null;
        $wasRemoved = (bool)$previousIsRemoved;

        $statusChanged = array_key_exists('Status', $changedFields)
            && $previousStatus !== $repoItem->Status;
        $isRemovedChanged = array_key_exists('IsRemoved', $changedFields)
            && (bool)$previousIsRemoved !== (bool)$repoItem->IsRemoved;

        $draftToPublished = $statusChanged && $previousStatus === 'Draft' && $repoItem->Status === 'Published';
        $isRestoredFromRemoval = $isRemovedChanged && $wasRemoved && !$repoItem->IsRemoved;
        $removedChanged = $isRemovedChanged
            && $previousIsRemoved !== $repoItem->IsRemoved
            && (!$isRestoredFromRemoval || $repoItem->IsHistoricallyPublished);

        if (!$draftToPublished && !$removedChanged) {
            return null;
        }

        return [
            'className' => $dataObject->ClassName,
            'identifier' => $repoItem->Uuid,
        ];
    }
}
