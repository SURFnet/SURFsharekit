<?php


namespace SurfSharekit\Models\Helper;


use SimpleXMLElement;

class XMLHelper
{
    static function simplexml_import_xml(SimpleXMLElement $parent, $xml, $before = false, $ns = '')
    {
        $xml = (string)$xml;

        // check if there is something to add
        if ($nodata = !strlen($xml) or $parent[0] == NULL) {
            return $nodata;
        }

        // add the XML
        $node     = dom_import_simplexml($parent);
        $fragment = $node->ownerDocument->createDocumentFragment();
        Logger::debugLog($fragment->namespaceURI);
        $fragment->appendXML($xml);

        if ($before) {
            return (bool)$node->parentNode->insertBefore($fragment, $node);
        }

        return (bool)$node->appendChild($fragment);
    }

    /**
     * Insert SimpleXMLElement into SimpleXMLElement
     *
     * @param SimpleXMLElement $parent
     * @param SimpleXMLElement $child
     * @param bool $before
     * @return bool SimpleXMLElement added
     */
    static function simplexml_import_simplexml(SimpleXMLElement $parent, SimpleXMLElement $child, $before = false)
    {
        // check if there is something to add
        if ($child[0] == NULL) {
            return true;
        }

        // if it is a list of SimpleXMLElements default to the first one
        $child = $child[0];

        // insert attribute
        if ($child->xpath('.') != array($child)) {
            $parent[$child->getName()] = (string)$child;
            return true;
        }

        $xml = $child->asXML();

        // remove the XML declaration on document elements
        if ($child->xpath('/*') == array($child)) {
            $pos = strpos($xml, "\n");
            $xml = substr($xml, $pos + 1);
        }

        return self::simplexml_import_xml($parent, $xml, $before);
    }

    static public function encodeXMLString($value) {

        return self::utf8_for_xml(htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }

    static public function utf8_for_xml($string)
    {
        return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    }
}