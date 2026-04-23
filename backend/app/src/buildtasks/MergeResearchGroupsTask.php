<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\SearchObject;

class MergeResearchGroupsTask extends BuildTask
{
    protected $title = 'Merge Research Groups';
    protected $description = 'Merges specific research groups by moving references to a primary institute and deleting the redundant ones.';

    protected $enabled = true;

    private bool $dryRun = true;

    private array $mergeMap = [
        'Onderzoeksgroep Natuur en Ontwikkeling Kind' => [
            'Natuur en Ontwikkeling Kind'
        ],
        'Onderzoeksgroep Waarde(n) van Vrijeschoolonderwijs' => [
            'Waarde(n) van Vrijeschool Onderwijs'
        ]
    ];

    public function run($request): void
    {
        set_time_limit(0);

        $this->dryRun = $request->getVar('dryRun') !== 'no';

        if (!$this->dryRun) {
            if ($request->getVar('madeBackup') !== 'yes') {
                $this->print("'madeBackup' is not set to 'yes'. Please make a backup first.");
                return;
            }
            if ($request->getVar('areYouSure') !== 'yes') {
                $this->print("'areYouSure' is not set to 'yes'. Aborting.");
                return;
            }
        }

        $this->print($this->dryRun ? "=== DRY RUN MODE — nothing will be modified ===" : "=== LIVE MODE — changes WILL be persisted ===");
        $this->print("");

        foreach ($this->mergeMap as $targetTitle => $sourceTitles) {
            $this->print("--- Merging into: \"$targetTitle\" ---");

            $targetInstitute = Institute::get()->filter(['Title' => $targetTitle])->first();
            if (!$targetInstitute || !$targetInstitute->exists()) {
                $this->print("ERROR: Target institute \"$targetTitle\" not found. Skipping.");
                continue;
            }

            foreach ($sourceTitles as $sourceTitle) {
                $sourceInstitute = Institute::get()->filter(['Title' => $sourceTitle])->first();
                if (!$sourceInstitute || !$sourceInstitute->exists()) {
                    $this->print("INFO: Source institute \"$sourceTitle\" not found. Skipping.");
                    continue;
                }

                $this->mergeInstitutes($sourceInstitute, $targetInstitute);
            }
            $this->print("");
        }

        $this->print("=== Done ===");
    }

    private function mergeInstitutes(Institute $source, Institute $target): void
    {
        $this->print("Merging \"{$source->Title}\" (ID={$source->ID}) into \"{$target->Title}\" (ID={$target->ID})");

        // 1. RepoItems (Direct SQL to bypass ownership validation and side-effects)
        $count = \SurfSharekit\Models\RepoItem::get()->filter('InstituteID', $source->ID)->count();
        $this->print("  - Moving {$count} RepoItem(s)");
        if (!$this->dryRun && $count > 0) {
            DB::prepared_query(
                "UPDATE \"SurfSharekit_RepoItem\" SET \"InstituteID\" = ?, \"InstituteUUID\" = ? WHERE \"InstituteID\" = ?",
                [$target->ID, $target->UUID, $source->ID]
            );
        }

        // 2. MetaField Values
        $affectedValues = RepoItemMetaFieldValue::get()->filter([
            'InstituteID' => $source->ID,
            'IsRemoved' => 0
        ]);
        $this->print("  - Moving {$affectedValues->count()} MetaField value(s)");
        if (!$this->dryRun) {
            foreach ($affectedValues as $value) {
                $value->InstituteID = $target->ID;
                $value->InstituteUuid = $target->Uuid;
                $value->DisableForceWriteRepoItem = true;
                $value->write();
            }
        }

        // 2. DefaultMetaFieldOptionParts
        $affectedParts = DefaultMetaFieldOptionPart::get()->filter('InstituteID', $source->ID);
        $this->print("  - Moving {$affectedParts->count()} DefaultMetaFieldOptionPart(s)");
        if (!$this->dryRun) {
            foreach ($affectedParts as $part) {
                $part->InstituteID = $target->ID;
                $part->InstituteUuid = $target->Uuid;
                $part->write();
            }
        }

        // 3. Group Memberships
        $groups = Group::get()->filter('InstituteID', $source->ID);
        foreach ($groups as $group) {
            $memberCount = $group->Members()->count();
            if ($memberCount > 0) {
                $this->print("  - Group \"{$group->Title}\" (ID={$group->ID}): Removing {$memberCount} member(s)");
                if (!$this->dryRun) {
                    $group->Members()->removeAll();
                }
            }
        }

        // 4. Groups cleanup
        $this->print("  - Removing {$groups->count()} group(s)");
        if (!$this->dryRun) {
            foreach ($groups as $group) {
                $group->AutoAddedConsortiums()->removeAll();
                $group->Roles()->removeAll();
                $group->delete();
            }
        }

        // 5. SearchObjects
        $this->print("  - Removing search objects");
        if (!$this->dryRun) {
            SearchObject::get()->filter('InstituteID', $source->ID)->removeAll();
        }

        // 6. Delete Institute
        $this->print("  - Deleting institute \"{$source->Title}\"");
        if (!$this->dryRun) {
            foreach ($source->Templates() as $template) {
                DB::prepared_query("DELETE FROM \"SurfSharekit_TemplateMetaField\" WHERE \"TemplateID\" = ?", [$template->ID]);
                DB::prepared_query("DELETE FROM \"SurfSharekit_TemplateMetaField_Versions\" WHERE \"TemplateID\" = ?", [$template->ID]);
                $template->delete();
            }

            $image = $source->InstituteImage();
            if ($image && $image->exists()) {
                $image->delete();
            }

            $source->delete();
        }
    }

    private function print(string $message): void
    {
        if (Director::is_cli()) {
            echo $message . "\n";
        } else {
            $escaped = htmlspecialchars($message);
            if (str_starts_with($message, '===')) {
                echo "<h3>{$escaped}</h3>";
            } elseif (str_starts_with($message, '---')) {
                echo "<h4>{$escaped}</h4>";
            } elseif ($message === '') {
                echo "<br>";
            } else {
                echo "<pre>{$escaped}</pre>";
            }
        }
    }
}