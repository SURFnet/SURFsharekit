<?php

namespace SilverStripe\processors\Blueprint;

use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionRole;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;

class BlueprintPersonConverterProcessor extends BlueprintConverterProcessor
{

    public function getTargetClass()
    {
        return Person::class;
    }

    /**
     * Converts an existing BlueprintPerson to a Person object.
     *
     * @param $blueprint
     * @return mixed|Person|null
     */
    public function convert($blueprint)
    {
        $json = json_decode($blueprint->JSON, true);
        if (!$json || !isset($json['data'])) {
            return null;
        }

        $data = $json['data'];

        // Check if email is empty. Person can't be created if email is null
        if (!$data['email']) return null;

        // If person exists, overwrite. Else, create a new Person object
        $person = Institute::get()->filter('Uuid', $data['uuid'])->first();
        if (!$person) {
            $person = Institute::create();
        }

        $person->Uuid = $data['uuid'];
        $person->FirstName = $data['firstName'] ?? '';
        $person->Surname = $data['surname'] ?? '';
        $person->Email = $data['email'] ?? '';
        $person->SecondaryEmail = $data['secondaryEmail'] ?? '';
        $person->Initials = $data['initials'] ?? '';
        $person->LinkedInUrl = $data['linkedInUrl'] ?? '';
        $person->TwitterUrl = $data['twitterUrl'] ?? '';
        $person->SocialMediaUrl = $data['socialMediaUrl'] ?? '';
        $person->ResearchGateUrl = $data['researchGateUrl'] ?? '';
        $person->FormOfAddress = $data['formOfAddress'] ?? '';
        $person->AcademicTitle = $data['academicTitle'] ?? '';
        $person->PersistentIdentifier = $data['persistentIdentifier'] ?? '';
        $person->ORCID = $data['orcid'] ?? '';
        $person->ISNI = $data['isni'] ?? '';
        $person->HogeschoolID = $data['hogeschoolID'];
        $person->Phone = $data['phone'] ?? '';
        $person->Position = $data['position'] ?? '';

        $person->GeneratedThroughBlueprint = true;
        $person->GeneratedBy = Security::getCurrentUser() ? Security::getCurrentUser()->Email : null;

        $person->write();

        $this->appendGroupsToPerson($data['groups'], $person);
        $this->appendPersonConfigToPerson($data['personConfig'], $person);

        return $person;
    }

    /**
     * function to append the Groups to a Person from the given data JSON
     *
     * @param array $data       The institute UUID, or null for groups without an institute.
     * @param string $roleKey   The key of the PermissionRole.
     * @return Group|null       The found Group or null if not found.
     */
    private function appendGroupsToPerson(array $data, Person $person): void {
        if (empty($data)) {
            return;
        }

        // Process groups with an institute.
        if (!empty($data['institutes']) && is_array($data['institutes'])) {
            foreach ($data['institutes'] as $instituteData) {

                // Efficiently retrieve the institute UUID.
                if (!($instituteUuid = $instituteData['uuid'] ?? null)) continue;
                if (empty($instituteData['roles']) || !is_array($instituteData['roles'])) continue;

                // For every role described for institute, search the Group and append to Person object.
                foreach ($instituteData['roles'] as $roleKey) {
                    if ($group = $this->findGroup($instituteUuid, $roleKey)) {
                        $person->Groups()->add($group);
                    }
                }
            }
        }

        // Process groups that are based solely on default roles.
        if (!empty($data['defaultRoles']) && is_array($data['defaultRoles'])) {

            // For every role described, search the Group and append to Person object.
            foreach ($data['defaultRoles'] as $roleKey) {
                if ($group = $this->findGroup(null, $roleKey)) {
                    $person->Groups()->add($group);
                }
            }
        }
    }

    /**
     * function to append a PersonConfig to a Person when converting the BlueprintPerson
     *
     * @param array $data
     * @param Person $person
     * @return void
     */
    private function appendPersonConfigToPerson(array $data, Person $person): void
    {
        if (empty($data['uuid'])) return;

        // Search for an existing PersonConfig
        $existingConfig = PersonConfig::get()->filter('Uuid', $data['uuid'])->first();

        // Use the existing config if it exists and is not already coupled to a person.
        if ($existingConfig && !Person::get()->filter('PersonConfigID', $existingConfig->ID)->exists()) {
            $personConfig = $existingConfig;
        } else {
            $personConfig = $this->createPersonConfig($data);
        }

        // Link the PersonConfig to the Person
        $person->PersonConfigID = $personConfig->ID;
        $person->write();
    }

    /**
     * Helper function to find a Group given an optional institute UUID and a role key.
     *
     * @param string|null $instituteUuid The institute UUID, or null for groups without an institute.
     * @param string      $roleKey       The key of the PermissionRole.
     * @return Group|null                The found Group or null if not found.
     */
    private function findGroup(?string $instituteUuid, string $roleKey): ?Group {
        $permissionRole = PermissionRole::get()->filter('Key', $roleKey)->first();

        // Skip if PermissionRole doesn't exist.
        if (!$permissionRole) return null;

        // Apply filters to find Group
        $filter = ['DefaultRoleID' => $permissionRole->ID];
        if ($instituteUuid) {
            $filter['InstituteUuid'] = $instituteUuid;
        }

        // If group has been found, return group, else return null.
        $group = Group::get()->filter($filter)->first();
        return $group instanceof Group ? $group : null;
    }

    /**
     * Creates a new PersonConfig.
     *
     * @param $data
     * @return PersonConfig
     */

    private function createPersonConfig($data): PersonConfig {
        $personConfig = PersonConfig::create();
        $personConfig->Uuid = $data['uuid'];
        $personConfig->EmailNotificationsEnabled = (bool)($data['emailNotificationsEnabled'] ?? false);
        $personConfig->NotificationVersion = 1;
        $personConfig->EnabledNotifications = json_encode($data['enabledNotifications']) ?? [];
        $personConfig->write();

        return $personConfig;
    }
}