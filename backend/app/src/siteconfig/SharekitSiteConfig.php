<?php

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class SharekitSiteConfig extends DataExtension {
    private static $db = [
        "Email" => "Text",
    ];

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab("Root.Main", new TextField("Email"));
    }
}