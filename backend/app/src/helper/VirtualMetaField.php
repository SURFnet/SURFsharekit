<?php

namespace SurfSharekit\Helper;

use SimpleXMLElement;
use SurfSharekit\Api\OaipmhApiController;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\XMLHelper;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\ProtocolNode;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

/**
 * Class VirtualMetaField
 * @package SurfSharekit\Helper
 * Obect with static functions representing a virtual metafield field that can be added to a protocol node
 */
class VirtualMetaField {
    public static function addNodeForType($type, RepoItem $repoItem, ProtocolNode $context, SimpleXMLElement $parentNode = null) {
        switch ($type) {
            case 'dii:Identifier':
                return self::addDiiIdentifier($repoItem, $context, $parentNode);
                break;
            case 'dcterms:modified':
                return self::addDctermsModified($repoItem, $context, $parentNode);
                break;
            case 'mods:dateIssued':
                return self::addModsDateIssued($repoItem, $context, $parentNode);
                break;
            case 'mods:namePart:family':
                return self::addPersonFamilyName($repoItem, $context, $parentNode);
                break;
            case 'mods:namePart:given':
                return self::addPersonGivenName($repoItem, $context, $parentNode);
                break;
            case 'mods:displayForm':
                return self::addPersonDisplayForm($repoItem, $context, $parentNode);
                break;
            case 'lom:languageString':
                return self::addLanguageString($repoItem, $context, $parentNode);
                break;
            case 'lom:Identifier':
                return self::addLomIdentifier($repoItem, $context, $parentNode);
                break;
            case 'vCard':
                return self::addVCard($repoItem, $context, $parentNode);
                break;
            case 'lom:encaseInStringNode':
                return self::addEncaseInStringNode($repoItem, $context, $parentNode);
                break;
            case 'lom:technical':
                return self::addLomTechnicalNode($repoItem, $context, $parentNode);
                break;
            case 'didl:resource:file':
                return self::addDidlResourceFile($repoItem, $context, $parentNode);
                break;
            case 'didl:resource:link':
                return self::addDidlResourceLink($repoItem, $context, $parentNode);
                break;
            case 'mods:genre:thesis':
                return self::addThesisType($repoItem, $context, $parentNode);
                break;
            case 'hbo:namePart:departmentFromLowerInstitute':
                return self::addNamePartDepartmentFromLowerInstitute($repoItem, $context, $parentNode);
                break;
            case 'hbo:namePart':
                return self::addNamePart($repoItem, $context, $parentNode);
                break;
            case 'dai:identifierExtension':
                return self::addDaiIdentifierExtension($repoItem, $context, $parentNode);
                break;
            case 'dai:identifier':
                return self::addDaiIdentifier($repoItem, $context, $parentNode);
                break;
            case 'orcid:identifier':
                return self::addOrcidIdentifier($repoItem, $context, $parentNode);
                break;
            case 'isni:identifier':
                return self::addIsniIdentifier($repoItem, $context, $parentNode);
                break;
            case 'localAuthor:identifier':
                return self::addLocalAuthorIdentifier($repoItem, $context, $parentNode);
                break;
            case 'hogeschool:identifier':
                return self::addHogeschoolIdentifier($repoItem, $context, $parentNode);
                break;
            case 'csv:personalIdentifiers':
                return self::getCSVPersonalIdentifiers($repoItem, $context);
                break;
            case 'json:hogeschoolIdentifier':
                return self::getJSONPersonInfo($repoItem, $context, 'HogeschoolID');
                break;
            case 'json:orcid':
                return self::getJSONPersonInfo($repoItem, $context, 'ORCID', 'http://orcid.org/');
                break;
            case 'json:dai':
                return self::getJSONPersonInfo($repoItem, $context, 'PersistentIdentifier', 'info:eu-repo/dai/nl/');
                break;
            case 'json:isni':
                return self::getJSONPersonInfo($repoItem, $context, 'ISNI', 'http://isni.org/isni/');
                break;
            case 'mods:name:personal':
                return self::addModsNamePersonal($repoItem, $context, $parentNode);
                break;
            case 'lom:contribute:validator':
                return self::addLomValidatorFromLowerInstitute($repoItem, $context, $parentNode);
                break;
            case 'lom:relation:description':
                return self::addLomRelationDescription($repoItem, $context, $parentNode);
                break;
            case 'lom:rightsofusage':
                return self::addLomRightsOfUsage($repoItem, $context, $parentNode);
                break;
            case 'oai:Identifier':
                return self::addOAIIdentifier($repoItem, $context, $parentNode);
                break;
            case 'mods:identifier:isbn':
                return self::addISBNIdentifier($repoItem, $context, $parentNode);
                break;
            case 'didl:resource':
                return self::getResourceLink($repoItem, $context, $parentNode);
                break;
            case 'lom:classification:taxonomy':
                return self::addClassificationTaxonomy($repoItem, $context, $parentNode);
                break;
            case 'json:modified':
                return self::getJSONModified($repoItem);
                break;
            case 'json:created':
                return self::getJSONCreated($repoItem);
                break;
            case 'csv:created':
                return self::getCSVCreated($repoItem);
                break;
            case 'csv:creator':
                return self::getCSVCreator($repoItem);
                break;
            case 'json:hasParts':
                return self::getJSONHasParts($repoItem, $context);
                break;
            case 'json:partOf':
                return self::getJSONPartOf($repoItem, $context);
                break;
            case 'json:dateIssued':
                return self::getJSONDateIssued($repoItem, $context);
                break;
            case 'json:personEmail':
                return self::getJSONPersonEmail($repoItem, $context);
                break;
            case 'json:validator':
                return self::getJSONValidator($repoItem, $context);
                break;
            case 'json:resourceMimeType':
                return self::getJSONResourceMimeType($repoItem, $context);
                break;
            case 'json:taxonomy':
                return self::getJSONClassificationTaxonomy($repoItem, $context);
                break;
            case 'json:vocabulary':
                return self::getJSONTreeMultiSelect($repoItem, $context);
                break;
            case 'json:domain':
                return self::getJSONTreeMultiSelect($repoItem, $context);
                break;
            case 'json:diiIdentifier':
                return self::getJSONDiiIdentifier($repoItem, $context);
                break;
            case 'json:alias':
                return self::getJSONAlias($repoItem, $context);
                break;
            case 'json:rootOrganisation':
                return self::getJSONRootOrganisation($repoItem, $context);
                break;
            case 'json:etag':
                return self::getEtag($repoItem, $context);
                break;
        }
    }

    private static function getRepoItemsForSubContext(RepoItem $repoItem, $context) {
        $repoItems = [];
        if ($context->SubMetaFieldID) {
            $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
            if ($repoItemMetaField) {
                $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]);
                foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
                    if ($subRepoItem = $repoItemMetaFieldValue->RepoItem()) {
                        $repoItems[] = $subRepoItem;
                    }
                }
            }
        }
        return $repoItems;
    }

    private static function getRepoItemMetaField(RepoItem $repoItem, $context) {
        if ($context->MetaField()) {
            /** @var RepoItemMetaField $repoItemMetaField */
            $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
                ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
                ->filter('MetaFieldID', $context->MetaFieldID)->first();
            return $repoItemMetaFieldForMetaField;
        }
        return null;
    }

    private static function getRepoItemMetaFieldFromUuid(RepoItem $repoItem, $metaFieldUuid) {
        /** @var RepoItemMetaField $repoItemMetaField */
        $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
            ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
            ->filter('MetaFieldUuid', $metaFieldUuid)->first();
        return $repoItemMetaFieldForMetaField;
    }

    private static function addNode(SimpleXMLElement $parentNode, $contentItem, $context) {
        $namespace = empty($context->NamespaceURI) ? null : $context->NamespaceURI;
        /** @var SimpleXMLElement $node */
        return $parentNode->addChild($context->NodeTitle, XMLHelper::encodeXMLString($contentItem), $namespace);
    }

    private static function addLanguageString($repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            $answer = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($answer) {
                $value = $answer->Value;
                $node = self::addNode($parentNode, $value, $context);
                if ($value && $value != '') {
                    if ($repoItem->Language && $repoItem->Language != '') {
                        $node->addAttribute('language', XMLHelper::encodeXMLString($repoItem->Language));
                    }
                }
                return $node;
            }
        }
        return $parentNode;
    }

    private static function addClassificationTaxonomy(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            $answers = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0, 'MetaFieldOption.ID:not' => null])->sort('metafieldoption_SurfSharekit_MetaFieldOption.Label_NL ASC');
            if ($answers->count()) {
                $previousLabel = '';
                /** @var RepoItemMetaFieldValue $answer */

                foreach ($answers as $answer) {
                    /** @var MetaFieldOption $option */
                    $option = $answer->MetaFieldOption();
                    // remove prefix as LOM needs the raw ID instead of URL
                    $optionValue = str_replace('http://purl.edustandaard.nl/concept/', '', $option->Value);
                    $optionLabel = $option->Label_NL;
                    Logger::debugLog([$optionValue, $optionLabel, $previousLabel]);
                    $taxonPathNode = null;
                    if (stripos($optionLabel, $previousLabel) === false) {
                        // new taxonomy path
                        $taxonPathNode = $parentNode->addChild('taxonPath');
                        $sourceNode = $taxonPathNode->addChild('source');
                        $sourceNode->addChild('string', 'http://purl.edustandaard.nl/concept')->addAttribute('language', 'x-none');
                        $previousLabel = $optionLabel;
                    }
                    if (!is_null($taxonPathNode)) {
                        $taxonNode = $taxonPathNode->addChild('taxon');
                        $taxonNode->addChild('id', $optionValue);
                        $entryNode = $taxonNode->addChild('entry');
                        $stringNode = $entryNode->addChild('string', $optionLabel);
                        $stringNode->addAttribute('language', 'nl');
                    }

                }

            }
        }
        return $parentNode;
    }

    private static function addISBNIdentifier(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            $answer = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($answer) {
                $value = $answer->getField('Value');
                if (!empty($value)) {
                    $contentItem = 'URN:ISBN:' . $value;
                    $node = self::addNode($parentNode, $contentItem, $context);
                    $node->addAttribute('type', 'uri');
                    return $node;
                }
            }
        }
        return $parentNode;
    }

    private static function addDiiIdentifier(RepoItem $repoItem, $context, $parentNode) {
        $urn = self::mapUrn($repoItem, $context);
        if ($urn) {
            $contentItem = $urn . '-' . $repoItem->getField('Uuid');
            return self::addNode($parentNode, $contentItem, $context);
        }
        return $parentNode;
    }

    private static function addModsNamePersonal(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaFieldFromUuid($repoItem, 'a361a5a9-a80b-4e2c-8145-a60e2fce9acf');
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $node = self::addNode($parentNode, null, $context);
                    $node->addAttribute('type', 'personal');
                    $node->addAttribute('ID', '_' . $repoItem->getField('Uuid'));
                    return $node;
                }
            }
        }
        return $parentNode;
    }


    private static function addDaiIdentifierExtension(RepoItem $repoItem, $context, $parentNode) {
        return static::addPersonIdentifier($repoItem, $context, $parentNode, 'PersistentIdentifier', 'info:eu-repo/dai/nl');
    }

    private static function addDaiIdentifier(RepoItem $repoItem, $context, $parentNode) {
        //return static::addPersonIdentifier($repoItem, $context, $parentNode, 'PersistentIdentifier', 'info:eu-repo/dai/nl');
        return static::addNameIdentifier($repoItem, $context, $parentNode, 'PersistentIdentifier', 'dai-nl', 'info:eu-repo/dai/nl');
    }

    private static function addOrcidIdentifier(RepoItem $repoItem, $context, $parentNode) {
       // return static::addPersonIdentifier($repoItem, $context, $parentNode, 'ORCID', null, 'http://orcid.org/');
        return static::addNameIdentifier($repoItem, $context, $parentNode, 'ORCID', 'orcid', 'http://id.loc.gov/vocabulary/identifiers/orcid');
    }

    private static function addISNIIdentifier(RepoItem $repoItem, $context, $parentNode) {
        return static::addNameIdentifier($repoItem, $context, $parentNode, 'ISNI', 'isni', 'http://id.loc.gov/vocabulary/identifiers/isni');
    }

    private static function addLocalAuthorIdentifier(RepoItem $repoItem, $context, $parentNode) {
        return static::addNameIdentifier($repoItem, $context, $parentNode, 'HogeschoolID', 'local');
    }

    private static function addHogeschoolIdentifier(RepoItem $repoItem, $context, $parentNode) {
        return static::addPersonIdentifier($repoItem, $context, $parentNode, 'HogeschoolID', null);
    }

    private static function addNameIdentifier(RepoItem $repoItem, $context, $parentNode, $personField, $type = null, $typeURI = null){
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $contentItem = $person->getField($personField);
                    if ($contentItem && strlen($contentItem)) {
                        $node = self::addNode($parentNode, $contentItem, $context);
                        if (!is_null($type)) {
                            $node->addAttribute('type', $type);
                        }
                        if (!is_null($typeURI)) {
                            $node->addAttribute('typeURI', $typeURI);
                        }
                        return $node;
                    }
                }
            }
        }
        return $parentNode;
    }

    /** Deprecated */
    private static function addPersonIdentifier(RepoItem $repoItem, $context, $parentNode, $personField, $authority, $prefix = '') {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $contentItem = $person->getField($personField);
                    if ($contentItem && strlen($contentItem)) {
                        $contentItem = $prefix . $contentItem;
                        $node = self::addNode($parentNode, $contentItem, $context);
                        if (!is_null($authority)) {
                            $node->addAttribute('authority', $authority);
                        }
                        $node->addAttribute('IDref', '_' . $repoItem->getField('Uuid'));
                        return $node;
                    }
                }
            }
        }
        return $parentNode;
    }

    private static function addLomIdentifier(RepoItem $repoItem, $context, $parentNode) {
        $contentItem = 'urn:uuid:' . $repoItem->Uuid;
        return self::addNode($parentNode, $contentItem, $context);
    }

    private static function addOAIIdentifier(RepoItem $repoItem, $context, $parentNode) {
        $contentItem = OaipmhApiController::$OAI_PREFIX . ':' . OaipmhApiController::$OAI_NAMESPACE . ':' . $repoItem->Uuid;
        return self::addNode($parentNode, $contentItem, $context);
    }

    private static function addVCard(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            /** @var RepoItemMetaFieldValue $answer */
            $answer = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($answer) {
                $valueString = self::escapeVcardString($answer->getRelatedObjectTitle());
                if ($answer->Institute() && $answer->Institute()->exists()) {
                    $vCardValue = "BEGIN:VCARD\nVERSION:3.0\nFN:$valueString\nN:$valueString;;;;\nORG:$valueString\nEND:VCARD\n";
                } else {
                    $vCardValue = "BEGIN:VCARD\nVERSION:3.0\nFN:$valueString\nN:$valueString;;;;\nEND:VCARD\n";
                }
                return self::addNode($parentNode, $vCardValue, $context);
            }
        }
        return $parentNode;
    }

    private static function addLomTechnicalNode(RepoItem $repoItem, $context, $parentNode) {
        $hasMovedTechnicalNodeToParentNode = false;

        $filesRepoItemMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'Attachment'])->first();
        if ($filesRepoItemMetaField && $filesRepoItemMetaField->exists()) {
            foreach ($filesRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $value) {
                $fileRepoItem = $value->RepoItem();
                if (!$fileRepoItem || !$fileRepoItem->exists()) {
                    continue;
                }
                $fileAnswer = $fileRepoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'File'])->first();
                if (!$fileAnswer || !$fileAnswer->exists()) {
                    continue;
                }
                $fileAnswerValue = $fileAnswer->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if (!$fileAnswerValue || !$fileAnswerValue->exists()) {
                    continue;
                }
                /**
                 * @var $file RepoItemFile
                 */
                $file = $fileAnswerValue->RepoItemFile();
                if (!$file || !$file->exists()) {
                    continue;
                }
                if (!$hasMovedTechnicalNodeToParentNode) {
                    $parentNode = $parentNode->addChild('technical');
                    $hasMovedTechnicalNodeToParentNode = true;
                }
                $parentNode->addChild('format', XMLHelper::encodeXMLString($file->getMimeType()));
                $parentNode->addChild('location', XMLHelper::encodeXMLString($file->getPublicStreamURL()));
            }
        }
        $linkRepoItemMetaField = $repoItem->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'RepoItemLink'])->first();
        if ($linkRepoItemMetaField && $linkRepoItemMetaField->exists()) {
            foreach ($linkRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $value) {
                $linkRepoItems = $value->RepoItem();
                if (!$linkRepoItems || !$linkRepoItems->exists()) {
                    continue;
                }
                $urlAnswer = $linkRepoItems->RepoItemMetaFields()->filter(['MetaField.MetaFieldType.Title' => 'URL'])->first();
                if (!$urlAnswer || !$urlAnswer->exists()) {
                    continue;
                }
                $fileAnswerValue = $urlAnswer->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                if (!$fileAnswerValue || !$fileAnswerValue->exists()) {
                    continue;
                }

                $url = $fileAnswerValue->Value;
                if (!$url) {
                    continue;
                }
                if (!$hasMovedTechnicalNodeToParentNode) {
                    $parentNode = $parentNode->addChild('technical');
                    $hasMovedTechnicalNodeToParentNode = true;
                }
                $parentNode->addChild('format', XMLHelper::encodeXMLString('text/html'));
                $parentNode->addChild('location', XMLHelper::encodeXMLString($url));
            }
        }
        return $parentNode;
    }

    private static function addEncaseInStringNode(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            foreach ($repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $value) {
                $keywordNode = self::addNode($parentNode, '', $context);
                $stringNode = $keywordNode->addChild('string', XMLHelper::encodeXMLString($value->Value ?? $value->getRelatedObjectTitle()));
                $stringNode->addAttribute('language', 'x-none');
            }
        }
        return $parentNode;
    }

    private static function addModsDateIssued(RepoItem $repoItem, $context, $parentNode) {
        $contentItem = '1970-01-01';
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($repoItemMetaFieldValue) {
                $contentItem = DateHelper::iso8601FromDMYString($repoItemMetaFieldValue->Value);
            }
        }

        return self::addNode($parentNode, $contentItem, $context);
    }

    private static function addDctermsModified(RepoItem $repoItem, $context, $parentNode) {
        $modified = $repoItem->LastEdited;
        $contentItem = DateHelper::iso8601zFromString($modified);
        return self::addNode($parentNode, $contentItem, $context);
    }

    private static function getJSONModified(RepoItem $repoItem) {
        $modified = $repoItem->LastEdited;
        return DateHelper::iso8601zFromString($modified);
    }

    private static function getJSONCreated(RepoItem $repoItem) {
        $created = $repoItem->Created;
        return DateHelper::iso8601zFromString($created);
    }

    private static function getCSVCreated(RepoItem $repoItem) {
        $created = $repoItem->Created;
        return DateHelper::localExcelDateTimeFromString($created);
    }

    private static function getCSVCreator(RepoItem $repoItem) {
        return $repoItem->Owner()->Title;
    }

    private static function getJSONValidator(RepoItem $repoItem, ProtocolNode $context) {
        if (($institute = $repoItem->Institute()) && $institute && $institute->exists()) {
            /** @var RepoItemMetaFieldValue $answer */
            if ($institute->Level == 'consortium') {
                return $institute->Title;
            }
        }
        return null;
    }

    private static function getJSONDateIssued(RepoItem $repoItem, $context) {
        $contentItem = '1970-01-01';
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($repoItemMetaFieldValue) {
                $contentItem = DateHelper::iso8601FromDMYString($repoItemMetaFieldValue->Value);
            }
        }

        return $contentItem;
    }

    private static function getJSONPersonEmail(RepoItem $repoItem, $context) {
        return static::getJSONPersonInfo($repoItem, $context, 'Email');
    }

    private static function getCSVPersonalIdentifiers(RepoItem $repoItem, $context) {
        // if SubMetaField is selected, find childRepoItem and use to continue
        $repoItems = self::getRepoItemsForSubContext($repoItem, $context);
        if (count($repoItems)) {
            $context->MetaFieldID = $context->SubMetaFieldID;
            $result = [];
            foreach ($repoItems as $repoItem) {
                $hogeschoolID = static::getJSONPersonInfo($repoItem, $context, 'HogeschoolID');
                $orcid = static::getJSONPersonInfo($repoItem, $context, 'ORCID', 'http://orcid.org/');
                $dai = static::getJSONPersonInfo($repoItem, $context, 'PersistentIdentifier', 'info:eu-repo/dai/nl/');
                $isni = static::getJSONPersonInfo($repoItem, $context, 'ISNI', 'http://isni.org/isni/');
                $result[] = ['HogeschoolID' => $hogeschoolID, 'ORCID' => $orcid, 'DAI' => $dai, 'ISNI' => $isni];
            }
            return $result;
        } else {
            $hogeschoolID = static::getJSONPersonInfo($repoItem, $context, 'HogeschoolID');
            $orcid = static::getJSONPersonInfo($repoItem, $context, 'ORCID', 'http://orcid.org/');
            $dai = static::getJSONPersonInfo($repoItem, $context, 'PersistentIdentifier', 'info:eu-repo/dai/nl/');
            $isni = static::getJSONPersonInfo($repoItem, $context, 'ISNI', 'http://isni.org/isni/');
            return ['HogeschoolID' => $hogeschoolID, 'ORCID' => $orcid, 'DAI' => $dai, 'ISNI' => $isni];
        }
    }

    private static function getJSONPersonInfo(RepoItem $repoItem, $context, $field, $prefix = '') {
        $contentItem = null;
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $contentItem = $person->$field;
                    if ($contentItem && strlen($contentItem)) {
                        return $prefix . $contentItem;
                    }

                }
            }
        }

        return $contentItem;
    }

    private static function getJSONHasParts(RepoItem $repoItem, $context) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        $answers = [];
        if ($repoItemMetaField) {
            $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]);
            /** @var RepoItem $subRepoItem */
            foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
                $subRepoItem = $repoItemMetaFieldValue->RepoItem();
                if ($subRepoItem->exists()) {
                    $subRepoItemMetaField = $subRepoItem->RepoItemMetaFields()
                        ->filter('MetaField.MetaFieldTypeID', 26)->first();
                    if ($subRepoItemMetaField) {
                        $subRepoItemMetaFieldValue = $subRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
                        if ($subRepoItemMetaFieldValue) {
                            $subSubRepoItemUuid = $subRepoItemMetaFieldValue->getField('RepoItemUuid');
                            $answers[] = $subSubRepoItemUuid;
                        }
                    }
                }
            }
        }
        return $answers;
    }

    private static function getJSONPartOf(RepoItem $repoItem, $context) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        $answers = [];
        if ($repoItemMetaField) {
            $repoItemMetaFieldValues = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]);
            /** @var RepoItem $subRepoItem */
            foreach ($repoItemMetaFieldValues as $repoItemMetaFieldValue) {
                $subRepoItem = $repoItemMetaFieldValue->RepoItem();
                if ($subRepoItem->exists()) {
                    $answers[] = $repoItemMetaFieldValue->getField('RepoItemUuid');
                }
            }
        }
        return $answers;
    }

    private static function getJSONResourceMimeType(RepoItem $repoItem, $context) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var RepoItemFile $repoItemFile */
            if ($repoItemMetaFieldValue && $repoItemFile = $repoItemMetaFieldValue->RepoItemFile()) {
                if ($repoItemFile->exists()) {
                    $mimeType = $repoItemFile->getMimeType();
                    return $mimeType;
                }
            }
        }
    }

    private static function getJSONClassificationTaxonomy(RepoItem $repoItem, $context) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        $taxonPathNode = null;
        if ($repoItemMetaFieldForMetaField) {
            $answers = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0, 'MetaFieldOption.ID:not' => null])->sort('metafieldoption_SurfSharekit_MetaFieldOption.Label_NL ASC');
            if ($answers->count()) {
                $previousLabel = '';
                /** @var RepoItemMetaFieldValue $answer */
                $taxonPathNode = [];
                $idx = -1;
                foreach ($answers as $answer) {
                    /** @var MetaFieldOption $option */
                    $option = $answer->MetaFieldOption();
                    $optionValue = $option->Value;
                    $optionLabel = $option->Label_NL;

                    if (stripos($optionLabel, $previousLabel) === false) {
                        $idx++;
                        $taxonPathNode[$idx] = [];
                        $taxonPathNode[$idx]['taxonPath'] = [];
                    }

                    $taxonNode = ['taxon' => ['id' => $optionValue, 'entry' => $optionLabel]];
                    $taxonPathNode[$idx]['taxonPath'][] = $taxonNode;
                    $previousLabel = $optionLabel;
                }

            }
        }
        return $taxonPathNode;
    }

    private static function getJSONTreeMultiSelect(RepoItem $repoItem, $context) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        $taxonPathNode = [];
        if ($repoItemMetaFieldForMetaField) {
            $answers = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0, 'MetaFieldOption.ID:not' => null])->sort('metafieldoption_SurfSharekit_MetaFieldOption.Label_NL ASC');
            if ($answers->count()) {
                /** @var RepoItemMetaFieldValue $answer */
                foreach ($answers as $answer) {
                    /** @var MetaFieldOption $option */
                    $option = $answer->MetaFieldOption();
                    $optionValue = $option->Value;
                    $optionLabel = $option->Label_NL;

                    $taxonNode = ['source' => $optionValue, 'value' => $optionLabel];
                    $taxonPathNode[] = $taxonNode;
                }

            }
        }
        return $taxonPathNode;
    }

    private static function getJSONDiiIdentifier(RepoItem $repoItem, $context) {
        $urn = self::mapUrn($repoItem, $context);
        if ($urn) {
            $contentItem = $urn . '-' . $repoItem->getField('Uuid');
            return $contentItem;
        }
        return null;
    }

    private static function addPersonFamilyName(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $contentItem = $person->getFamilyName();
                    $node = self::addNode($parentNode, $contentItem, $context);
                    $node->addAttribute('type', 'family');
                    return $node;
                }
            }
        }
        return null;
    }

    private static function addDidlResourceFile(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var RepoItemFile $repoItemFile */
            if ($repoItemMetaFieldValue && $repoItemFile = $repoItemMetaFieldValue->RepoItemFile()) {
                if ($repoItemFile->exists()) {
                    $mimeType = $repoItemFile->getMimeType();
                    $resourceUrl = $repoItemFile->getPublicStreamURL();
                    $resourceNode = self::addNode($parentNode, '', $context);
                    $resourceNode->addAttribute('mimeType', XMLHelper::encodeXMLString($mimeType));
                    $resourceNode->addAttribute('ref', XMLHelper::encodeXMLString($resourceUrl));
                    return $resourceNode;
                }
            }
        }
        return $parentNode;
    }

    private static function addDidlResourceLink(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();

            if ($repoItemMetaFieldValue && $repoItemMetaFieldValue->Value) {
                $mimeType = 'text/html';
                $resourceUrl = $repoItemMetaFieldValue->Value;
                $resourceNode = self::addNode($parentNode, '', $context);
                $resourceNode->addAttribute('mimeType', XMLHelper::encodeXMLString($mimeType));
                $resourceNode->addAttribute('ref', XMLHelper::encodeXMLString($resourceUrl));
                return $resourceNode;
            }
        }
        return $parentNode;
    }

    private static function addPersonGivenName(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    $contentItem = $person->FirstName;
                    $node = self::addNode($parentNode, $contentItem, $context);
                    $node->addAttribute('type', 'given');
                    return $node;
                }
            }
        }
        return $parentNode;
    }

    private static function addPersonDisplayForm(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var Person $person */
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    if ($repoItem->Alias) {
                        $contentItem = $repoItem->Alias;
                    } else {
                        $contentItem = $person->getFullName();
                    }
                    $node = self::addNode($parentNode, $contentItem, $context);
                    return $node;
                }
            }
        }
        return $parentNode;
    }

    /** @var ProtocolNode $context */
    private static function addThesisType(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        $metaFieldOptionValue = '';
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($repoItemMetaFieldValue) {
                $metaFieldOption = $repoItemMetaFieldValue->MetaFieldOption();
                if ($metaFieldOption) {
                    $metaFieldOptionValue = $metaFieldOption->getField('Value');
                }

            }
        }

        $niveauRepoItemMetaFieldOptionValue = '';
        $niveauRepoItemMetaField = self::getRepoItemMetaFieldFromUuid($repoItem, '782f655c-1d4a-4123-9641-e784ca566f9d');
        if ($niveauRepoItemMetaField) {
            $niveauRepoItemMetaFieldValue = $niveauRepoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($niveauRepoItemMetaFieldValue) {
                $niveauRepoItemMetaFieldOption = $niveauRepoItemMetaFieldValue->MetaFieldOption();
                if ($niveauRepoItemMetaFieldOption) {
                    $niveauRepoItemMetaFieldOptionValue = $niveauRepoItemMetaFieldOption->getField('Value');
                }

            }
        }

        $lookupValue = $metaFieldOptionValue . '+' . $niveauRepoItemMetaFieldOptionValue;

        $contentItem = $context->mapAnswer($lookupValue);
        if (!is_null($contentItem)) {
            return self::addNode($parentNode, $contentItem, $context);
        } else {
            $contentItem = $context->mapAnswer($metaFieldOptionValue);
            if (!is_null($contentItem)) {
                return self::addNode($parentNode, $contentItem, $context);
            }
        }
        return $parentNode;
    }

    private static function addLomValidatorFromLowerInstitute(RepoItem $repoItem, $context, $parentNode) {
        if (($institute = $repoItem->Institute()) && $institute && $institute->exists()) {
            /** @var RepoItemMetaFieldValue $answer */
            if ($institute->Level == 'consortium') {
                $content = $institute->Title;
                $contentItem = self::escapeVcardString($content);
                /** @var SimpleXMLElement $contributeNode */
                $contributeNode = $parentNode->addChild('contribute');
                $roleNode = $contributeNode->addChild('role');
                $roleNode->addChild('source', 'LOMv1.0');
                $roleNode->addChild('value', 'validator');
                $vCardValue = "BEGIN:VCARD\nVERSION:3.0\nFN:$contentItem\nN:$contentItem;;;;\nORG:$contentItem\nEND:VCARD\n";
                $entityNode = $contributeNode->addChild('entity', XMLHelper::encodeXMLString($vCardValue));
                return $entityNode;
            }
        }
        return $parentNode;
    }

    private static function addLomRelationDescription(RepoItem $repoItem, $context, $parentNode) {
        $repoItemTitle = $repoItem->Title;
        if (!empty($repoItemTitle)) {
            return self::addNode($parentNode, $repoItemTitle, $context);
        }
        return $parentNode;
    }

    private static function addLomRightsOfUsage(RepoItem $repoItem, $context, $parentNode) {
        // First, check if the parent RepoItem has a value for usage rights
        // Disabled after changing to files/urls
//        $usageRightRepoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
//        if ($usageRightRepoItemMetaField) {
//            /** @var RepoItemMetaFieldValue|null $usageRightRepoItemMetaFieldValue */
//            $usageRightRepoItemMetaFieldValue = $usageRightRepoItemMetaField->RepoItemMetaFieldValues()->filter(["IsRemoved" => false])->first();
//            if ($usageRightRepoItemMetaFieldValue) {
//                $option = MetaFieldOption::get()->find("ID", $usageRightRepoItemMetaFieldValue->MetaFieldOptionID);
//                if ($option) {
//                    $mappedValue = $context->mapAnswer($option->Value);
//                    return self::addNode($parentNode, $mappedValue, $context);
//                }
//            }
//        }

        // Loop the child RepoItems of the parent and return the first usage right value that can be found.
        $subRepoItemIDs = $repoItem->getAllRepoItemMetaFieldValues()->filter(["RepoItemID:not" => [0, $repoItem->ID]])->column("RepoItemID");
        if ($subRepoItemIDs) {
            $subRepoItems = RepoItem::get()->filter([
                "ID" => $subRepoItemIDs,
                "RepoType" => ["RepoItemRepoItemFile", "RepoItemLink"]
            ]);

            foreach ($subRepoItems as $subRepoItem) {
                $usageRightRepoItemMetaField = self::getRepoItemMetaField($subRepoItem, $context);
                if ($usageRightRepoItemMetaField) {
                    /** @var RepoItemMetaFieldValue|null $usageRightRepoItemMetaFieldValue */
                    $usageRightRepoItemMetaFieldValue = $usageRightRepoItemMetaField->RepoItemMetaFieldValues()->filter(["IsRemoved" => false])->first();

                    if ($usageRightRepoItemMetaFieldValue) {
                        $option = MetaFieldOption::get()->find("ID", $usageRightRepoItemMetaFieldValue->MetaFieldOptionID);
                        if ($option) {
                            $mappedValue = $context->mapAnswer($option->Value);
                            return self::addNode($parentNode, $mappedValue, $context);
                        }
                    }
                }
            }
        }
        // No usage right value found on the parent RepoItem nor on its child RepoItems, use fallback
        $defaultValue = "alle-rechten-voorbehouden";
        $mappedValue = $context->mapAnswer($defaultValue);
        return self::addNode($parentNode, $mappedValue, $context);

    }

    private static function addNamePart(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            /** @var RepoItemMetaFieldValue $answer */
            $answers = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]);
            $addedInstitutes = [];
            $chains = [];
            // get first answer and chain each of them until root institute
            // collect uuids
            // get next answer and check if uuid already used
            // - if used, do nothing
            // - if not used, chain up and check if uuid is already used
            //    - if used, add to same root node
            //    - if not used, chain up and check if uuid is already used
            // sort from root to lowest
            foreach ($answers as $answer) {
                if ($answer->Institute() && $answer->Institute()->exists()) {
                    /** @var Institute $institute */
                    $institute = $answer->Institute();
                    if ($institute && $institute->exists()) {
                        if (array_key_exists($institute->Uuid, $addedInstitutes)) {
                            // do nothing
                        } else {
                            $instituteChain = $institute->getInstituteChain();
                            $chains[$institute->Uuid] = $instituteChain;
                            $addedInstitutes = array_merge($addedInstitutes, $instituteChain['addedInstitutes']);
                        }
                    }
                }
            }
            // loop all $chains

            foreach($chains as $chain) {
                $institutesToBeAdded = [];
                $instituteChain = $chain;
                $institute = $instituteChain['institute'];
                if ($institute && $institute->exists()) {
                    $node = self::addNode($parentNode, '', $context);
                    $node->addAttribute('type', XMLHelper::encodeXMLString('corporate'));
                    do {
                        $institute = $instituteChain['institute'];
                        $institutesToBeAdded[$institute->Uuid] = $institute;
                    } while ($instituteChain = $instituteChain['parentInstituteChain']);
                    $institutesToBeAdded = array_reverse($institutesToBeAdded);
                    foreach ($institutesToBeAdded as $institute) {
                        if ($institute && $institute->exists()) {
                            $instituteNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($institute->getTitle()));
                            $instituteLevel = $institute->getField('Level');
                            if (!empty($instituteLevel)) {
                                $instituteNode->addAttribute('type', XMLHelper::encodeXMLString($instituteLevel));
                            }
                        }
                    }
                }
            }
//
//            foreach ($lowestInstitutes as $lowestInstitute) {
//                $addedInstitutes[$lowestInstitute->Uuid] = $lowestInstitute;
//                $lowestInstituteContentItem = $lowestInstitute->getTitle();
//                $node = self::addNode($parentNode, '', $context);
//                $node->addAttribute('type', XMLHelper::encodeXMLString('corporate'));
//                $lowestInstituteNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($lowestInstituteContentItem));
//                $lowestInstituteLevel = $lowestInstitute->getField('Level');
//                if(!empty($lowestInstituteLevel)) {
//                    $lowestInstituteNode->addAttribute('type', XMLHelper::encodeXMLString($lowestInstituteLevel));
//                }
//
//                $parentDepartment = $lowestInstitute->getParentDepartment();
//                if (!is_null($parentDepartment) && $parentDepartment->exists()) {
//                    // add department
//                    $addedInstitutes[$parentDepartment->Uuid] = $parentDepartment;
//                    $parentDepartmentContentItem = $parentDepartment->getTitle();
//                    $parentDepartmentNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($parentDepartmentContentItem));
//                    $parentDepartmentLevel = $parentDepartment->getField('Level');
//                    if (!empty($parentDepartmentLevel)) {
//                        $parentDepartmentNode->addAttribute('type', XMLHelper::encodeXMLString($parentDepartmentLevel));
//                    }
//                    $parentOrganisation = $parentDepartment->getRootInstitute();
//                    if (!is_null($parentOrganisation) && $parentOrganisation->exists()) {
//                        // add organisation
//                        $addedInstitutes[$parentOrganisation->Uuid] = $parentOrganisation;
//                        $parentOrganisationContentItem = $parentOrganisation->getTitle();
//                        $parentOrganisationNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($parentOrganisationContentItem));
//                        $parentOrganisationLevel = $parentOrganisation->getField('Level');
//                        if (!empty($parentOrganisationLevel)) {
//                            $parentOrganisationNode->addAttribute('type', XMLHelper::encodeXMLString($parentOrganisationLevel));
//                        }
//                    }
//                }else {
//                    $parentOrganisation = $lowestInstitute->getRootInstitute();
//                    if (!is_null($parentOrganisation) && $parentOrganisation->exists()) {
//                        // add organisation
//                        $addedInstitutes[$parentOrganisation->Uuid] = $parentOrganisation;
//                        $parentOrganisationContentItem = $parentOrganisation->getTitle();
//                        $parentOrganisationNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($parentOrganisationContentItem));
//                        $parentOrganisationLevel = $parentOrganisation->getField('Level');
//                        if (!empty($parentOrganisationLevel)) {
//                            $parentOrganisationNode->addAttribute('type', XMLHelper::encodeXMLString($parentOrganisationLevel));
//                        }
//                    }
//                }
//            }
//            // add rest of institutes
//            foreach ($answers as $answer) {
//                if ($answer->Institute() && $answer->Institute()->exists()) {
//                    $institute = $answer->Institute();
//                    if ($institute && $institute->exists()) {
//                        if(!array_key_exists($institute->Uuid, $addedInstitutes)){
//                            $node = self::addNode($parentNode, '', $context);
//                            $node->addAttribute('type', XMLHelper::encodeXMLString('corporate'));
//                            $instituteNode = $node->addChild('hbo:namePart', XMLHelper::encodeXMLString($institute->getTitle()));
//                            $instituteLevel = $institute->getField('Level');
//                            if(!empty($instituteLevel)) {
//                                $instituteNode->addAttribute('type', XMLHelper::encodeXMLString($instituteLevel));
//                            }
//                        }
//                    }
//                }
//            }
        }
        return $parentNode;
    }

    private static function addNamePartDepartmentFromLowerInstitute(RepoItem $repoItem, $context, $parentNode) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaFieldForMetaField) {
            /** @var RepoItemMetaFieldValue $answer */
            $answer = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($answer) {
                if ($answer->Institute() && $answer->Institute()->exists()) {
                    $discipline = $answer->Institute();
                    $department = $discipline->getParentDepartment();
                    if ($department) {
                        $contentItem = $department->getTitle();
                        $node = self::addNode($parentNode, $contentItem, $context);
                        $node->addAttribute('type', 'department');
                        return $node;
                    }
                }

            }
        }
        return $parentNode;
    }

    private static function getResourceLink(RepoItem $repoItem, $context, $parentNode) {
        $mimeType = 'text/html';
        $resourceUrl = $repoItem->getPublicURL();
        $resourceNode = self::addNode($parentNode, '', $context);
        $resourceNode->addAttribute('mimeType', XMLHelper::encodeXMLString($mimeType));
        $resourceNode->addAttribute('ref', XMLHelper::encodeXMLString($resourceUrl));
        return $resourceNode;
    }

    private static function mapUrn(RepoItem $repoItem, ProtocolNode $context) {
        $repoItemMetaFieldForMetaField = self::getRepoItemMetaField($repoItem, $context);

        if ($repoItemMetaFieldForMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($repoItemMetaFieldValue) {
                return $context->mapAnswer($repoItemMetaFieldValue->InstituteUuid);
            }
        }
        return null;
    }

    private static function escapeVcardString($value) {
        return addcslashes($value, "\\\n,:;");
    }

    private static function getJSONAlias(RepoItem $repoItem, ProtocolNode $context) {
        $contentItem = null;
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            if ($repoItemMetaFieldValue && $person = $repoItemMetaFieldValue->Person()) {
                if ($person->exists()) {
                    if ($repoItem->Alias) {
                        $contentItem = $repoItem->Alias;
                    } else {
                        $contentItem = $person->getFullName();
                    }
                }
            }
        }
        return $contentItem;
    }

    private static function getJSONRootOrganisation(RepoItem $repoItem, ProtocolNode $context) {
        $rootInstitute = $repoItem->Institute->getRootInstitute();

        return [
            'id' => $rootInstitute->Uuid,
            'name' => $rootInstitute->Title,
            'type' => $rootInstitute->Level
        ];
    }

    private static function getEtag(RepoItem $repoItem, ProtocolNode $context){
        $repoItemMetaField = self::getRepoItemMetaField($repoItem, $context);
        if ($repoItemMetaField) {
            $repoItemMetaFieldValue = $repoItemMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
            /** @var RepoItemFile $repoItemFile */
            if ($repoItemMetaFieldValue && $repoItemFile = $repoItemMetaFieldValue->RepoItemFile()) {
                if ($repoItemFile->exists()) {
                    return $repoItemFile->ETag;
                }
            }
        }
    }
}