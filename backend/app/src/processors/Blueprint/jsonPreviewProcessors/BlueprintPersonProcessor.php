<?php

namespace SilverStripe\processors\Blueprint\jsonPreviewProcessors;

use SurfSharekit\Models\Person;
use SurfSharekit\models\webhooks\exceptions\InvalidTypeException;

class BlueprintPersonProcessor extends BlueprintProcessor {
    public function convertDataObjectToJson(): string {

        if (!($this->dataObject instanceof Person)) {
            throw new InvalidTypeException('DataObject is not of type person');
        }

        $data = [
            'uuid' => $this->dataObject->Uuid,
            'firstName' => $this->dataObject->FirstName,
            'surname' => $this->dataObject->Surname,
            'email' => $this->dataObject->Email,
            'secondaryEmail' => $this->dataObject->SecondaryEmail,
            'initials' => $this->dataObject->Initials,
            'linkedInUrl' => $this->dataObject->LinkedInUrl,
            'twitterUrl' => $this->dataObject->TwitterUrl,
            'socialMediaUrl' => $this->dataObject->SocialMediaUrl,
            'researchGateUrl' => $this->dataObject->ResearchGateUrl,
            'formOfAddress' => $this->dataObject->FormOfAddress,
            'academicTitle' => $this->dataObject->AcademicTitle,
            'persistentIdentifier' => $this->dataObject->PersistentIdentifier,
            'orcid' => $this->dataObject->ORCID,
            'isni' => $this->dataObject->ISNI,
            'hogeschoolID' => $this->dataObject->HogeschoolID,
            'phone' => $this->dataObject->Phone,
            'position' => $this->dataObject->Position,
            'personConfig' => $this->convertPersonConfigToArray(),
            'groups' => $this->convertPersonGroupsToArray()
        ];

        return $this->createBlueprintJsonResponse($data);
    }

    /**
     * Converts the PersonConfig of the Person into an array.
     */
    private function convertPersonConfigToArray(): ?array {
        // Check if a PersonConfig is attached.
        if (!$this->dataObject->PersonConfigID) {
            return null;
        }

        $personConfig = $this->dataObject->PersonConfig();
        if (!$personConfig || !$personConfig->exists()) {
            return null;
        }

        return [
            'uuid' => $personConfig->Uuid,
            'emailNotificationsEnabled' => (bool)$personConfig->EmailNotificationsEnabled,
            'notificationVersion' => $personConfig->NotificationVersion,
            'enabledNotifications' => $personConfig->EnabledNotifications,
        ];
    }

    /**
     * Converts the groups of Persons into an array.
     */
    private function convertPersonGroupsToArray(): array {
        // If there are no groups, return empty arrays.
        if (!$this->dataObject->Groups() || !$this->dataObject->Groups()->exists()) {
            return [
                'institutes' => [],
                'defaultRoles' => []
            ];
        }

        $institutes = [];
        $noInstituteRoles = [];

        // Loop through each group.
        foreach ($this->dataObject->Groups() as $group) {
            // Retrieve the default role.
            $defaultRole = $group->DefaultRole();
            if (!$defaultRole || !$defaultRole->exists()) {
                continue;
            }
            $roleKey = $defaultRole->Key;

            // Retrieve the institute (if any).
            $institute = $group->Institute();
            if ($institute && $institute->exists() && !empty($institute->Uuid)) {
                $instituteUuid = $institute->Uuid;
                // Initialize the institute entry if it doesn't exist.
                if (!isset($institutes[$instituteUuid])) {
                    $institutes[$instituteUuid] = [
                        'uuid' => $instituteUuid,
                        'roles' => [],
                    ];
                }
                // Add the role if it's not already in the array.
                if (!in_array($roleKey, $institutes[$instituteUuid]['roles'])) {
                    $institutes[$instituteUuid]['roles'][] = $roleKey;
                }
            } else {
                // Group has no associated institute. Aggregate its role separately.
                if (!in_array($roleKey, $noInstituteRoles)) {
                    $noInstituteRoles[] = $roleKey;
                }
            }
        }

        return [
            'institutes' => array_values($institutes),
            'defaultRoles' => $noInstituteRoles
        ];
    }
}