<?php

namespace SurfSharekit\buildtasks;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonConfig;
use SurfSharekit\Models\PersonSummary;
use SurfSharekit\Models\SearchObject;

class DeleteInactivePersons extends BuildTask
{

    protected $title = "Delete inactive persons";
    protected $description = "Deletes inactive persons which are not related to any object";

    private bool $dryRun = true;
    private array $ignoreDataObjects = [
        PersonConfig::class,
        SearchObject::class,
        PersonSummary::class
    ];

    public function run($request) {

        if ($request->getVar('dryRun') !== null) {
            $this->dryRun = !!$request->getVar('dryRun');
        }

        $this->print("Dry run: " . ($this->dryRun() ? "enabled" : "disabled"));
        $this->print("---");


        // get all dataObjects
        $dataObjects = ClassInfo::subclassesFor(DataObject::class);
        $inactivePerson = Person::get()->filter('HasLoggedIn', false);

        foreach ($inactivePerson as $person) {
            $isRelatedTo = [];

            foreach ($dataObjects as $dataObjectClass) {
                if (in_array($dataObjectClass, $this->ignoreDataObjects)) {
                    continue;
                }

                $tableName = Config::inst()->get($dataObjectClass, 'table_name');

                // only check sharekit tables
                if (str_starts_with($tableName, "SurfSharekit_")) {

                    /** @var DataObject $dataObject */
                    $dataObject = singleton($dataObjectClass);

                    // get all possible relations
                    $relationTypes = [
                        'hasOne' => $dataObject->hasOne(),
                        'hasMany' => $dataObject->hasMany(),
                        'manyMany' => $dataObject->manyMany(),
                    ];

                    foreach ($relationTypes as $type => $relations) {
                        foreach ($relations as $relationName => $relation) {
                            // check if relation is not a "through" relation
                            if (is_array($relation)) {
                                if (!isset($relation['through'])) {
                                    throw new \Exception("Invalid relation");
                                }

                                $relationClass = $relation['through'];
                            } else {
                                $relationClass = $relation;
                            }

                            // check if relation class is a person
                            if ($relationClass === Person::class || $relationClass === Member::class) {
                                if (!!$dataObject::get()->filter($relationName . ".ID", $person->ID)->count()) {
                                    $isRelatedTo[] = get_class($dataObject);
                                }
                            }

                            if ($isRelatedTo) {
                                break 3;
                            }
                        }
                    }
                }
            }

            // if not related to anything... do delete
            if (!count($isRelatedTo)) {
                $this->print("Deleting: $person->ID ($person->Uuid)");

                if (!$this->dryRun()) {
                    $this->doDeletePerson($person);
                }
            }
        }
    }

    private function doDeletePerson(Person $person) {
        $person->delete();
    }

    private function dryRun(): bool {
        return $this->dryRun;
    }

    private function print($message) {
        if (Director::is_cli()) {
            echo $message;
        } else {
            echo "<span>$message</span><br>";
        }
    }
}