<?php

namespace SilverStripe\models\oai;

use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\MetaField;

class Set extends DataObject {
    private static $table_name = 'sets';

    private static $db = [
        'name' => 'Varchar(255)',
        'spec' => 'Varchar(255)',
        'description' => 'Text', // Not used, but required by OAI-PMH package
        'IsEnabled' => 'Boolean(1)',
        'IsPublic' => 'Boolean(1)',
        'RepoTypes' => 'Varchar(255)',
        'Channels' => 'Varchar(255)'
    ];

    private static $summary_fields = [
        'name' => 'Name',
        'spec' => 'OAI Set Specification',
        'DisplayEnabled' => 'Enabled',
        'DisplayPublic' => 'Public',
        'RepoTypesSummary' => 'Repository Types',
        'ChannelsSummary' => 'Channels',
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName('description');

        $fields->makeFieldReadonly([
            'IsEnabled',
            'IsPublic'
        ]);

        $fields->dataFieldByName('spec')
            ->setDescription('Unique specification (e.g. `uni_x:theses`). Required.');

        // Map the repository types to their readable names
        $repoTypesMap = [
            'PublicationRecord' => 'Publication Record',
            'ResearchObject'    => 'Research Object',
            'LearningObject'    => 'Learning Object',
        ];

        // Add the listbox field
        $fields->addFieldsToTab('Root.Main', [
            ListboxField::create(
                'RepoTypes',
                'Selected Repository Types',
                $repoTypesMap
            )->setDescription('Select one or more record types that should be included in this OAI set.')
        ]);


        // Get all channels
        $channelMetaFields = MetaField::get()->filter([
            'MetaFieldType.Key' => 'switch-row',
            'SystemKey'     => 'PublicChannel'
        ]);
        $channelMap = $channelMetaFields->map('Uuid', 'Title')->toArray();

        // Add the listbox field
        if (empty($channelMap)) {
            $fields->addFieldsToTab('Root.Main', [
                LiteralField::create('NoChannels', '<p class="message warning">No channels found that meet the criteria.</p>')
            ]);
        } else {
            $fields->addFieldsToTab('Root.Main', [
                ListboxField::create(
                    'Channels',
                    'Selected Channels',
                    $channelMap
                )->setDescription('Records must be published on at least one of these Channels to be included in this OAI set.')
            ]);
        }

        return $fields;
    }

    public function getRepoTypesSummary() {
        if($this->RepoTypes === null) return 'No types selected';

        // Replaces comma-separated string with comma-space for readability
        return trim(str_replace([',', '[', ']', '"'], [', ', '', '', ''], $this->RepoTypes));
    }

    public function getChannelsSummary() {
        if (!$this->Channels) return 'No Channels selected';

        // Translates the stored IDs back to names for display
        $ids = explode(',', trim(str_replace(['"', '[', ']'], [''], $this->Channels)));

        $names = MetaField::get()->filter(['Uuid' => $ids])->map('Title')->toArray();
        return implode(', ', $names);
    }

    public function getDisplayEnabled() {
        return $this->IsEnabled ? "Yes" : "No";
    }

    public function getDisplayPublic() {
        return $this->IsPublic ? "Yes" : "No";
    }
}