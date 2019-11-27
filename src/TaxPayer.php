<?php

namespace SzamlaAgent;

use SzamlaAgent\Request\Request;

/**
 * Adózó
 *
 * @package SzamlaAgent
 */
class TaxPayer {

    /**
     * Társas vállalkozás (Bt., Kft., zRt.)
     */
    const TAXPAYER_JOINT_VENTURE = 5;

    /**
     * Egyéni vállalkozó
     */
    const TAXPAYER_INDIVIDUAL_BUSINESS = 4;

    /**
     * Adószámos magánszemély
     */
    const TAXPAYER_PRIVATE_INDIVIDUAL_WITH_TAXNUMBER = 3;

    /**
     * Adószámos egyéb szervezet
     */
    const TAXPAYER_OTHER_ORGANIZATION_WITH_TAXNUMBER = 2;

    /**
     * Van adószáma
     */
    const TAXPAYER_HAS_TAXNUMBER = 1;

    /**
     * Nem tudjuk, hogy adóalany-e
     */
    const TAXPAYER_WE_DONT_KNOW = 0;

    /**
     * Nincs adószáma
     */
    const TAXPAYER_NO_TAXNUMBER = -1;

    /**
     * Magánszemély
     */
    const TAXPAYER_PRIVATE_INDIVIDUAL = -2;

    /**
     * Adószám nélküli egyéb szervezet
     */
    const TAXPAYER_OTHER_ORGANIZATION_WITHOUT_TAXNUMBER = -3;

    /**
     * Törzsszám
     *
     * @var string
     */
    protected $taxPayerId;

    /**
     * Az adózó milyen típusú adóalany
     *
     * @var int
     */
    protected $taxPayerType;

    /**
     * Kötelezően kitöltendő mezők
     *
     * @var array
     */
    protected $requiredFields = ['taxPayerId'];

    /**
     * Adózó (adóalany) példányosítás
     *
     * @param string $taxpayerId
     * @param int    $taxPayerType
     */
    function __construct($taxpayerId = '', $taxPayerType = self::TAXPAYER_WE_DONT_KNOW) {
        $this->setTaxPayerId($taxpayerId);
        $this->setTaxPayerType($taxPayerType);
    }

    /**
     * @return array
     */
    protected function getRequiredFields() {
        return $this->requiredFields;
    }

    /**
     * @param array $requiredFields
     */
    protected function setRequiredFields(array $requiredFields) {
        $this->requiredFields = $requiredFields;
    }

    /**
     * @return int
     */
    public function getDefault() {
        return self::TAXPAYER_WE_DONT_KNOW;
    }

    /**
     * Ellenőrizzük a mező típusát
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return string
     * @throws SzamlaAgentException
     */
    protected function checkField($field, $value) {
        if (property_exists($this, $field)) {
            $required = in_array($field, $this->getRequiredFields());
            switch ($field) {
                case 'taxPayerType':
                    Util::checkIntField($field, $value, $required, __CLASS__);
                    break;
                case 'taxPayerId':
                    Util::checkStrFieldWithRegExp($field, $value, false, __CLASS__, '/[0-9]{8}/');
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
     * Összeállítja az adózó XML adatait
     *
     * @param Request $request
     *
     * @return array
     * @throws SzamlaAgentException
     */
    public function buildXmlData(Request $request) {
        $this->checkFields();

        $data = [];
        $data["beallitasok"] = $request->getAgent()->getSetting()->buildXmlData($request);
        $data["torzsszam"]   = $this->getTaxPayerId();

        return $data;
    }

    /**
     * @return string
     */
    public function getTaxPayerId() {
        return $this->taxPayerId;
    }

    /**
     * @param string $taxPayerId
     */
    public function setTaxPayerId($taxPayerId) {
        $this->taxPayerId = substr($taxPayerId, 0,8);
    }

    /**
     * @return int
     */
    public function getTaxPayerType() {
        return $this->taxPayerType;
    }

    /**
     * Adózó milyen típusú adóalany
     *
     * Adott ÁFA összeg felett be kell küldeni az adóhatósághoz a számlát a NAV online rendszerében, kivéve ha a vásárló magányszemély.
     * Ezt az információt a partner adatként tárolja a rendszerben, ott módosítható is.
     *
     * A következő értékeket veheti fel ez a mező:
     *
     *  5: TaxPayer::TAXPAYER_JOINT_VENTURE                        - társas vállalkozás (Bt., Kft., zRt.)
     *  4: TaxPayer::TAXPAYER_INDIVIDUAL_BUSINESS                  - egyéni vállalkozó
     *  3: TaxPayer::TAXPAYER_PRIVATE_INDIVIDUAL_WITH_TAXNUMBER    - adószámos magánszemély
     *  2: TaxPayer::TAXPAYER_OTHER_ORGANIZATION_WITH_TAXNUMBER    - adószámos egyéb szervezet
     *  1: TaxPayer::TAXPAYER_HAS_TAXNUMBER                        - van adószáma
     *  0: TaxPayer::TAXPAYER_WE_DONT_KNOW                         - nem tudjuk
     * -1: TaxPayer::TAXPAYER_NO_TAXNUMBER                         - nincs adószáma
     * -2: TaxPayer::TAXPAYER_PRIVATE_INDIVIDUAL                   - magánszemély
     * -3: TaxPayer::TAXPAYER_OTHER_ORGANIZATION_WITHOUT_TAXNUMBER - adószám nélküli egyéb szervezet
     *
     * @param int $taxPayerType
     */
    public function setTaxPayerType($taxPayerType) {
        $this->taxPayerType = $taxPayerType;
    }
 }