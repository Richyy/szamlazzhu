<?php

namespace SzamlaAgent\CreditNote;

use SzamlaAgent\Document\Document;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\Util;

/**
 * Számla jóváírás
 *
 * @package SzamlaAgent\CreditNote
 */
class InvoiceCreditNote extends CreditNote {

    /**
     * Jóváírás dátuma
     *
     * @var string
     */
    protected $date;

    /**
     * Kötelezően kitöltendő mezők
     *
     * @var array
     */
    protected $requiredFields = ['date', 'paymentMode', 'amount'];

    /**
     * Jóváírás létrehozása
     *
     * @param string $date        jóváírás dátuma
     * @param string $paymentMode jóváírás jogcíme (fizetési módja)
     * @param double $amount      jóváírás összege
     * @param string $description jóváírás leírása
     */
    function __construct($date, $amount, $paymentMode = Document::PAYMENT_METHOD_TRANSFER, $description = '') {
        parent::__construct($paymentMode, $amount, $description);
        $this->setDate($date);
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
    protected function checkField($field, $value) {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'date':
                    Util::checkDateField($field, $value, $required, __CLASS__);
                    break;
                case 'amount':
                    Util::checkDoubleField($field, $value, $required, __CLASS__);
                    break;
                case 'paymentMode':
                case 'description':
                    Util::checkStrField($field, $value, $required, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    /**
     * Ellenőrizzük a tulajdonságokat
     *
     * @throws SzamlaAgentException
     */
    protected function checkFields() {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            $this->checkField($field, $value);
        }
    }

    /**
     * @return array
     * @throws SzamlaAgentException
     */
    public function buildXmlData() {
        $data = [];
        $this->checkFields();

        if (Util::isNotBlank($this->getDate()))        $data['datum']  = $this->getDate();
        if (Util::isNotBlank($this->getPaymentMode())) $data['jogcim'] = $this->getPaymentMode();
        if (Util::isNotNull($this->getAmount()))       $data['osszeg'] = Util::doubleFormat($this->getAmount());
        if (Util::isNotBlank($this->getDescription())) $data['leiras'] = $this->getDescription();

        return $data;
    }

    /**
     * @return string
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate($date) {
        $this->date = $date;
    }
 }