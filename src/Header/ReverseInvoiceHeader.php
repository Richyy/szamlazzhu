<?php

namespace SzamlaAgent\Header;

use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\SzamlaAgentUtil;
use SzamlaAgent\Request\Request;

/**
 * Sztornó számla fejléc
 *
 * @package SzamlaAgent\Header
 */
class ReverseInvoiceHeader extends InvoiceHeader {

    /**
     * XML-ben kötelezően kitöltendő mezők
     *
     * @var array
     */
    protected $requiredFields = ['invoiceNumber'];

    /**
     * @param int $type
     *
     * @throws SzamlaAgentException
     */
    function __construct($type = Invoice::INVOICE_TYPE_P_INVOICE) {
        parent::__construct($type);
        $this->setReserveInvoice(true);
    }

    /**
     * Ellenőrizzük a mező típusát
     *
     * @param $field
     * @param $value
     *
     * @return string
     * @throws SzamlaAgentException
     */
    public function checkField($field, $value) {
        if (property_exists(get_parent_class($this), $field) || property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'issueDate':
                case 'fulfillment':
                    SzamlaAgentUtil::checkDateField($field, $value, $required, __CLASS__);
                    break;
                case 'invoiceNumber':
                    SzamlaAgentUtil::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }
        return $value;
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

            $data["szamlaszam"] = $this->getInvoiceNumber();
            if (!empty($this->getIssueDate()))   $data['keltDatum'] = $this->getIssueDate();
            if (!empty($this->getFulfillment())) $data['teljesitesDatum'] = $this->getFulfillment();
            $data['tipus'] = Document::DOCUMENT_TYPE_REVERSE_INVOICE_CODE;

            $this->checkFields();

            return $data;
        } catch (SzamlaAgentException $e) {
            throw $e;
        }
    }
}