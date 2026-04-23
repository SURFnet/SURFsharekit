<?php

namespace SilverStripe\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\registries\BlueprintConverterRegistry;

class ConvertBlueprintToDataObjectTask extends BuildTask
{
    private static $segment = 'convert-blueprints';

    public function isEnabled()
    {
        return false;
    }

    public function run($request)
    {
        $class = $request->getVar('blueprintClass');
        if (!$class) {
            return 'No blueprint class specified';
        }

        $converter = BlueprintConverterRegistry::getConverter($class);
        if (!$converter) {
            return "No converter found for class: $class";
        }

        $blueprints = $class::get();
        $count = 0;

        foreach ($blueprints as $blueprint) {
            $converted = $converter->convert($blueprint);
            if ($converted) {
                $converted->write();
                $count++;
            }
        }

        return "Converted $count {$converter->getTargetClass()} records";
    }
}