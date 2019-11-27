<?php

namespace SzamlaAgent;

/**
 * SimpleXMLElement kiterjesztése
 *
 * @package SzamlaAgent
 */
class SimpleXMLExtended extends \SimpleXMLElement {

    /**
     * @param  \SimpleXMLElement $node
     * @param  string            $value
     * @return void
     */
    public function addCDataToNode(\SimpleXMLElement $node, $value = '') {
        if ($domElement = dom_import_simplexml($node)) {
            $domOwner = $domElement->ownerDocument;
            $domElement->appendChild($domOwner->createCDATASection($value));
        }
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return \SimpleXMLElement
     */
    public function addChildWithCData($name = '', $value = '') {
        $newChild = parent::addChild($name);
        if (Util::isNotBlank($value)) {
            $this->addCDataToNode($newChild, $value);
        }
        return $newChild;
    }

    /**
     * @param  string $value
     * @return void
     */
    public function addCData($value = '')  {
        $this->addCDataToNode($this, $value);
    }

    /**
     * @param  string $name
     * @param  string $value [optional]
     * @param  string $namespace [optional]
     *
     * @return \SimpleXMLElement|SimpleXMLExtended
     */
    public function addChild($name, $value = null, $namespace = null) {
        return parent::addChild($name, $value, $namespace);
    }
}