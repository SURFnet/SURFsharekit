<?php

namespace SilverStripe\buildtasks;

use SilverStripe\api\Upload\Processors\DisciplineMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DropdownFieldMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\LectorateMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MultiSelectPublisherSwitchMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\PersonInvolvedMetaFieldProcessor;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\MetaField;

class MetaFieldProcessorDebugTask extends BuildTask
{

    public function run($request) {
        $t = 'text';
        $classes = ClassInfo::subclassesFor(MetaFieldProcessor::class, false);

        foreach ($classes as $class) {
            if (Config::forClass($class)->get('type') == $t) {

            }

            $array = ['95ea838-ebae-465b-8f62-7c992d2d47fe'];
            $metaField = MetaField::get()->find('Uuid', '58cc2eed-f5ff-408c-8872-762ddbb12724');
            if (!MultiSelectPublisherSwitchMetaFieldProcessor::create($metaField, $array)->validate()->hasErrors()) {
                die;
            }

            $j = '';

            die;
        }

        die;
    }
}