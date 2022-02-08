<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class RepoItemMetaFieldValue
 * @package SurfSharekit\Models
 * @method RepoitemMetaField RepoitemMetaField()
 * @method MetaFieldOption MetaFieldOption()
 * @method Institute Institute()
 * DataObject representing a part of a @see RepoItemMetaField
 * e.g. the question 'What languages does this RepoItem contain?' could have more than one @see RepoItemMetaFieldValue, but only one RepoItemMetaField
 */
class RepoItemMetaFieldValue extends DataObject {
    private static $table_name = 'SurfSharekit_RepoItemMetaFieldValue';
    private static $default_sort = 'SortOrder ASC';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $db = [
        "Value" => 'Text',
        "SortOrder" => 'Int(0)',
        "IsRemoved" => 'Boolean(0)'
    ];

    private static $has_one = [
        'RepoItem' => RepoItem::class,
        'MetaFieldOption' => MetaFieldOption::class,
        'RepoItemMetaField' => RepoItemMetaField::class,
        'RepoItemFile' => RepoItemFile::class,
        'Person' => Person::class,
        'Institute' => Institute::class
    ];

    private static $summary_fields = [
        'SummaryFieldValue' => "Value"
    ];

    private static $indexes = [
        'FulltextSearch' => [
            'type' => 'fulltext',
            'columns' => ['Value']
        ]
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName('RepoItemID');
        $fields->removeByName('RepoItemMetaFieldID');

        $metaFieldOptionField = $fields->dataFieldByName('MetaFieldOptionID');
        $metaFieldOptionField->setDisabled(true);
        $repoItemMetaField = $this->RepoItemMetaField();
        if ($repoItemMetaField && $repoItemMetaField->exists()) {
            $metaField = $repoItemMetaField->MetaField();
            if ($metaField && $metaField->exists()) {
                $filterBy = ['IsRemoved' => 0];
                if ($this->MetaFieldOptionID) {
                    // add current ID to filter so it returns even if removed = 1
                    $filterBy['ID'] = $this->MetaFieldOptionID;
                }
                $metaFieldOptions = $metaField->MetaFieldOptions()->filterAny($filterBy);

                if ($metaFieldOptions->count()) {
                    if ($metaFieldOptionField->hasMethod('setSource')) {
                        $metaFieldOptionField->setSource($metaFieldOptions);
                        $metaFieldOptionField->setDisabled(false);
                    }
                }
            }
        }

        $fields->changeFieldOrder(['Value', 'MetaFieldOptionID', 'RepoItemID', 'RepoItemMetaFieldID']);

        return $fields;
    }

    public function getSummaryFieldValue() {
        if ($this->MetaFieldOption() && $this->MetaFieldOption()->getField("Value")) {
            return $this->MetaFieldOption()->getField("Value") ?? $this->MetaFieldOption()->Label_NL;
        } else if ($this->Person() && $this->Person()->exists()) {
            return $this->Person()->Name;
        } else if ($this->Institute() && $this->Institute()->exists()) {
            return $this->Institute()->Title;
        } else if ($this->RepoItem() && $this->RepoItem()->exists()) {
            return $this->RepoItem()->Title;
        } else if ($this->RepoItemFile() && $this->RepoItemFile()->exists()) {
            return json_encode($this->RepoItemFile()->getPublicStreamURL());
        } else {
            return $this->getField("Value");
        }
    }

    public function getUuidOfConnectedItem() {
        if ($this->MetaFieldOption() && $this->MetaFieldOption()->getField("Value")) {
            return $this->MetaFieldOptionUuid;
        } else if ($this->Person() && $this->Person()->exists()) {
            return $this->PersonUuid;
        } else if ($this->Institute() && $this->Institute()->exists()) {
            return $this->InstituteUuid;
        } else if ($this->RepoItem() && $this->RepoItem()->exists()) {
            return $this->RepoItemUuid;
        } else if ($this->RepoItemFile() && $this->RepoItemFile()->exists()) {
            return $this->RepoItemFileUuid;
        }
        return null;
    }

    public function getRelatedObjectSummary() {
        if (($repoItem = $this->RepoItem()) && $repoItem->exists()) {
            /** @var RepoItem $repoItem */
            return $repoItem->Summary;
        } else if (($repoItemFile = $this->RepoItemFile()) && $repoItemFile->exists()) {
            // TODO, use $repoItemFile->Summary to get this object/array?
            return [
                'title' => $repoItemFile->Name,
                'url' => $repoItemFile->getStreamURL(),
                'publicUrl' => $repoItemFile->getPublicStreamURL(),
                'size' => $repoItemFile->Size,
            ];
        } else if (($person = $this->Person()) && $person->exists()) {
            // TODO, use $person->Summary to get this object/array?
            return [
                'name' => $person->Name,
                'imageURL' => $person->PersonImage->getStreamURL(),
                'id' => $person->Uuid
            ];
        } else if (($institute = $this->Institute()) && $institute->exists()) {
            /** @var Institute $institute */
            return $institute->Summary;
        } else if (($option = $this->MetaFieldOption()) && $option->exists()) {
            // TODO, use $metafieldOption->Summary to get this object/array?
            return [
                'value' => $option->Title,
                'label' => $option->Label_NL
            ];
        }
        return null;
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        $this->updateAttributeOfRepoItems();
        if (!$this->DisableForceWriteRepoItem) {
            $this->forceWriteRepoItem();
        }
    }

    /**
     * This method stores its own value in the connected repoItem
     * @param array $repoItems if set, not all repoitems will be updated, but only the ones given in the array
     */
    public function updateAttributeOfRepoItems($repoItems = []) {
        if ($this->IsRemoved) {
            return;
        }
        $repoItemMetaField = $this->RepoItemMetaField();
        $metaField = $repoItemMetaField->MetaField();

        $fieldName = null;

        //Logger::debugLog($metaField->AttributeKey);
        switch ($metaField->AttributeKey) {
            case 'Title':
                $fieldName = 'Title';
                break;
            case 'Alias':
                $fieldName = 'Alias';
                break;
            case 'Subtitle':
                $fieldName = 'Subtitle';
                break;
            case 'EmbargoDate':
                $fieldName = 'EmbargoDate';
                break;
            case 'InstituteID':
                $fieldName = 'InstituteID';
                break;
            case 'PublicationDate':
                $fieldName = 'PublicationDate';
                break;
            case 'SubType':
                $fieldName = 'SubType';
                break;
            case 'Language':
                $fieldName = 'Language';
                break;
        }

        if ($fieldName) {
            $value = null;
            if (preg_match('/ID$/', $fieldName)) {
                if ($this->RepoItemID) {
                    $value = $this->RepoItemID;
                } else if ($this->InstituteID) {
                    $value = $this->InstituteID;
                } else if ($this->RepoItemFileID) {
                    $value = $this->RepoItemFileID;
                } else if ($this->PersonID) {
                    $value = $this->PersonID;
                }
            } else if ($this->Value) {
                $value = $this->Value;
            } else if ($this->getRelatedObjectSummary()) {
                $value = $this->getRelatedObjectTitle();
            }

            //PublicationDates are input in Dutch format, database needs to store them using English dates
            if ($fieldName == 'PublicationDate') {
                $value = static::getFullEnglishDateFrom($value);
            }

            if (count($repoItems) === 0) {
                $updateQuery = "UPDATE SurfSharekit_RepoItem ri
                INNER JOIN SurfSharekit_RepoItemMetaField rimf ON rimf.RepoItemID = ri.ID
                INNER JOIN SurfSharekit_RepoItemMetaFieldValue rimfv ON rimfv.RepoItemMetaFieldID = rimf.id
                SET ri.$fieldName = ?
                WHERE rimfv.ID = $this->ID";
                DB::prepared_query($updateQuery, [$value]);
            } else {
                foreach ($repoItems as $repoItem) {
                    $repoItem->$fieldName = $value;
                }
            }
        }
    }

    private function forceWriteRepoItem() {
        try {
            $this->RepoitemMetaField()->RepoItem()->write(false, false, true);
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }

    public function canEdit($member = null) {
        // todo, check if api uses this
        return true;
    }

    public function canView($member = null) {
        return true;
    }

    public function getRelatedObjectTitle() {
        if ($relatedObjectSummary = $this->getRelatedObjectSummary()) {
            if (isset($relatedObjectSummary['title'])) {
                return $relatedObjectSummary['title'];
            } else if (isset($relatedObjectSummary['name'])) {
                return $relatedObjectSummary['name'];
            } else if (isset($relatedObjectSummary['value'])) {
                return $relatedObjectSummary['value'];
            }
        }
        return null;
    }

    private static function getFullEnglishDateFrom($value) {
        $dutchDateParts = explode("-", $value); //jjjj-mm-dd , jjjj-mm or jjjj
        $englishDateParts = array_reverse($dutchDateParts); //dd-mm-yyyy, mm-yyyy or yyyy
        if (count($englishDateParts) < 3) {
            $englishDateParts[] = '01';
        }
        if (count($englishDateParts) < 3) {
            $englishDateParts[] = '01';
        }
        return implode("-", $englishDateParts);
    }
}