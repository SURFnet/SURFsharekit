<?php

namespace SurfSharekit\Extensions;

use ReflectionException;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Validation\RelationValidationService;

/**
 * Hook up static validation to the deb/build process
 *
 * @method DatabaseAdmin getOwner()
 */
class DatabaseAdminExtension extends Extension {
    /**
     * Extension point in @param bool $quiet
     * @param bool $populate
     * @param bool $testMode
     * @throws ReflectionException
     * @see DatabaseAdmin::doBuild()
     *
     */
    public function onAfterBuild(bool $quiet, bool $populate, bool $testMode): void {
        $service = RelationValidationService::singleton();

        if (!$service->config()->get('output_enabled')) {
            return;
        }

        $service->executeValidation();
    }
}
