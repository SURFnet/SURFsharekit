<?php

namespace SurfSharekit\Extensions;

use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SurfSharekit\Api\MetaFieldOptionTreeSearchController;

/**
 * Prepends tree-multiselect + legacy v1 URLs so they win over the catch-all `api/v1//$Action` rule when
 * Director iterates rules in alphabetical order (where `api/v1//...` sorts before `api/v1/meta...`).
 *
 * @method Director getOwner()
 */
class DirectorRoutesExtension extends Extension
{
    public function updateRules(array &$rules): void
    {
        $controller = MetaFieldOptionTreeSearchController::class;
        $prependKeys = [
            'api/tree-multiselect/metaFieldOptionTreeSearch',
            'api/v1/metaFieldOptionTreeSearch',
        ];
        foreach ($prependKeys as $key) {
            unset($rules[$key]);
        }
        $rules = array_combine($prependKeys, array_fill(0, count($prependKeys), $controller)) + $rules;
    }
}
