<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\Helper\MetafieldHelper;
use SurfSharekit\Models\MetaField;

class UpdateMetafieldJsonKeysTask extends BuildTask {

    protected $title = 'Update MetaFields JSON Key Task';
    protected $description = 'Updates jsonKey for each MetaField with labelEN or title if labelEN is empty. Skips if jsonKey is already set.';

    public function run($request)
    {
        set_time_limit(0);

        // Fetch all MetaField objects
        $metaFields = MetaField::get();

        foreach ($metaFields as $metaField) {
            // Skip if jsonKey is already set
            if (!empty($metaField->JsonKey)) {
                continue;
            }

            // Determine the value for jsonKey
            if (!empty(trim($metaField->Label_EN))) {
                $jsonKey = MetafieldHelper::toCamelCase($metaField->Label_EN);
            } elseif (!empty(trim($metaField->Title))) {
                $jsonKey = MetafieldHelper::toCamelCase($metaField->Title);
            } else {
                // Skip if both labelEN and title are empty
                continue;
            }

            // Update and save the MetaField
            $metaField->JsonKey = $jsonKey;
            $metaField->write();

            echo "Updated MetaField ID {$metaField->ID} with jsonKey: $jsonKey from $metaField->Label_EN";
        }

        echo "MetaFields update task completed.\n";
    }
}
