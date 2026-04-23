<?php

use SilverStripe\ORM\DB;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;

class MetaFieldOptionsSyncer {

    private MetaField $metaField;
    private ?MetaFieldOption $metaFieldOption;
    private $rootNode;

    public function __construct(array $rootNode, MetaField $metaField, ?MetaFieldOption $metaFieldOption = null) {
        $this->rootNode = $rootNode;
        $this->metaField = $metaField;
        $this->metaFieldOption = $metaFieldOption;
    }

    function run() {
        try {
            DB::get_conn()->transactionStart();
            $rootNodeId = $this->rootNode['@id'];
            $existingMetaFieldOption = $this->metaField->MetaFieldOptions()->filter(["Value" => $rootNodeId])->first();

            $metaFieldOptionId = $this->metaFieldOption->ID ?? null;
            if ($existingMetaFieldOption) {
                $metaFieldOptionId = $this->updateMetaFieldOption($this->rootNode, $existingMetaFieldOption, 1, $metaFieldOptionId)->ID;
            } else {
                $metaFieldOptionId = $this->createMetaFieldOption($this->rootNode, 1, $metaFieldOptionId)->ID;
            }

            if (array_key_exists('metaFieldOptions', $this->rootNode)){
                $this->loopThroughChildrenNodes($this->rootNode['metaFieldOptions'], $metaFieldOptionId);
            }
            DB::get_conn()->transactionEnd();
        } catch (Exception $e) {
            Logger::debugLog("Syncing went wrong for $rootNodeId : " . $e->getMessage());
            DB::get_conn()->transactionRollback();
        }
    }

    private function loopThroughChildrenNodes($childNodes, $parentMetaFieldOptionId) {
        $sortOrder = 1;
        foreach ($childNodes as $childNode){
            $childNodeId = $childNode['@id'];
            $existingMetaFieldOption = $this->metaField->MetaFieldOptions()->filter(["Value" => $childNodeId])->first();

            $metaFieldOptionId = null;
            if ($existingMetaFieldOption) {
                $metaFieldOptionId = $this->updateMetaFieldOption($childNode, $existingMetaFieldOption, $sortOrder, $parentMetaFieldOptionId)->ID;
            } else {
                $metaFieldOptionId = $this->createMetaFieldOption($childNode, $sortOrder, $parentMetaFieldOptionId)->ID;
            }
            if (array_key_exists('metaFieldOptions', $childNode)){
                $this->loopThroughChildrenNodes($childNode['metaFieldOptions'], $metaFieldOptionId);
            }
            $sortOrder += 1;
        }
    }

    private function createMetaFieldOption($metaFieldOption, $sortOrder, $parentMetaFieldOptionId = null): MetaFieldOption {
        if (array_key_exists('skos:prefLabel',$metaFieldOption)) {
            $labelValue = $this->getPreferredLabelValue($metaFieldOption['skos:prefLabel']);
            if ($labelValue) {
                $newMetaFieldOption = new MetaFieldOption();
                $newMetaFieldOption->Value = $metaFieldOption['@id'];
                $newMetaFieldOption->Label_NL = $labelValue;
                $newMetaFieldOption->Label_EN = $labelValue;
                $newMetaFieldOption->MetaFieldID = $this->metaField->ID;
                $newMetaFieldOption->MetaFieldOptionID = $parentMetaFieldOptionId;
                $newMetaFieldOption->SortOrder = $sortOrder;
                $newMetaFieldOption->SetCustomSortOrder = true;

                $altLabelText = $this->formatAltLabelsAsDescription($metaFieldOption);
                $newMetaFieldOption->Description_EN = $altLabelText;
                $newMetaFieldOption->Description_NL = $altLabelText;

                $newMetaFieldOption->write();
                return $newMetaFieldOption;
            } else {
                throw new Exception("Label value does not contain valid value");
            }
        } else {
            throw new Exception("Label key missing");
        }
    }

    private function updateMetaFieldOption($metaFieldOption, $existingMetaFieldOption, $sortOrder, $parentMetaFieldOptionId = null): MetaFieldOption {
        if (array_key_exists('skos:prefLabel',$metaFieldOption)) {
            $labelValue = $this->getPreferredLabelValue($metaFieldOption['skos:prefLabel']);
            if ($labelValue) {
               $existingMetaFieldOption->Value = $metaFieldOption['@id'];
               $existingMetaFieldOption->Label_NL = $labelValue;
               if ($existingMetaFieldOption->Label_EN){
                   $existingMetaFieldOption->Label_EN = $labelValue;
               }
               $existingMetaFieldOption->MetaFieldID = $this->metaField->ID;
               $existingMetaFieldOption->MetaFieldOptionID = $parentMetaFieldOptionId;
               $existingMetaFieldOption->SortOrder = $sortOrder;
               $existingMetaFieldOption->SetCustomSortOrder = true;

               $altLabelText = $this->formatAltLabelsAsDescription($metaFieldOption);
               $existingMetaFieldOption->Description_EN = $altLabelText;
               $existingMetaFieldOption->Description_NL = $altLabelText;

               $existingMetaFieldOption->write();
               return $existingMetaFieldOption;
            } else {
                throw new Exception("Label value does not contain valid value");
            }
        } else {
            throw new Exception("Label key missing");
        }
    }

    private function getPreferredLabelValue($prefLabel): ?string {
        // Existing sources can provide either an object with @value or a language array.
        if (is_array($prefLabel) && array_key_exists('@value', $prefLabel)) {
            return $prefLabel['@value'] ?: null;
        }

        if (!is_array($prefLabel)) {
            return null;
        }

        $firstAvailable = null;
        foreach ($prefLabel as $label) {
            if (!is_array($label) || !array_key_exists('@value', $label)) {
                continue;
            }

            $value = $label['@value'];
            if (!$value) {
                continue;
            }

            if ($firstAvailable === null) {
                $firstAvailable = $value;
            }

            $language = strtolower((string)($label['@language'] ?? ''));
            if ($language === 'la') {
                return $value;
            }
        }

        foreach ($prefLabel as $label) {
            if (!is_array($label) || !array_key_exists('@value', $label)) {
                continue;
            }

            $value = $label['@value'];
            if (!$value) {
                continue;
            }

            $language = strtolower((string)($label['@language'] ?? ''));
            if ($language === 'en') {
                return $value;
            }
        }

        return $firstAvailable;
    }

    /**
     * Flattens skos:altLabel (JSON-LD language-tagged literals or plain strings) into one line:
     * "first - second - third"
     */
    private function formatAltLabelsAsDescription(array $metaFieldOption): ?string {
        if (!array_key_exists('skos:altLabel', $metaFieldOption)) {
            return null;
        }

        $altLabels = $metaFieldOption['skos:altLabel'];

        if (is_string($altLabels)) {
            $trimmed = trim($altLabels);
            return $trimmed !== '' ? $trimmed : null;
        }

        if (!is_array($altLabels)) {
            return null;
        }

        $values = [];

        // Single literal object: { "@language": "en", "@value": "..." }
        if (array_key_exists('@value', $altLabels)) {
            $v = $altLabels['@value'];
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
            return null;
        }

        foreach ($altLabels as $label) {
            if (is_string($label)) {
                $t = trim($label);
                if ($t !== '') {
                    $values[] = $t;
                }
                continue;
            }
            if (!is_array($label) || !array_key_exists('@value', $label)) {
                continue;
            }
            $v = $label['@value'];
            if ($v !== null && $v !== '') {
                $values[] = (string) $v;
            }
        }

        if (count($values) === 0) {
            return null;
        }

        return implode(' - ', $values);
    }
}
