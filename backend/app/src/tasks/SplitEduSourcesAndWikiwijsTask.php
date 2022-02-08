<?php

namespace SurfSharekit\Tasks;

use Exception;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\TemplateMetaField;

class SplitEduSourcesAndWikiwijsTask extends BuildTask {

    protected $title = "'edusources & Wikiwijs'-splitting task";
    protected $description = "This task finds a metafield titled 'edusources & Wikiwijs' and splits it up into two different metafields.";

    protected $enabled = true;

    function run($request) {
        //find
        $combinedMetaField = MetaField::get()->filter('Title', 'edusources & Wikiwijs')->first();
        if ($combinedMetaField && $combinedMetaField->exists()) {
            echo("-Found 'edusources & Wikiwijs'-metafield");
            echo('<br>');
            $this->splitCombinedMetaField($combinedMetaField);
        } else {
            echo("-Didn't find 'edusources & Wikiwijs'-metafield, maybe already split into 'edusources' and 'Wikiwijs'?");
        }
    }

    function splitCombinedMetaField($combinedMetaField) {
        $combinedMetaField->Title = 'edusources';
        $combinedMetaField->Label_EN = 'edusources';
        $combinedMetaField->Label_NL = 'edusources';
        $combinedMetaField->write();
        echo("-Renamed 'edusources & Wikiwijs' metafield to 'edusources'");
        echo('<br>');

        $wikiwijsMetafield = new MetaField();
        $wikiwijsMetafield->Title = 'Wikiwijs';
        $wikiwijsMetafield->Label_EN = 'Wikiwijs';
        $wikiwijsMetafield->Label_NL = 'Wikiwijs';
        $wikiwijsMetafield->IsCopyable = $combinedMetaField->IsCopable;
        $wikiwijsMetafield->SystemKey = $combinedMetaField->SystemKey;
        $wikiwijsMetafield->MetaFieldTypeID = $combinedMetaField->MetaFieldTypeID;
        $wikiwijsMetafield->write();
        echo("-Created 'Wikiwijs' metafield");
        echo('<br>');

        $this->createTemplateMetafields($combinedMetaField, $wikiwijsMetafield);
    }

    function createTemplateMetafields($combinedMetaField, $wikiwijsMetafield) {
        $edusourceInstitutes = $this->getTitlesOfInstitutesWithEdusourceLicense();

        echo("-Splitting template-metafields reffering to 'edusources & Wikiwijs'-metafield,");
        echo('<br>');
        echo("-Using labels, infotext and description from metafield");
        echo('<br>');
        foreach (TemplateMetaField::get()->filter('MetaFieldID', $combinedMetaField->ID) as $combinedTemplateMetaField) {
            $instituteHasEdusourceLicense = in_array($combinedTemplateMetaField->Template()->Institute()->getRootInstitute()->Title, $edusourceInstitutes);

            $wikiwijsTemplateMetafield = new TemplateMetaField();
            $wikiwijsTemplateMetafield->SortOrder = $combinedTemplateMetaField->SortOrder; //Same sort order
            $wikiwijsTemplateMetafield->IsRemoved = $combinedTemplateMetaField->IsRemoved; //Copy state
            $wikiwijsTemplateMetafield->IsRequired = $combinedTemplateMetaField->IsRequired;
            $wikiwijsTemplateMetafield->IsSmallField = $combinedTemplateMetaField->IsSmallField;
            $wikiwijsTemplateMetafield->IsLocked = $combinedTemplateMetaField->IsLocked;
            $wikiwijsTemplateMetafield->IsEnabled = $combinedTemplateMetaField->IsEnabled;
            $wikiwijsTemplateMetafield->IsReadOnly = $combinedTemplateMetaField->IsReadOnly;
            $wikiwijsTemplateMetafield->IsHidden = $combinedTemplateMetaField->IsHidden;
            $wikiwijsTemplateMetafield->IsCopyable = $combinedTemplateMetaField->IsCopyable;

            $wikiwijsTemplateMetafield->Label_EN = $wikiwijsMetafield->Label_EN;
            $wikiwijsTemplateMetafield->Label_NL = $wikiwijsMetafield->Label_NL;
            $wikiwijsTemplateMetafield->Description_EN = $wikiwijsMetafield->Description_EN;
            $wikiwijsTemplateMetafield->Description_NL = $wikiwijsMetafield->Description_NL;
            $wikiwijsTemplateMetafield->InfoText_EN = $wikiwijsMetafield->InfoText_EN;
            $wikiwijsTemplateMetafield->InfoText_NL = $wikiwijsMetafield->InfoText_NL;
            $wikiwijsTemplateMetafield->SkipPropagation = true;
            $wikiwijsTemplateMetafield->write();
            echo("-     Created new templatemetafield for 'Wikiwijs'");
            echo('<br>');

            $combinedTemplateMetaField->Label_EN = $combinedMetaField->Label_EN;
            $combinedTemplateMetaField->Label_NL = $combinedMetaField->Label_NL;
            $combinedTemplateMetaField->Description_EN = $combinedMetaField->Description_EN;
            $combinedTemplateMetaField->Description_NL = $combinedMetaField->Description_NL;
            $combinedTemplateMetaField->InfoText_EN = $combinedMetaField->InfoText_EN;
            $combinedTemplateMetaField->InfoText_NL = $combinedMetaField->InfoText_NL;
            $combinedTemplateMetaField->IsEnabled = $instituteHasEdusourceLicense ? $combinedTemplateMetaField->IsEnabled : false;
            $combinedTemplateMetaField->IsRequired = $instituteHasEdusourceLicense ? $combinedTemplateMetaField->IsRequired : false;
            $combinedTemplateMetaField->SkipPropagation = true;
            $combinedTemplateMetaField->write();
            echo("-     Repurposed old combined templatemetafield for 'edusources'");
            echo('<br>');

            echo("-     Copying answers for 'edusources & Wikiwijs'-metafield");
            echo('<br>');
            foreach (RepoItemMetaField::get()->filter('MetaFieldID', $combinedMetaField->ID) as $combinedRepoItemMetafield) {
                try {
                    echo("-         Copying answer for 'edusources & Wikiwijs'-metafield");
                    echo('<br>');
                    $wikiwijsRepoItemMetafield = new RepoItemMetaField();
                    $wikiwijsRepoItemMetafield->RepoItemID = $combinedRepoItemMetafield->RepoItemID;
                    $wikiwijsRepoItemMetafield->MetaFieldID = $wikiwijsMetafield->ID;
                    $wikiwijsRepoItemMetafield->write();

                    foreach ($combinedRepoItemMetafield->RepoItemMetafieldValues()->filter('IsRemoved', 0) as $combinedRepoItemMetafieldValues) {
                        $wikiwijsRepoItemMetafieldValue = new RepoItemMetaFieldValue();
                        $wikiwijsRepoItemMetafieldValue->Value = $combinedRepoItemMetafieldValues->Value;
                        $wikiwijsRepoItemMetafieldValue->RepoItemMetaFieldID = $wikiwijsRepoItemMetafield->ID;
                        $wikiwijsRepoItemMetafieldValue->write();
                    }
                } catch (Exception $exception) {
                    echo("-             ERROR: on repoItem (ID " . $wikiwijsRepoItemMetafield->RepoItemID . ')  ' . $wikiwijsRepoItemMetafield->RepoItem()->Title);
                    echo('<br>');
                    echo("-             ERROR: " . $exception->getMessage());
                    echo('<br>');
                }
            }
        }
    }

    function getTitlesOfInstitutesWithEdusourceLicense() {
        return [

        ];
    }
}