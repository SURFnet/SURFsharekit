<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\SearchObject;

// vendor/bin/sake dev/tasks/SurfSharekit-Tasks-RemoveFontysDisciplinesTask dryRun=yes
// vendor/bin/sake dev/tasks/SurfSharekit-Tasks-RemoveFontysDisciplinesTask dryRun=no madeBackup=yes areYouSure=yes
class RemoveFontysDisciplinesTask extends BuildTask
{
    protected $title = 'Remove Fontys Disciplines';
    protected $description = 'Removes all discipline-level institutes under Fontys, their related groups (and group memberships), and replaces discipline references in the departmentLectorateDiscipline metafield with the parent institute.';

    protected $enabled = true;

    private bool $dryRun = true;

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

        $fontysInstitute = $this->findFontysInstitute();
        if (!$fontysInstitute) {
            return;
        }

        $disciplines = $this->collectDisciplines($fontysInstitute);
        if ($disciplines->count() === 0) {
            $this->print("No discipline-level institutes found under Fontys. Nothing to do.");
            return;
        }

        $this->print("Found {$disciplines->count()} discipline(s) to process.");
        $this->print("");

        $disciplineIDs = $disciplines->column('ID');

        $this->replaceMetaFieldReferences($disciplineIDs);
        $this->reassignRepoItems($disciplineIDs);
        $this->cleanupRemainingMetaFieldValues($disciplineIDs);
        $this->cleanupDefaultMetaFieldOptionParts($disciplineIDs);
        $this->removeGroupMemberships($disciplineIDs);
        $this->removeGroups($disciplineIDs);
        $this->logDepartmentGroupOverview($fontysInstitute);
        $this->removeInstitutes($disciplines);

        $this->print("");
        $this->print("=== Done ===");
    }

    private function findFontysInstitute(): ?Institute
    {
        $fontys = Institute::get()->filter(['Title:PartialMatch' => 'Fontys', 'Level' => 'organisation'])->first();
        if (!$fontys || !$fontys->exists()) {
            $this->print("ERROR: Could not find an organisation-level institute with 'Fontys' in the title.");
            return null;
        }

        $this->print("Found Fontys institute: \"{$fontys->Title}\" (ID={$fontys->ID}, Uuid={$fontys->Uuid})");
        return $fontys;
    }

    private function collectDisciplines(Institute $fontys)
    {
        $allChildren = Institute::getAllChildInstitutes($fontys->Uuid);
        return $allChildren->filter('Level', 'discipline');
    }

    /**
     * For every RepoItemMetaFieldValue that references a discipline via the
     * departmentLectorateDiscipline metafield, replace it with the first
     * non-discipline ancestor (typically department or lectorate).
     */
    private function replaceMetaFieldReferences(array $disciplineIDs): void
    {
        $this->print("--- Step 1: Replace discipline references in departmentLectorateDiscipline metafield ---");

        $metaField = MetaField::get()->filter('JsonKey', 'departmentLectorateDiscipline')->first();
        if (!$metaField || !$metaField->exists()) {
            $this->print("WARNING: MetaField with jsonKey 'departmentLectorateDiscipline' not found. Skipping metafield migration.");
            return;
        }

        $this->print("Found MetaField \"{$metaField->Title}\" (ID={$metaField->ID})");

        $affectedValues = RepoItemMetaFieldValue::get()
            ->filter([
                'InstituteID' => $disciplineIDs,
                'IsRemoved' => 0,
            ])
            ->innerJoin(
                'SurfSharekit_RepoItemMetaField',
                'SurfSharekit_RepoItemMetaField.ID = SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID'
            )
            ->where([
                'SurfSharekit_RepoItemMetaField.MetaFieldID' => $metaField->ID,
            ]);

        $count = $affectedValues->count();
        $this->print("Found {$count} metafield value(s) referencing a discipline institute.");

        $replaced = 0;
        $skipped = 0;

        foreach ($affectedValues as $value) {
            /** @var RepoItemMetaFieldValue $value */
            $discipline = Institute::get()->byID($value->InstituteID);
            if (!$discipline) {
                $this->print("  [SKIP] Value ID={$value->ID}: discipline institute ID={$value->InstituteID} not found in DB.");
                $skipped++;
                continue;
            }

            $replacement = $this->getFirstNonDisciplineAncestor($discipline);
            if (!$replacement) {
                $this->print("  [SKIP] Value ID={$value->ID}: no non-discipline ancestor found for \"{$discipline->Title}\" (ID={$discipline->ID}). Leaving as-is.");
                $skipped++;
                continue;
            }

            $this->print("  [REPLACE] Value ID={$value->ID}: \"{$discipline->Title}\" (ID={$discipline->ID}) -> \"{$replacement->Title}\" (ID={$replacement->ID}, Level={$replacement->Level})");

            if (!$this->dryRun) {
                $value->InstituteID = $replacement->ID;
                $value->InstituteUuid = $replacement->Uuid;
                $value->DisableForceWriteRepoItem = true;
                $value->write();
            }

            $replaced++;
        }

        $this->print("Metafield values: {$replaced} replaced, {$skipped} skipped.");
        $this->print("");
    }

    /**
     * Reassigns RepoItems owned by disciplines to their first non-discipline ancestor.
     */
    private function reassignRepoItems(array $disciplineIDs): void
    {
        $this->print("--- Step 1.5: Reassign RepoItems owned by disciplines ---");

        $repoItems = \SurfSharekit\Models\RepoItem::get()->filter([
            'InstituteID' => $disciplineIDs
        ]);

        $count = $repoItems->count();
        if ($count === 0) {
            $this->print("No RepoItems owned by discipline institutes found.");
            $this->print("");
            return;
        }

        $this->print("Found {$count} RepoItem(s) owned by discipline institutes.");

        foreach ($disciplines = \SurfSharekit\Models\Institute::get()->byIDs($disciplineIDs) as $discipline) {
            $replacement = $this->getFirstNonDisciplineAncestor($discipline);
            if (!$replacement) {
                $this->print("  [WARNING] No non-discipline ancestor found for \"{$discipline->Title}\" (ID={$discipline->ID}).");
                continue;
            }

            $this->print("  [REASSIGN] Moving RepoItems from \"{$discipline->Title}\" (ID={$discipline->ID}) to \"{$replacement->Title}\" (ID={$replacement->ID})");

            if (!$this->dryRun) {
                DB::prepared_query(
                    "UPDATE \"SurfSharekit_RepoItem\" SET \"InstituteID\" = ?, \"InstituteUUID\" = ? WHERE \"InstituteID\" = ?",
                    [$replacement->ID, $replacement->UUID, $discipline->ID]
                );
            }
        }

        $this->print("");
    }


    /**
     * Walks up the institute tree and returns the first ancestor that is NOT a discipline.
     */
    private function getFirstNonDisciplineAncestor(Institute $institute): ?Institute
    {
        $parent = $institute->Institute();
        $depth = 0;

        while ($parent && $parent->exists() && $depth < 10) {
            if ($parent->Level !== 'discipline') {
                return $parent;
            }
            $parent = $parent->Institute();
            $depth++;
        }

        return null;
    }

    /**
     * Handles RepoItemMetaFieldValues referencing disciplines from OTHER metafields
     * (not departmentLectorateDiscipline). These are marked as removed so they don't
     * block institute deletion.
     */
    private function cleanupRemainingMetaFieldValues(array $disciplineIDs): void
    {
        $this->print("--- Step 2: Clean up remaining metafield values referencing disciplines ---");

        $remaining = RepoItemMetaFieldValue::get()->filter([
            'InstituteID' => $disciplineIDs,
            'IsRemoved' => 0,
        ]);

        $count = $remaining->count();
        if ($count === 0) {
            $this->print("No remaining metafield values referencing disciplines. OK.");
            $this->print("");
            return;
        }

        $this->print("Found {$count} remaining value(s) from other metafields.");

        foreach ($remaining as $value) {
            /** @var RepoItemMetaFieldValue $value */
            $discipline = Institute::get()->byID($value->InstituteID);
            $disciplineTitle = $discipline ? $discipline->Title : "(unknown, ID={$value->InstituteID})";

            $repoItemMetaField = $value->RepoItemMetaField();
            $metaFieldTitle = ($repoItemMetaField && $repoItemMetaField->exists() && $repoItemMetaField->MetaField()->exists())
                ? $repoItemMetaField->MetaField()->Title . " (JsonKey={$repoItemMetaField->MetaField()->JsonKey})"
                : "(unknown metafield)";

            $replacement = $discipline ? $this->getFirstNonDisciplineAncestor($discipline) : null;

            if ($replacement) {
                $this->print("  [REPLACE] Value ID={$value->ID} (MetaField: {$metaFieldTitle}): \"{$disciplineTitle}\" -> \"{$replacement->Title}\" (ID={$replacement->ID})");
                if (!$this->dryRun) {
                    $value->InstituteID = $replacement->ID;
                    $value->InstituteUuid = $replacement->Uuid;
                    $value->DisableForceWriteRepoItem = true;
                    $value->write();
                }
            } else {
                $this->print("  [SOFT-DELETE] Value ID={$value->ID} (MetaField: {$metaFieldTitle}): \"{$disciplineTitle}\" — no replacement found, marking as removed.");
                if (!$this->dryRun) {
                    $value->IsRemoved = 1;
                    $value->DisableForceWriteRepoItem = true;
                    $value->write();
                }
            }
        }

        $this->print("");
    }

    /**
     * Removes DefaultMetaFieldOptionPart records that reference discipline institutes.
     */
    private function cleanupDefaultMetaFieldOptionParts(array $disciplineIDs): void
    {
        $this->print("--- Step 3: Clean up DefaultMetaFieldOptionParts referencing disciplines ---");

        $parts = DefaultMetaFieldOptionPart::get()->filter('InstituteID', $disciplineIDs);
        $count = $parts->count();

        if ($count === 0) {
            $this->print("No DefaultMetaFieldOptionParts referencing disciplines. OK.");
            $this->print("");
            return;
        }

        $this->print("Found {$count} DefaultMetaFieldOptionPart(s) to update.");

        foreach ($parts as $part) {
            /** @var DefaultMetaFieldOptionPart $part */
            $discipline = Institute::get()->byID($part->InstituteID);
            $disciplineTitle = $discipline ? $discipline->Title : "(unknown, ID={$part->InstituteID})";

            $replacement = $discipline ? $this->getFirstNonDisciplineAncestor($discipline) : null;

            if ($replacement) {
                $this->print("  [REPLACE] Part ID={$part->ID}: \"{$disciplineTitle}\" -> \"{$replacement->Title}\" (ID={$replacement->ID})");
                if (!$this->dryRun) {
                    $part->InstituteID = $replacement->ID;
                    $part->InstituteUuid = $replacement->Uuid;
                    $part->write();
                }
            } else {
                $this->print("  [DELETE] Part ID={$part->ID}: \"{$disciplineTitle}\" — no replacement found, deleting.");
                if (!$this->dryRun) {
                    $part->delete();
                }
            }
        }

        $this->print("");
    }

    /**
     * Removes all members from groups that belong to discipline institutes.
     */
    private function removeGroupMemberships(array $disciplineIDs): void
    {
        $this->print("--- Step 4: Remove people from discipline groups ---");

        $groups = Group::get()->filter('InstituteID', $disciplineIDs);
        $totalRemoved = 0;

        foreach ($groups as $group) {
            /** @var Group $group */
            $memberCount = $group->Members()->count();
            if ($memberCount === 0) {
                continue;
            }

            $this->print("  Group \"{$group->Title}\" (ID={$group->ID}): {$memberCount} member(s) to remove");

            foreach ($group->Members() as $member) {
                $this->print("    - Member: {$member->getName()} (ID={$member->ID})");
            }

            if (!$this->dryRun) {
                $group->Members()->removeAll();
            }

            $totalRemoved += $memberCount;
        }

        $this->print("Total memberships removed: {$totalRemoved}");
        $this->print("");
    }

    /**
     * Removes all groups linked to discipline institutes,
     * including AutoAddedGroups many-many references and role assignments.
     */
    private function removeGroups(array $disciplineIDs): void
    {
        $this->print("--- Step 5: Remove discipline groups ---");

        $groups = Group::get()->filter('InstituteID', $disciplineIDs);
        $count = $groups->count();
        $this->print("Found {$count} group(s) to remove.");

        foreach ($groups as $group) {
            /** @var Group $group */
            $this->print("  [DELETE] Group \"{$group->Title}\" (ID={$group->ID}, InstituteID={$group->InstituteID})");

            if (!$this->dryRun) {
                $autoAddedConsortiums = $group->AutoAddedConsortiums();
                if ($autoAddedConsortiums->count() > 0) {
                    $autoAddedConsortiums->removeAll();
                }

                $group->Roles()->removeAll();
                $group->delete();
            }
        }

        $this->print("");
    }

    /**
     * Logs an overview of department-level groups with members, so Fontys can
     * manually review and clean those up.
     */
    private function logDepartmentGroupOverview(Institute $fontys): void
    {
        $this->print("--- Overview: Department groups with members (for Fontys manual review) ---");

        $allChildren = Institute::getAllChildInstitutes($fontys->Uuid, false);
        $departments = $allChildren->filter('Level', 'department');

        if ($departments->count() === 0) {
            $this->print("No department-level institutes found under Fontys.");
            $this->print("");
            return;
        }

        $departmentIDs = $departments->column('ID');
        $groups = Group::get()->filter('InstituteID', $departmentIDs);

        $hasAny = false;
        foreach ($groups as $group) {
            /** @var Group $group */
            $memberCount = $group->Members()->count();
            if ($memberCount === 0) {
                continue;
            }
            $hasAny = true;

            $institute = Institute::get()->byID($group->InstituteID);
            $instituteTitle = $institute ? $institute->Title : "(unknown)";
            $this->print("  Department: \"{$instituteTitle}\" | Group: \"{$group->Title}\" (ID={$group->ID}) | Members: {$memberCount}");

            foreach ($group->Members() as $member) {
                $this->print("    - {$member->getName()} ({$member->Email})");
            }
        }

        if (!$hasAny) {
            $this->print("No department groups with members found.");
        }

        $this->print("");
    }

    /**
     * Removes the discipline institutes themselves, including templates and search objects.
     */
    private function removeInstitutes($disciplines): void
    {
        $this->print("--- Step 6: Remove discipline institutes ---");
        $this->print("Removing {$disciplines->count()} institute(s).");

        foreach ($disciplines as $institute) {
            /** @var Institute $institute */
            $parentTitle = $institute->Institute()->exists() ? $institute->Institute()->Title : '(no parent)';
            $this->print("  [DELETE] Institute \"{$institute->Title}\" (ID={$institute->ID}, Level={$institute->Level}, Parent=\"{$parentTitle}\")");

            if (!$this->dryRun) {
                foreach ($institute->Templates() as $template) {
                    // Use a direct query to delete TemplateMetaFields to avoid triggering downPropagateFast()
                    // which often causes DuplicateEntryException when templates are being removed or restructured.
                    DB::prepared_query(
                        "DELETE FROM \"SurfSharekit_TemplateMetaField\" WHERE \"TemplateID\" = ?",
                        [$template->ID]
                    );

                    DB::prepared_query(
                        "DELETE FROM \"SurfSharekit_TemplateMetaField_Versions\" WHERE \"TemplateID\" = ?",
                        [$template->ID]
                    );

                    $template->delete();
                }

                SearchObject::get()->filter('InstituteID', $institute->ID)->removeAll();
                $instituteImage = $institute->InstituteImage();
                if ($instituteImage && $instituteImage->exists()) {
                    $instituteImage->delete();
                }

                $institute->delete();
            }
        }

        $this->print("");
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
