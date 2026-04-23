<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;

/**
 * Class DefaultMetaFieldOptionPart
 * @package SurfSharekit\Models
 * A DataObject representing the default value on a given @see TemplateMetaField
 */
class DefaultMetaFieldOptionPart extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_DefaultMetaFieldOptionPart';

    private static $db = [
        'Value' => 'Text'
    ];

    private static $has_one = [
        'TemplateMetaField' => TemplateMetaField::class,
        'MetaFieldOption' => MetaFieldOption::class,
        'Person' => Person::class,
        'Institute' => Institute::class,
        'RepoItemFile' => RepoItemFile::class,
        'RepoItem' => RepoItem::class
    ];

    private static $summary_fields = [
        'Title'
    ];

    public function getRelatedObjectSummary() {
        if (($repoItem = $this->RepoItem()) && $repoItem->exists()) {
            return $repoItem->Summary;
        } else if (($repoItemFile = $this->RepoItemFile()) && $repoItemFile->exists()) {
            // TODO, use $repoItemFile->Summary to get this object/array?
            return [
                'title' => $repoItemFile->Name,
                'url' => $repoItemFile->getStreamURL(),
                'size' => $repoItemFile->Size,
            ];
        } else if (($person = $this->Person()) && $person->exists()) {
            // TODO, use $person->Summary to get this object/array?
            return [
                'name' => $person->Name,
                'imageURL' => $person->PersonImage->getStreamURL()
            ];
        } else if (($institute = $this->Institute()) && $institute->exists()) {
            return $institute->Summary;
        } else if (($option = $this->MetaFieldOption()) && $option->exists()) {
            return [
                'value' => $option->Value,
                'labelEN' => $option->Label_EN,
                'labelNL' => $option->Label_NL
            ];
        }
        return null;
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canView($member = null) {
        return $this->TemplateMetaField()->Template()->canView($member);
    }

    public function canEdit($member = null) {
        return $this->TemplateMetaField()->Template()->canEdit($member);
    }

    private function getDefaultValueFlags() {
        return [
            'MetaFieldOption' => (int)$this->MetaFieldOptionID > 0,
            'Value' => $this->isNonEmptyValue($this->Value),
            'Person' => (int)$this->PersonID > 0,
            'Institute' => (int)$this->InstituteID > 0,
            'RepoItemFile' => (int)$this->RepoItemFileID > 0,
            'RepoItem' => (int)$this->RepoItemID > 0
        ];
    }

    private function isNonEmptyValue($value): bool {
        return strlen(trim((string)$value)) > 0;
    }

    private function getDefaultValueTitleCandidates() {
        return [
            [
                'label' => 'Selected default metafield option',
                'relation' => 'MetaFieldOption',
                'property' => 'Title'
            ],
            [
                'label' => 'Default value',
                'value' => $this->Value
            ],
            [
                'label' => 'Selected default person',
                'relation' => 'Person',
                'property' => 'Name'
            ],
            [
                'label' => 'Selected default institute',
                'relation' => 'Institute',
                'property' => 'Title'
            ],
            [
                'label' => 'Selected default repo item',
                'relation' => 'RepoItem',
                'property' => 'Title'
            ],
            [
                'label' => 'Selected default repo item file',
                'relation' => 'RepoItemFile',
                'property' => 'Name'
            ]
        ];
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        // Replace the default searchable dropdown with a simple dropdown limited to this metafield's options
        $fields->removeByName('MetaFieldOptionID');
        $metaField = $this->TemplateMetaField()->MetaField();
        $optionSource = [];
        if ($metaField && $metaField->exists()) {
            $optionSource = $metaField->MetaFieldOptions()->map('ID', 'Title')->toArray();
        }
        $optionField = DropdownField::create('MetaFieldOptionID', 'MetaField Option')
            ->setSource($optionSource)
            ->setEmptyString($metaField ? '-- Select option --' : 'Select a metafield first')
            ->setHasEmptyDefault(true)
            ->setValue($this->MetaFieldOptionID);

        $fields->insertBefore('PersonID', $optionField);

        return $fields;
    }

    public function validate(): ValidationResult {
        $result = parent::validate();

        $flags = $this->getDefaultValueFlags();
        $filledCount = count(array_filter($flags));
        $fieldList = implode(', ', array_keys($flags));

        if ($filledCount === 0) {
            $result->addError('Set exactly one of: ' . $fieldList . '.');
        } elseif ($filledCount > 1) {
            $result->addError('Only one of ' . $fieldList . ' can be set.');
        }

        if ($this->MetaFieldOptionID) {
            $metaField = $this->TemplateMetaField()->MetaField();
            $metaFieldOption = $this->MetaFieldOption();

            if ($metaField && $metaField->exists()) {
                $metaFieldTypeKey = strtolower($metaField->MetaFieldType()->Key ?? '');
                if (in_array($metaFieldTypeKey, ['tree-multiselect', 'dropdowntag'])) {
                    $result->addError('Default values are not allowed for this metafield type.');
                }
            }

            if ($metaField && $metaField->exists() && $metaFieldOption && $metaFieldOption->exists()) {
                if ((int)$metaFieldOption->MetaFieldID !== (int)$metaField->ID) {
                    $result->addError('Selected metafield option does not belong to this metafield.');
                }
            }
        }

        return $result;
    }

    public function getTitle() {
        foreach ($this->getDefaultValueTitleCandidates() as $candidate) {
            if (array_key_exists('value', $candidate)) {
                if ($this->isNonEmptyValue($candidate['value'])) {
                    return $candidate['label'] . ': ' . $candidate['value'];
                }
                continue;
            }

            $relation = $candidate['relation'] ?? null;
            $property = $candidate['property'] ?? null;
            if ($relation && $property) {
                $object = $this->$relation();
                if ($object && $object->exists()) {
                    return $candidate['label'] . ': ' . $object->$property;
                }
            }
        }

        return 'Default Value';
    }
}
