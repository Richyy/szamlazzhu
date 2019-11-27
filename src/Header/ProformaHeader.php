<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\Request\Request;
use SzamlaAgent\Util;

/**
 * Díjbekérő fejléc
 *
 * @package SzamlaAgent\Header
 */
class ProformaHeader extends InvoiceHeader {

    /**
     * XML-ben kötelezően kitöltendő mezők
     *
     * @var array
     */
    protected $requiredFields = [];

    /**
     * @throws \SzamlaAgent\SzamlaAgentException
     */
    function __construct() {
        parent::__construct();
        $this->setProforma(true);
        $this->setPaid(false);
    }

    /**
     * Összeállítja a bizonylat elkészítéséhez szükséges XML fejléc adatokat
     *
     * Csak azokat az XML mezőket adjuk hozzá, amelyek kötelezőek,
     * illetve amelyek opcionálisak, de ki vannak töltve.
     *
     * @param Request $request
     *
     * @return array
     * @throws SzamlaAgentException
     */
    public function buildXmlData(Request $request) {
        try {
            if (empty($request)) {
                throw new SzamlaAgentException(SzamlaAgentException::XML_DATA_NOT_AVAILABLE);
            }

            $data = [];
            switch ($request->getXmlName()) {
                case $request::XML_SCHEMA_DELETE_PROFORMA:
                    if (Util::isNotBlank($this->getInvoiceNumber())) $data["szamlaszam"] = $this->getInvoiceNumber();
                    if (Util::isNotBlank($this->getOrderNumber())) $data["rendelesszam"] = $this->getOrderNumber();
                    $this->checkFields();
                    break;
                default:
                    $data = parent::buildXmlData($request);
            }
            return $data;
        } catch (SzamlaAgentException $e) {
            throw $e;
        }
    }
}