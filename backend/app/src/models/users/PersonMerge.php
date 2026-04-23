<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use UuidExtension;
use Zooma\SilverStripe\Models\ApiObject;

class PersonMerge extends ApiObject {
    function execute() {
        if (!is_array($this->MergePersonIds) || count($this->MergePersonIds) < 2) {
            throw new Exception("POST at least 2 mergePersonIds");
        }
        $personsToMerge = [];

        // Get all users that can be merged with the current user
        $currentUser = Security::getCurrentUser();
        $firstName = $currentUser->FirstName;
        $surname = $currentUser->Surname;
        $scopedInstituteIds = $currentUser->getInstituteIDs();
        $scopedInstituteIds[] = -1; // trick to add at least 1 institue
        Logger::infoLog($scopedInstituteIds, __CLASS__, __FUNCTION__);
        $personsAllowedToMerge = Person::get()
            ->leftJoin('Group_Members', '`Group_Members`.`MemberID` = Member.ID')
            ->leftJoin('Group', '`Group`.`ID` = `Group_Members`.`GroupID`')
            ->leftJoin('SurfSharekit_Institute', '`SurfSharekit_Institute`.`ID` = `Group`.`InstituteID`')
            ->where([
                "(MATCH (Member.FirstName) AGAINST (? IN NATURAL LANGUAGE MODE) + MATCH (Member.Surname) AGAINST (? IN NATURAL LANGUAGE MODE)) >= ?" =>
                    [$firstName, $surname, 10],
                "SurfSharekit_Person.HasLoggedIn" => 0,
                "Member.ID != ?" => $currentUser->ID,
                "Member.ID NOT IN (SELECT DISTINCT MemberID FROM Group_Members
                    INNER JOIN `Group` ON `Group`.ID = Group_Members.GroupID 
                    INNER JOIN PermissionRole ON PermissionRole.ID = `Group`.DefaultRoleID 
                    WHERE PermissionRole.Key IN ('Supporter', 'Siteadmin'))",
                "SurfSharekit_Institute.ID in (?)" => implode(',', $scopedInstituteIds)
            ]);
        $dataQuery = $personsAllowedToMerge->dataQuery();
        $params = [];
        $sql = $dataQuery->sql($params);
        Logger::infoLog("Suggestion query = " . $sql . " | Parameters = " . json_encode($params), __CLASS__, __FUNCTION__);
        
        foreach ($this->MergePersonIds as $id) {
            $person = UuidExtension::getByUuid(Person::class, $id);
            if (!($person && $person->exists())) {
                throw new Exception("Person with ID $id not found");
            }
            // Dont do these checks if the person is in the onboarding stage
            if ($person->HasFinishedOnboarding) {
                if ($person->HasLoggedIn) {
                    throw new Exception("Cannot merge active person {$person->Name}");
                }
                if (!$person->canMerge(Security::getCurrentUser())) {
                    throw new Exception("Cannot merge person {$person->Name}");
                }
                if (!$person->canEdit(Security::getCurrentUser())) {
                    throw new Exception("Cannot edit person {$person->Name}");
                }
            } else {
                if ($personsAllowedToMerge->filter('ID', $person->ID)->count() == 0 && !$person == $currentUser) {
                    throw new Exception("Cannot merge person {$person->Name}");
                }
            }
            Logger::infoLog("Person to merge: " . $person->Name, __CLASS__, __FUNCTION__);
            $personsToMerge[] = $person;
        }
        
        try {
            DB::get_conn()->transactionStart();
            $mainPerson = $personsToMerge[0];
            $this->clearDuplicateEmailOnMerge($mainPerson, $personsToMerge);
            $this->setFieldsOn($mainPerson);
            $mainPerson->write();

            $this->claimGroups($mainPerson, $personsToMerge);
            $this->claimChannels($mainPerson, $personsToMerge);
            // Change values set before ownership
            $this->claimObjectsOfType($mainPerson, $personsToMerge, RepoItemMetaFieldValue::class);
            $this->generateNewRepoItemSummariesForRepoItemMetafieldValues($mainPerson);
            $this->claimRepoItems($mainPerson, $personsToMerge);
            $this->claimObjectsOfType($mainPerson, $personsToMerge, DefaultMetaFieldOptionPart::class);
            $this->claimObjectsOfType($mainPerson, $personsToMerge, ReportFile::class);
            $this->claimObjectsOfType($mainPerson, $personsToMerge, GeneratedDoi::class);

            //todo: Should mark the merged profiles as deleted inside the cache instead of just settings
            // all PersonID's of the cache nodes to the ID of the mainperson. The cache nodes of the mainperson should be regenerated
            $this->claimObjectsOfType($mainPerson, $personsToMerge, Cache_RecordNode::class);
            $this->markMergedPersonsRemoved($mainPerson, $personsToMerge);

            DB::get_conn()->transactionEnd();
            // Automatically generated because of updated objects
//        $this->claimObjectsOfType($mainPerson, $allButMainPerson, RepoItemSummary::class, 'Owner');
//        $this->claimObjectsOfType($mainPerson, $allButMainPerson, PersonSummary::class, 'Person');
//        $this->claimObjectsOfType($mainPerson, $allButMainPerson, SearchObject::class);
        } catch (Exception $e) {
            Logger::errorLog("An error occurred while merging profiles: " . $e->getMessage());
            DB::get_conn()->transactionRollback();
        }

    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    private function setFieldsOn($mainPerson) {
        $mainPerson->SurnamePrefix = $this->SurnamePrefix;
        $mainPerson->Surname = $this->Surname;
        $mainPerson->FirstName = $this->FirstName;
        $mainPerson->Email = $this->Email;
        $mainPerson->LinkedInUrl = $this->LinkedInUrl;
        $mainPerson->TwitterUrl = $this->TwitterUrl;
        $mainPerson->SocialMediaUrl = $this->SocialMediaUrl;
        $mainPerson->ResearchGateUrl = $this->ResearchGateUrl;
        $mainPerson->City = $this->City;
        $mainPerson->SkipEmail = $this->SkipEmail;
        $mainPerson->DisableEmailChange = $this->DisableEmailChange;
        $mainPerson->Phone = $this->Phone;
        $mainPerson->FormOfAddress = $this->FormOfAddress;
        $mainPerson->AcademicTitle = $this->AcademicTitle;
        $mainPerson->Initials = $this->Initials;
        $mainPerson->SecondaryEmail = $this->SecondaryEmail;
        $mainPerson->PersistentIdentifier = $this->PersistentIdentifier;
        $mainPerson->ORCID = $this->ORCID;
        $mainPerson->ISNI = $this->ISNI;
        $mainPerson->HogeschoolID = $this->HogeschoolID;
        $mainPerson->Position = $this->Position;
    }

    /**
     * Clear duplicate emails on merged-away persons before writing the main person.
     * This avoids SilverStripe's unique identifier (Email) validation error.
     */
    private function clearDuplicateEmailOnMerge($mainPerson, array $mergePersons) {
        if (!$this->Email) {
            return;
        }

        foreach ($mergePersons as $person) {
            if ($person->ID === $mainPerson->ID || !$person->Email) {
                continue;
            }

            if (strcasecmp($person->Email, $this->Email) === 0) {
                $person->Email = null;
                $person->write();
            }
        }
    }

    private function markMergedPersonsRemoved($mainPerson, array $mergePersons) {
        $idsToRemove = [];

        foreach ($mergePersons as $person) {
            if ($person->ID === $mainPerson->ID) {
                continue;
            }
            $idsToRemove[] = (int)$person->ID;
        }

        if (!empty($idsToRemove)) {
            $idsToRemoveString = implode(',', $idsToRemove);
            DB::query("UPDATE Member SET IsRemoved = 1 WHERE ID IN ($idsToRemoveString)");
        }
    }

    private function claimGroups($mainPerson, array $mergePersons) {
        foreach ($mergePersons as $person) {
            foreach ($person->Groups() as $group) {
                $group->Members()->add($mainPerson);
            }
            $person->Groups()->removeAll();
        }
        Logger::debugLog("Merged user has " . $mainPerson->Groups()->count() . " groups");
    }

    private function claimChannels($mainPerson, array $mergePersons) {
        foreach ($mergePersons as $person) {
            foreach (Channel::get()->filter('Members.ID', $person->ID) as $channel) {
                $channel->Members()->add($mainPerson);
                $channel->Members()->remove($person);
            }
        }
        Logger::debugLog("Merged user has " . $mainPerson->Groups()->count() . " groups");
    }

    private function claimRepoItems($mainPerson, array $mergePersons) {
        foreach ($mergePersons as $person) {
            foreach (RepoItem::get()->filter('OwnerID', $person->ID) as $repoItem) {
                $repoItem->OwnerID = $mainPerson->ID;
                $repoItem->OwnerUuid = $mainPerson->Uuid;
                if ($repoItem->CreatedByID == $person->ID) {
                    $repoItem->CreatedByID = $mainPerson->ID;
                    $repoItem->CreatedByUuid = $mainPerson->Uuid;
                }
                $repoItem->SkipValidation = true;
                $repoItem->write(false, false, true);
                Logger::debugLog("repoItem (" . $repoItem->RepoType . ") " . $repoItem->Title);
            }

            foreach (RepoItem::get()->filter('CreatedByID', $person->ID) as $repoItem) {
                /** @var RepoItem $repoItem */
                $repoItem->CreatedByID = $mainPerson->ID;
                $repoItem->CreatedByUuid = $mainPerson->Uuid;
                $repoItem->SkipValidation = true;
                $repoItem->write(false, false, true);
                Logger::debugLog("repoItem (" . $repoItem->RepoType . ") " . $repoItem->Title);
            }
        }
        Logger::debugLog("Merged user has ". RepoItem::get()->filter('OwnerID', $mainPerson->ID)->count() . " repoItems");
    }

    private function claimObjectsOfType($mainPerson, array $mergePersons, $objectClass, $fieldName = 'Person') {
        $idFieldName = $fieldName . 'ID';
        $uuidFieldName = $fieldName . 'Uuid';

        foreach ($mergePersons as $person) {
            foreach ($objectClass::get()->filter($idFieldName, $person->ID) as $object) {
                $object->$idFieldName = $mainPerson->ID;
                $object->$uuidFieldName = $mainPerson->Uuid;
                $object->write(false, false, true);
            }
        }
        Logger::debugLog("Merged user has ". $objectClass::get()->filter($idFieldName, $mainPerson->ID)->count() . " $objectClass");
    }



    private function generateNewRepoItemSummariesForRepoItemMetafieldValues($mainPerson) {
        foreach (RepoItem::get()->filter('RepoItemMetaFields.RepoItemMetaFieldValues.PersonID', $mainPerson->ID) as $repoItemInfluencedByEditOfValue) {
            if($repoItemInfluencedByEditOfValue->RepoType == 'RepoItemPerson') {
                $repoItemInfluencedByEditOfValue->Title = $mainPerson->Name;
            }
            $repoItemInfluencedByEditOfValue->SkipValidation = true;
            $repoItemInfluencedByEditOfValue->write(false, false, true); // generate new summary
            if (!in_array($repoItemInfluencedByEditOfValue,Constants::MAIN_REPOTYPES)){
                foreach(RepoItem::get()->filter('RepoItemMetaFields.RepoItemMetaFieldValues.RepoItemID', $repoItemInfluencedByEditOfValue->ID) as $parentRepoItem){
                    $parentRepoItem->SkipValidation = true;
                    $parentRepoItem->write(false, false, true);
                }
            }
        }
    }
}