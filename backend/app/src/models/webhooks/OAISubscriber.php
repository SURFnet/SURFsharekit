<?php

namespace SurfSharekit\models\webhooks;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\models\oai\Set;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Permission;
use SurfSharekit\Models\Institute;

/**
 * Represents a subscriber to an OAI set, responsible for receiving notifications via a webhook
 * when changes occur in the associated set. This class includes configuration for subscriber
 * settings, such as contact details, endpoint URL, and rate limit delay. It also enforces
 * validation rules and permissions for managing subscribers.
 */
class OAISubscriber extends DataObject {
    private static $table_name = 'SurfSharekit_OAISubscriber';

    private static $db = [
        'Name' => 'Varchar(255)',
        'EndpointURL' => 'Varchar(512)',
        'IsEnabled' => 'Boolean(1)',
        'ContactDetails' => 'Text',
        'RateLimitDelayMs' => 'Int'
    ];

    private static $has_one = [
        'OAISet' => Set::class
    ];

    private static $defaults = [
        'IsEnabled' => 1,
        'RateLimitDelayMs' => 0
    ];

    private static $summary_fields = [
        'Name' => 'Subscriber',
        'OAISet.spec' => 'OAI Set',
        'EndpointURL' => 'Webhook URL',
        'IsEnabled.Nice' => 'Active',
        'RateLimitDelayMs' => 'Delay (ms)'
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create(
                'OAISetID',
                'Set Spec',
                Set::get()
                    ->sort('spec')
                    ->map('ID', 'spec')
            )->setEmptyString('-- Select a set --'),
            TextField::create('EndpointURL', 'Webhook URL')
                ->setDescription('HTTP endpoint that receives POST requests when the selected set changes.'),
            TextareaField::create('ContactDetails', 'Contact details')
                ->setDescription('Contact person or organisation for this subscription.')
        ]);

        return $fields;
    }

    public function validate(): ValidationResult {
        $result = parent::validate();

        if (!$this->Name) {
            $result->addFieldError('Name', 'A subscriber name is required.');
        }

        if (!$this->EndpointURL) {
            $result->addFieldError('EndpointURL', 'An endpoint URL is required.');
        } else if (!filter_var($this->EndpointURL, FILTER_VALIDATE_URL)) {
            $result->addFieldError('EndpointURL', 'Provide a valid URL for the webhook endpoint.');
        }

        if (!$this->OAISetID) {
            $result->addFieldError('OAISetID', 'Select the OAI set this subscriber listens to.');
        }

        if ($this->RateLimitDelayMs < 0) {
            $result->addFieldError('RateLimitDelayMs', 'The rate limit delay cannot be negative.');
        }

        return $result;
    }

    public function canView($member = null) {
        return $this->hasOAIAdminAccess($member);
    }

    public function canEdit($member = null) {
        return $this->hasOAIAdminAccess($member);
    }

    public function canDelete($member = null) {
        return $this->hasOAIAdminAccess($member);
    }

    public function canCreate($member = null, $context = []) {
        return $this->hasOAIAdminAccess($member);
    }

    private function hasOAIAdminAccess($member = null): bool {
        return Permission::check('ADMIN', 'any', $member);
    }
}
