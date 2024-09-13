<?php

namespace SurfSharekit\Models;

use ExternalRepoItemChannelJsonApiDescription;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SimpleXMLElement;
use SurfSharekit\Helper\VirtualMetaField;
use SurfSharekit\Models\Helper\XMLHelper;

/**
 * Class ProtocolNode
 * @package SurfSharekit\Models
 * @method HasManyList Mapping
 * @method HasManyList NodeAttributes
 * @method HasManyList ChildrenNodes
 * @method MetaField MetaField
 * DataObject representing a single value added to a @see Protocol
 */
class ProtocolNode extends DataObject {
    private static $table_name = 'SurfSharekit_ProtocolNode';
    private static $default_sort = 'SortOrder ASC';

    private static $db = [
        'NodeTitle' => 'Varchar(255)',
        'ArrayNotation' => 'Int(0)',
        'HardcodedValue' => 'Varchar(255)',
        'VirtualMetaField' => "Enum('dii:Identifier,dcterms:modified,mods:namePart:family,mods:namePart:given,mods:displayForm,lom:languageString,lom:Identifier,lom:encaseInStringNode,vCard,lom:technical,didl:resource:file,didl:resource:link,mods:genre:thesis,hbo:namePart:departmentFromLowerInstitute,dai:identifierExtension,mods:name:personal,lom:contribute:validator,lom:relation:description,oai:Identifier,mods:identifier:isbn,lom:classification:taxonomy,hbo:namePart,didl:resource,json:modified,json:partOf,json:hasParts,mods:dateIssued,json:dateIssued,json:personEmail,json:alias,json:resourceMimeType,json:taxonomy,json:diiIdentifier,json:vocabulary,orcid:identifier,hogeschool:identifier,json:hogeschoolIdentifier,json:orcid,json:isni,json:dai,json:validator,csv:personalIdentifiers,json:created,json:rootOrganisation,json:etag,csv:created,csv:creator,dai:identifier,isni:identifier,localAuthor:identifier,lom:rightsofusage',null)",
        'Property' => 'Varchar(255)',
        'NamespaceURI' => 'Varchar(255)',
        'SortOrder' => 'Int(0)',
        'ParentProtocolID' => 'Int(0)'
        //   , 'HideEmptyNode' => 'Int(0)' // we gebruiken een boolean om aan te geven of een lege node ook moet worden getoond
        //, UseMapping /* MB:We kunnen dit gebruiken om per node bij te houden of mapping lookup nodig is of niet */
    ];

    private static $has_one = [
        'Protocol' => Protocol::class,
        'MetaField' => MetaField::class,
        'SubMetaField' => MetaField::class,
        'ParentNode' => ProtocolNode::class
    ];

    private static $has_many = [
        'ChildrenNodes' => ProtocolNode::class,
        'NodeAttributes' => ProtocolNodeAttribute::class,
        'NodeNamespaces' => ProtocolNodeNamespace::class,
        'Mapping' => ProtocolNodeMapping::class
    ];

    private static $summary_fields = [
        'Title' => 'Protocol Node Path',
        'NodeTitle' => 'Protocol Node title'
    ];

    private static $searchable_fields = [
        'NodeTitle' => [
            'title' => 'Protocol Node title',
            'filter' => 'PartialMatchFilter'
        ]
    ];

    /**
     * @var $accessControlFilter = function that takes a repoitem and returns either the repoitem or null if it passes the given access control filter
     */
    public static $accessControlFilter;

    public function __construct($record = null, $isSingleton = false, $queryParams = []) {
        parent::__construct($record, $isSingleton, $queryParams);
        //by default no acces control filter is given
        static::$accessControlFilter = function ($repoItem) {
            if ($repoItem->RepoType == "RepoItemRepoItemFile") {
                if ($repoItem->Status == "Embargo") {
                    return null;
                }

                if ($repoItem->AccessRight == RepoItemFile::CLOSED_ACCESS) {
                    return null;
                }
            }

            return $repoItem;
        };
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->isInDB()) {
            if ($this->ParentNode()->exists()) {
                $parentNode = $this->ParentNode();
                while (true) {
                    if ($parentNode->ProtocolID == 0) {
                        $parentNode = $parentNode->ParentNode();
                    } else {
                        $this->ParentProtocolID = $parentNode->ProtocolID;
                        break;
                    }
                }
            } else {
                $this->ParentProtocolID = $this->ProtocolID;
            }
        }
    }

    public function getTitle() {
        if ($this->ParentNode && $this->ParentNode->exists()) {
            return $this->ParentNode->Title . ' -> ' . $this->NodeTitle;
        } else if ($this->Protocol && $this->Protocol->exists()) {
            return $this->Protocol->Title . ' -> ' . $this->NodeTitle;
        }
        return $this->NodeTitle;
    }

    public function addVirtualMetaFieldNodeForRepoItem(RepoItem $repoItem, $parentNode) {
        if ($this->isInDB() && $type = $this->getField('VirtualMetaField')) {
            return VirtualMetaField::addNodeForType($type, $repoItem, $this, $parentNode);
        }
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        /** @var DropdownField $virtualMetaField */
        $virtualMetaField = $fields->dataFieldByName('VirtualMetaField');
        $virtualMetaField->setEmptyString('Select a vritual metafield');
        $virtualMetaField->setHasEmptyDefault(true);

        if ($this->isInDB()) {
            /** @var Grid
             * Field $protocolNodesGridField
             */
            $protocolNodesGridField = $fields->dataFieldByName('ChildrenNodes');
            $protocolNodesGridFieldConfig = $protocolNodesGridField->getConfig();
            $protocolNodesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            /** @var GridField $nodeAttributesGridField */
            $nodeAttributesGridField = $fields->dataFieldByName('NodeAttributes');
            $nodeAttributesGridFieldConfig = $nodeAttributesGridField->getConfig();
            $nodeAttributesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            /** @var GridField $nodeNamespacesGridField */
            $nodeNamespacesGridField = $fields->dataFieldByName('NodeNamespaces');
            $nodeNamespacesGridFieldConfig = $nodeNamespacesGridField->getConfig();
            $nodeNamespacesGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            /** @var GridField $mappingGridField */
            $mappingGridField = $fields->dataFieldByName('Mapping');
            $mappingGridFieldConfig = $mappingGridField->getConfig();
            $mappingGridFieldConfig->removeComponentsByType([new GridFieldAddExistingAutocompleter(), new GridFieldArchiveAction(), new GridFieldDeleteAction()]);

            $parentNodeField = HiddenField::create('ParentNodeID', 'ParentNodeID');
            $fields->replaceField('ParentNodeID', $parentNodeField);
        }
        /** @var TextField $propertyField */
        $propertyField = $fields->dataFieldByName('Property');
        $propertyField->setDescription('Use properties from object summaries : title, level for Institute, etc.');

        $fields->removeByName('ParentProtocolID');

        $fields = MetaField::ensureDropdownField($this, $fields, 'MetaFieldID', 'MetaField', true, 'Select a metafield');
        $fields = MetaField::ensureDropdownField($this, $fields, 'SubMetaFieldID', 'SubMetaField', true, 'If needed, select a metafield from a subrepoitem');

        return $fields;
    }

    public function describeJSONValueObjUsing($valueObj, $repoItem) {
        // TODO, support childnodes
        if ($this->Mapping()->count()) {
            $mapValues = true;
        } else {
            $mapValues = false;
        }
        $nodeAttributes = $this->NodeAttributes()->map('Title', 'Value')->toArray();
        if (!is_null($this->getField('Property'))) {
            // property must exist in summary
            $property = $this->getField('Property');
            if (array_key_exists($property, $valueObj)) {
                $value = $valueObj[$property];
                if ($mapValues) {
                    $contentItem = $this->mapAnswer($value);
                } else {
                    $contentItem = $value;
                }
                return $this->jsonAttrsValue($nodeAttributes, $contentItem);
            }
        } else if (!is_null($this->getField('VirtualMetaField'))) {
            $contentItem = $this->addVirtualMetaFieldNodeForRepoItem($repoItem, null);
            if ($this->ArrayNotation) {
                $contentItem = [$contentItem];
            }
            return $this->jsonAttrsValue($nodeAttributes, $contentItem);
        } else {
            $valueItem = (is_array($valueObj) && count($valueObj)) ? array_values($valueObj)[0] : $valueObj;
            $value = $this->getField('HardcodedValue') ?: $valueItem;
            if ($mapValues) {
                $contentItem = $this->mapAnswer($value);
            } else {
                $contentItem = $value;
            }
            return $this->jsonAttrsValue($nodeAttributes, $contentItem);
        }
    }

    public function describeJSONUsing(RepoItem $repoItem) {
        $content = null;
        $nodeAttributes = $this->NodeAttributes()->map('Title', 'Value')->toArray();
        if ($this->ChildrenNodes()->count() > 0 && $this->MetaField()->exists()) {
            /** @var RepoItemMetaField $repoItemMetaFieldForMetaField */
            $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
                ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
                ->filter('MetaFieldID', $this->MetaFieldID)->first();

            if ($repoItemMetaFieldForMetaField) {
                $repoItems = [];
                $values = [];
                /** @var RepoItemMetaFieldValue $repoItemMetaFieldFormMetaFieldValue */
                foreach ($repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $repoItemMetaFieldFormMetaFieldValue) {
                    // case value is repoitem
                    $subRepoItem = $repoItemMetaFieldFormMetaFieldValue->RepoItem();
                    //If filtering on acces control of a repoitemrepoitemfile, skip if filtered
                    if ($subRepoItem->RepoType == 'RepoItemRepoItemFile' && $accessControlFilter = ExternalRepoItemChannelJsonApiDescription::$accessControlFilter) {
                        if (!$accessControlFilter($subRepoItem)) {
                            continue;
                        }
                    }
                    if ($subRepoItem->exists()) {
                        $repoItems[] = $subRepoItem;
                    } else {
                        $values[] = $repoItemMetaFieldFormMetaFieldValue->getRelatedObjectSummary();
                    }
                }

                if (is_array($repoItems) && count($repoItems)) {
                    $content = [];
                    foreach ($repoItems as $subRepoItem) {
                        $nodeContent = [];
                        if (!is_null($this->getField('VirtualMetaField'))) {
                            // TODO add VirtualMetaField
                        }

                        if ($this->ChildrenNodes()->count() > 0) {
                            /** @var ProtocolNode $childNode */
                            foreach ($this->ChildrenNodes() as $childNode) {
                                $nodeContent[$childNode->NodeTitle] = $childNode->describeJSONUsing($subRepoItem);
                            }
                            $content[] = $nodeContent;
                        }
                    }
                }
                if (is_array($values) && count($values)) {
                    $content = [];
                    foreach ($values as $value) {
                        $nodeContent = [];
                        /** @var ProtocolNode $childNode */
                        foreach ($this->ChildrenNodes() as $childNode) {
                            $nodeContent[$childNode->NodeTitle] = $childNode->describeJSONValueObjUsing($value, $repoItem);
                        }
                        $content[] = $nodeContent;
                    }
                    if ((!$this->ArrayNotation) && (count($content) == 1)) {
                        $content = $content[0];
                    }
                }
            }
        } else if ($this->ChildrenNodes()->count() > 0) {
            if ($this->ChildrenNodes()->count() > 0) {
                $content = [];
                /** @var ProtocolNode $childNode */
                foreach ($this->ChildrenNodes() as $childNode) {
                    $content[$childNode->NodeTitle] = $childNode->describeJSONUsing($repoItem);
                }
            }
        } else if (!is_null($this->getField('VirtualMetaField'))) {
            $contentItem = $this->addVirtualMetaFieldNodeForRepoItem($repoItem, null);
            if (!$this->ArrayNotation) {

            } else {
                if (is_null($contentItem)) {
                    $contentItem = [];
                } else {
                    $contentItem = [$contentItem];
                }
            }
            $content = $this->jsonAttrsValue($nodeAttributes, $contentItem);
        } else if ($this->MetaField->exists()) {
            /** @var RepoItemMetaField $repoItemMetaFieldForMetaField */
            $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
                ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
                ->filter('MetaFieldID', $this->MetaFieldID)->first();

            if ($repoItemMetaFieldForMetaField) {
                if ($this->Mapping()->count()) {
                    $mapValues = true;
                } else {
                    $mapValues = false;
                }
                $isEncoded = $repoItemMetaFieldForMetaField->MetaField()->MetaFieldType()->JSONEncodedStorage;
                // TODO if protocolNode has attribute selection, use getRelatedObjectSummary
                if ($property = $this->getField('Property')) {
                    // node has property, so we need an object instead of values
                    $answer = $repoItemMetaFieldForMetaField->getObjValues();
                } else {
                    $answer = $repoItemMetaFieldForMetaField->Values;
                }
                if ($isEncoded) {
                    $answer = json_decode($answer);
                }
                if (is_array($answer)) {
                    $contentParts = [];
                    foreach ($answer as $answerPart) {
                        if ($property && is_array($answerPart) && array_key_exists($property, $answerPart)) {
                            $answerPart = $answerPart[$property];
                        }
                        if ($mapValues) {
                            $contentItem = $this->mapAnswer($answerPart);
                        } else {
                            $contentItem = $answerPart;
                        }
                        $contentParts[] = $this->jsonAttrsValue($nodeAttributes, $contentItem);
                    }
                    if (!$this->ArrayNotation && count($contentParts) == 1) {
                        $contentParts = $contentParts[0];
                    }
                    $content = $this->jsonAttrsValue($nodeAttributes, $contentParts);;
                } else {

                    if ($mapValues) {
                        $contentItem = $this->mapAnswer($answer);
                    } else {
                        $contentItem = $answer;
                    }
                    if (!$this->ArrayNotation) {

                    } else {
                        if (is_null($contentItem)) {
                            $contentItem = [];
                        } else {
                            $contentItem = [$contentItem];
                        }
                    }
                    $content = $this->jsonAttrsValue($nodeAttributes, $contentItem);
                }
            } else {
                if ($this->ArrayNotation) {
                    $content = [];
                } else {
                    $content = null;
                }
            }
        } else {
            $content = null;
        }
        return $content;

    }

    /**
     * @param RepoItem $repoItem
     * @param $format
     * @return array|mixed|string|null
     * Method to describe part of a RepoItem in a $format based on the values of this ProtocolNode
     * NOT WORKING / DEPRECATED
     */
    public function describeUsing(RepoItem $repoItem, $format) {
        if ($format == 'json') {
            return $this->describeJSONUsing($repoItem);
        }
        $content = '';
        if ($format == 'json') {
            if ($this->ChildrenNodes()->count() == 0) {
                $content = null;
            } else {
                $content = [];
            }
        }

        $nodeAttributes = $this->NodeAttributes()->map('Title', 'Value')->toArray();
        if (!is_null($this->getField('VirtualMetaField'))) {
            // WIP not workin
            $contentItem = $this->addVirtualMetaFieldNodeForRepoItem($repoItem, null);
            if (!$this->ArrayNotation) {

            } else {
                $contentItem = [$contentItem];
            }
            if ($format == 'json') {
                $content = $contentItem;
            } else {
                $content .= $this->xmlAttrsValue($this->NodeTitle, $nodeAttributes, $contentItem);
            }
        } elseif ($this->MetaField->exists()) {
            if ($this->Mapping()->count()) {
                $mapValues = true;
            } else {
                $mapValues = false;
            }

            /** @var RepoItemMetaField $repoItemMetaField */
            $repoItemMetaField = $repoItem->RepoItemMetaFields()->filter('MetaFieldID', $this->MetaFieldID)->first();

            if (!is_null($repoItemMetaField) && $repoItemMetaField->exists()) {
                // TODO JSON encoded value?
                $isJSONEncodedValue = $repoItemMetaField->MetaField()->MetaFieldType()->JSONEncodedStorage;
                // $answer is either value/option, array of values or array of option
                $answer = $repoItemMetaField->Values;
                if ($isJSONEncodedValue) {
                    $answer = json_decode($answer);
                }

                // list of options, add to array
                if (is_array($answer)) {
                    if ($format == 'json') {
                        if ($mapValues) {
                            $contentParts = [];
                            foreach ($answer as $answerPart) {

                                $contentParts[] = $this->jsonAttrsValue($nodeAttributes, $this->mapAnswer($answerPart));
                            }
                            $content = $contentParts;
                        } else {
                            if (!$this->ArrayNotation) {
                                $content = $this->jsonAttrsValue($nodeAttributes, $answer);
                            } else {
                                $content = [$this->jsonAttrsValue($nodeAttributes, $answer)];
                            }
                        }
                    } else {
                        foreach ($answer as $answerPart) {
                            if ($mapValues) {
                                $contentPart = $this->mapAnswer($answerPart);
                            } else {
                                $contentPart = $answerPart;
                            }
                            $content .= $this->xmlAttrsValue($this->NodeTitle, $nodeAttributes, $contentPart);
                        }
                    }
                } else {
                    if ($mapValues) {
                        $contentItem = $this->mapAnswer($answer);
                    } else {
                        $contentItem = $answer;
                    }

                    if ($format == 'json') {
                        if (!$this->ArrayNotation) {
                            $content = $this->jsonAttrsValue($nodeAttributes, $contentItem);
                        } else {
                            $content = [$this->jsonAttrsValue($nodeAttributes, $contentItem)];
                        }
                    } else {
                        if (!$this->ArrayNotation) {

                        } else {
                            $contentItem = [$contentItem];
                        }
                        $content .= $this->xmlAttrsValue($this->NodeTitle, $nodeAttributes, $contentItem);
                    }
                }
            }
        } else {
            $contentItem = $this->HardcodedValue;
            if (!$this->ArrayNotation) {

            } else {
                $contentItem = [$contentItem];
            }
            if ($format == 'json') {
                $content = $contentItem;
            } else {
                $content .= $this->xmlAttrsValue($this->NodeTitle, $nodeAttributes, $contentItem);
            }
        }

        if ($this->ChildrenNodes()->count() > 0) {
            $content = [];
        }
        foreach ($this->ChildrenNodes() as $childNode) {
            if ($format == 'json') {
                $content[$childNode->NodeTitle] = $childNode->describeUsing($repoItem, $format);
            } else {
                $content .= $childNode->describeUsing($repoItem, $format);
            }
        }

        return $content;
    }

    public function addAttributesFunction(SimpleXMLElement $node) {
        $nodeNamespaces = $this->NodeNamespaces()->map('Title', 'Value')->toArray();
        foreach ($nodeNamespaces as $attrKey => $attrValue) {
            $node->addAttribute($attrKey . ':_', '', $attrValue);
            unset($node->attributes($attrKey, TRUE)[0]);
        }

        $nodeAttributes = $this->NodeAttributes();
        foreach ($nodeAttributes as $nodeAttribute) {
            $namespace = empty($nodeAttribute->NamespaceURI) ? null : $nodeAttribute->NamespaceURI;
            $node->addAttribute($nodeAttribute->Title, XMLHelper::encodeXMLString($nodeAttribute->Value), $namespace); //' :'allow you to add namespaced attributes because simpleXml only removes the first namespace
        }
    }

    /**
     * @param RepoItem $repoItem
     * @param SimpleXMLElement $parentNode
     * @return array|mixed|string
     * Method to add this protocol as a simpleXML Element to a parentNode
     */
    public function addTo(RepoItem $repoItem, SimpleXMLElement $parentNode) {
        $namespace = empty($this->NamespaceURI) ? null : $this->NamespaceURI;
        if ($this->ChildrenNodes()->count() > 0 && $this->MetaField()->exists()) {
            /** @var RepoItemMetaField $repoItemMetaFieldForMetaField */
            $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
                ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
                ->filter('MetaFieldID', $this->MetaFieldID)->first();

            if ($repoItemMetaFieldForMetaField) {
                $repoItems = [];
                $values = [];
                /** @var RepoItemMetaFieldValue $repoItemMetaFieldFormMetaFieldValue */
                foreach ($repoItemMetaFieldForMetaField->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0]) as $repoItemMetaFieldFormMetaFieldValue) {

                    // case value is repoitem
                    $subRepoItem = $repoItemMetaFieldFormMetaFieldValue->RepoItem();
                    //If filtering on acces control of a repoitemrepoitemfile, skip if filtered
                    if ($subRepoItem->RepoType == 'RepoItemRepoItemFile' && $accessControlFilter = ProtocolNode::$accessControlFilter) {
                        if (!$accessControlFilter($subRepoItem)) {
                            continue;
                        }
                    }
                    if ($subRepoItem->exists()) {
                        $repoItems[] = $subRepoItem;
                    } else {
                        $values[] = $repoItemMetaFieldFormMetaFieldValue->getSummaryFieldValue();
                    }
                }

                if (is_array($repoItems) && count($repoItems)) {
                    foreach ($repoItems as $subRepoItem) {
                        if (!is_null($this->getField('VirtualMetaField'))) {
                            $node = $this->addVirtualMetaFieldNodeForRepoItem($subRepoItem, $parentNode);
                            $this->addAttributesFunction($node);
                        } elseif (!empty($this->NodeTitle)) {
                            $node = $parentNode->addChild($this->NodeTitle, '', $namespace);  //' :'allow you to add namespaced attributes because simpleXml only removes the first namespace
                            if (!is_null($node)) {
                                $this->addAttributesFunction($node);
                            }
                        } else {
                            $node = $parentNode;
                        }
                        foreach ($this->ChildrenNodes() as $childNode) {
                            $childNode->addTo($subRepoItem, $node);
                        }
                    }
                }
                if (is_array($values) && count($values)) {
                    if ($this->Mapping()->count()) {
                        $mapValues = true;
                    } else {
                        $mapValues = false;
                    }
                    foreach ($values as $value) {
                        if ($mv = $mapValues ? $this->mapAnswer($value) : $value) {
                            $node = $parentNode->addChild($this->NodeTitle, '', $namespace);  //' :'allow you to add namespaced attributes because simpleXml only removes the first namespace
                            if (!is_null($node)) {
                                $this->addAttributesFunction($node);
                            }
                            foreach ($this->ChildrenNodes() as $childNode) {
                                $this->addValueToChildNode($childNode, $mv, $node);
                            }
                        }
                    }
                }
            }
        } else if ($this->ChildrenNodes()->count() > 0) {
            $node = $parentNode->addChild($this->NodeTitle, '', $namespace);  //' :'allow you to add namespaced attributes because simpleXml only removes the first namespace
            if (!is_null($node)) {
                $this->addAttributesFunction($node);
            }
            foreach ($this->ChildrenNodes() as $childNode) {
                $childNode->addTo($repoItem, $node);
            }
        } else if (!is_null($this->getField('VirtualMetaField'))) {
            $node = $this->addVirtualMetaFieldNodeForRepoItem($repoItem, $parentNode);
            if (!is_null($node)) {
                $this->addAttributesFunction($node);
            }
        } else if ($this->MetaField->exists()) {
            $repoItemMetaFieldForMetaField = $repoItem->RepoItemMetaFields()
                ->leftJoin('SurfSharekit_MetaField', 'SurfSharekit_RepoItemMetaField.MetaFieldID = SurfSharekit_MetaField.ID')
                ->filter('MetaFieldID', $this->MetaFieldID)->first();

            if ($repoItemMetaFieldForMetaField) {
                if ($this->Mapping()->count()) {
                    $mapValues = true;
                } else {
                    $mapValues = false;
                }
                $isEncoded = $repoItemMetaFieldForMetaField->MetaField()->MetaFieldType()->JSONEncodedStorage;
                $answer = $repoItemMetaFieldForMetaField->Values;
                if ($isEncoded) {
                    $answer = json_decode($answer);
                }
                if (is_array($answer)) {
                    foreach ($answer as $answerPart) {
                        if ($mapValues) {
                            $contentItem = $this->mapAnswer($answerPart);
                        } else {
                            $contentItem = $answerPart;
                        }
                        $node = $parentNode->addChild($this->NodeTitle, XMLHelper::encodeXMLString($contentItem), $namespace);
                        if (!is_null($node)) {
                            $this->addAttributesFunction($node);
                        }
                    }
                } else {
                    if ($mapValues) {
                        $contentItem = $this->mapAnswer($answer);
                    } else {
                        $contentItem = $answer;
                    }
                    $node = $parentNode->addChild($this->NodeTitle, XMLHelper::encodeXMLString($contentItem), $namespace);
                    if (!is_null($node)) {
                        $this->addAttributesFunction($node);
                    }
                }
            }
        } else {
            $node = $parentNode->addChild($this->NodeTitle, XMLHelper::encodeXMLString($this->HardcodedValue), $namespace);
            if (!is_null($node)) {
                $this->addAttributesFunction($node);
            }
        }
    }

    public function addValueToChildNode($childNode, $value, SimpleXMLElement $parentNode) {
        $namespace = empty($childNode->NamespaceURI) ? null : $childNode->NamespaceURI;
        $value = $childNode->HardcodedValue ?: $value;
        $node = $parentNode->addChild($childNode->NodeTitle, XMLHelper::encodeXMLString($value), $namespace);
        $childNode->addAttributesFunction($node);
    }

    public function mapAnswer($answer) {
        /** @var ProtocolNodeMapping $targetMapping */
        $targetMapping = $this->Mapping()->filter(['SourceValue' => $answer])->first();
        if (!is_null($targetMapping) && $targetMapping->exists()) {
            return $targetMapping->TargetValue;
        } else {
            // TODO, log somewhere that target cannot be mapped!
        }
        return null; // return null if cannot be mapped TODO, return default if cannot be mapped
    }

    private function jsonAttrsValue($attrs, $value) {
        if (count($attrs)) {
            return ['@' => $attrs, '#' => $value];
        } else {
            return $value;
        }

    }

    private function xmlAttrsValue($nodeTitle, $attrs, $value) {
        $attrText = '';
        if (count($attrs)) {
            foreach ($attrs as $attrKey => $attrValue) {
                $attrText .= '$attrKey="' . XMLHelper::encodeXMLString($attrValue) . '" ';
            }
            $attrText = trim($attrText);
        }
        // will convert " to &quot; in addition to &, < and >.
        $xmlContent = XMLHelper::encodeXMLString($value);
        return "<$nodeTitle $attrText>$xmlContent</$nodeTitle>";
    }

    public function clearParentProtocol() {
        $this->ParentProtocolID = 0;
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

    public function canDelete($member = null) {
        return true;
    }
}