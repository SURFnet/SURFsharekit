<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Versioned\Versioned;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class Protocol
 * @package SurfSharekit\Models
 * DataObject representing a protocol the external api can open
 */
class Protocol extends DataObject {
    private static $table_name = 'SurfSharekit_Protocol';

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $db = [
        'Title' => 'Varchar(255)',
        'Prefix' => 'Varchar(255)',
        '_Schema' => 'Varchar(255)',
        'NamespaceURI' => 'Varchar(255)',
        'SystemKey' => 'Enum(array("OAI-PMH", "JSON:API", "CSV"))',
        'InvalidateCache' => 'Int(0)',
        'CacheLock' => 'Int(0)'
    ];

    private static $field_labels = [
        '_Schema' => 'Schema'
    ];

    private static $has_many = [
        'ProtocolNodes' => ProtocolNode::class,
        'ProtocolFilters' => ProtocolFilter::class,
        'Channels' => Channel::class,
        'SearchProtocolNodes' => ProtocolNode::class
    ];

    public function describe(RepoItem $repoItem) {
        return $this->ProtocolNode->describeUsing($repoItem);
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        if ($this->isInDB()) {
            /** @var GridField $protocolNodesGridField */
            $protocolNodesGridField = $fields->dataFieldByName('ProtocolNodes');
            $protocolNodesGridFieldConfig = $protocolNodesGridField->getConfig();
            $protocolNodesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction(), new GridFieldFilterHeader()]);
            $protocolNodesGridFieldConfig->addComponents([new GridFieldOrderableRows('SortOrder')]);

            /** @var GridField $searchProtocolNodesGridField */
            $searchProtocolNodesGridField = $fields->dataFieldByName('SearchProtocolNodes');
            $searchProtocolNodesGridFieldConfig = $searchProtocolNodesGridField->getConfig();
            $searchProtocolNodesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldAddNewButton(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);
            $searchProtocolNodesGridField->setList(ProtocolNode::get()->filter('ParentProtocolID', $this->ID));

            /** @var GridField $channelsGridField */
            $channelsGridField = $fields->dataFieldByName('Channels');
            $channelsGridFieldConfig = $channelsGridField->getConfig();
            $channelsGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldAddNewButton(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            /** @var GridField $protocolFiltersGridfield */
            $protocolFiltersGridfield = $fields->dataFieldByName('ProtocolFilters');
            $protocolFiltersGridfieldConfig = $protocolFiltersGridfield->getConfig();
            $protocolFiltersGridfieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            $fields->removeByName('CacheLock');
            if (in_array($this->SystemKey, ['OAI-PMH', 'CSV', 'JSON:API'])) {
                $invalidateCacheField = CheckboxField::create('InvalidateCache', 'Invalidate Cache', $this->InvalidateCache);
                $fields->replaceField('InvalidateCache', $invalidateCacheField);
                $invalidateCacheField = $fields->dataFieldByName('InvalidateCache');
                $invalidateCacheField->setDescription('When invalidate cache is set, the related caches will be updated during the next update cycle');
                if ($this->InvalidateCache) {
                    $fields->makeFieldReadonly('InvalidateCache');
                    $invalidateCacheField = $fields->dataFieldByName('InvalidateCache');
                    if ($this->CacheLock) {
                        $invalidateCacheField->setDescription('Cache is currently being refreshed... this can take up to 4 hours to complete!');
                    } else {
                        $invalidateCacheField->setDescription('Cache is scheduled to be refreshed... this can take up to 4 hours to complete!');
                    }

                }
            } else {
                $fields->removeByName('InvalidateCache');
            }
        }
        return $fields;
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }
}