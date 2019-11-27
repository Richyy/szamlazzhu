<?php

namespace SzamlaAgent\Waybill;

use SzamlaAgent\Request\Request;
use SzamlaAgent\SzamlaAgentException;
use SzamlaAgent\Util;

/**
 * Fuvarlevél
 *
 * @package SzamlaAgent\Waybill
 */
class Waybill {

    // Transoflex
    const WAYBILL_TYPE_TRANSOFLEX = 'Transoflex';
    // Sprinter
    const WAYBILL_TYPE_SPRINTER = 'Sprinter';
    // Pick-Pack-Pont
    const WAYBILL_TYPE_PPP = 'PPP';
    // Magyar Posta
    const WAYBILL_TYPE_MPL = 'MPL';

    /**
     * Úti cél
     *
     * @var string
     */
    protected $destination;

    /**
     * Futárszolgálat
     * (TOF, PPP, SPRINTER, MPL, FOXPOST, GLS, EMPTY)
     *
     * @var string
     */
    protected $parcel;

    /**
     * Általános vonalkód megadási lehetőség, ha nem adjuk meg az adott futárszolgálat
     * vonalkódjának előállításához szükséges adatokat, akkor ezt használja a rendszer.
     *
     * @var string
     */
    protected $barcode;

    /**
     * A fuvarlevélen ez a megjegyzés jelenik meg
     *
     * @var string
     */
    protected $comment;


    /**
     * Fuvarlevél létrehozása
     *
     * @param string  $destination  Úti cél
     * @param string  $parcel       Futárszolgálat neve
     * @param string  $barcode      Vonalkód
     * @param string  $comment      fuvarlevél megjegyzés
     */
    protected function __construct($destination = '', $parcel = '', $barcode = '', $comment = '') {
        $this->setDestination($destination);
        $this->setParcel($parcel);
        $this->setBarcode($barcode);
        $this->setComment($comment);
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
            switch ($field) {
                case 'destination':
                case 'parcel':
                case 'barcode':
                case 'comment':
                    Util::checkStrField($field, $value, false, __CLASS__);
                    break;
            }
        }
        return $value;
    }

    /**
     * Ellenőrizzük a tulajdonságokat
     *
     * @param string $entity
     *
     * @throws SzamlaAgentException
     */
    protected function checkFields($entity = null) {
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            if (get_class() == $entity) {
                self::checkField($field, $value);
            } else {
                $this::checkField($field, $value);
            }
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws SzamlaAgentException
     */
    public function buildXmlData(Request $request) {
        $data = [];
        self::checkFields(get_class());

        if (Util::isNotBlank($this->getDestination())) $data['uticel'] = $this->getDestination();
        if (Util::isNotBlank($this->getParcel()))      $data['futarSzolgalat'] = $this->getParcel();
        if (Util::isNotBlank($this->getBarcode()))     $data['vonalkod'] = $this->getBarcode();
        if (Util::isNotBlank($this->getComment()))     $data['megjegyzes'] = $this->getComment();

        return $data;
    }

    /**
     * @return string
     */
    public function getDestination() {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination) {
        $this->destination = $destination;
    }

    /**
     * @return string
     */
    public function getParcel() {
        return $this->parcel;
    }

    /**
     * @param string $parcel
     */
    public function setParcel($parcel) {
        $this->parcel = $parcel;
    }

    /**
     * @return string
     */
    public function getBarcode() {
        return $this->barcode;
    }

    /**
     * @param string $barcode
     */
    public function setBarcode($barcode) {
        $this->barcode = $barcode;
    }

    /**
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment) {
        $this->comment = $comment;
    }
}