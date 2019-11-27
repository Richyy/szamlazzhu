<?php

namespace SzamlaAgent;

/**
 * A Számla Agent közösen használt, hasznos funkcióinak osztálya
 *
 * @package SzamlaAgent
 */
class SzamlaAgentUtil {

    /**
     * Alapértelmezetten hozzáadott napok száma
     */
    const DEFAULT_ADDED_DAYS = 8;

    /**
     * Pontos dátum (Y-m-d) formátumban
     */
    const DATE_FORMAT_DATE      = 'date';

    /**
     * Pontos dátum (Y-m-d H:i:s) formátumban
     */
    const DATE_FORMAT_DATETIME  = 'datetime';

    /**
     * Aktuális időbélyeg
     */
    const DATE_FORMAT_TIMESTAMP = 'timestamp';


    /**
     * A kapott dátumot formázott szövegként adja vissza
     * (hozzáadva az átadott napok számát)
     *
     * @param int         $count
     * @param string|null $date
     *
     * @return mixed
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    public static function addDaysToDate($count, $date = null) {
        $newDate = self::getToday();

        if (!empty($date)) {
            $newDate = new \DateTime($date);
        }
        $newDate->modify("+{$count} day");
        return self::getDateStr($newDate);
    }

    /**
     * A kapott dátumot formázott szövegként adja vissza (típustól függően)
     *
     * @param \DateTime $date
     * @param string    $format
     *
     * @return mixed
     * @throws SzamlaAgentException
     */
    public static function getDateStr(\DateTime $date, $format = self::DATE_FORMAT_DATE) {
        switch ($format) {
            case self::DATE_FORMAT_DATE:
                $result = $date->format('Y-m-d');
                break;
            case self::DATE_FORMAT_DATETIME:
                $result = $date->format('Y-m-d H:i:s');
                break;
            case self::DATE_FORMAT_TIMESTAMP:
                $result = $date->getTimestamp();
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::DATE_FORMAT_NOT_EXISTS . ': ' . $format);
        }
        return $result;
    }

    /**
     * Visszaadja a mai dátumot
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function getToday() {
        return new \DateTime('now');
    }

    /**
     * Szövegként adja vissza a mai dátumot ('Y-m-d' formátumban)
     *
     * @return string
     * @throws \Exception
     */
    public static function getTodayStr() {
        $data = self::getToday();
        return $data->format('Y-m-d');
    }

    /**
     * Visszaadja, hogy a megadott dátum használható-e
     * A következő formátum az elfogadott: 'Y-m-d'.
     *
     * @param string $date
     *
     * @return bool
     */
    public static function isValidDate($date) {
        $parsedDate = \DateTime::createFromFormat('Y-m-d', $date);

        if (\DateTime::getLastErrors()['warning_count'] > 0 || !checkdate($parsedDate->format("m"), $parsedDate->format("d"), $parsedDate->format("Y"))) {
            return false;
        }

        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $parsedDate->format('Y-m-d'))) {
            return false;
        }
        return true;
    }

    /**
     * Visszaadja, hogy a megadott dátum nem érvényés-e
     * A következő formátum az elfogadott: 'Y-m-d'.
     *
     * @param string $date
     *
     * @return bool
     */
    public static function isNotValidDate($date) {
        return !self::isValidDate($date);
    }

    /**
     * Visszaadja a létrehozandó XML fájl nevét
     * Az $entity megadása esetén a fájl neve az átadott osztály neve lesz
     *
     * @param string $prefix a fájl előtagja
     * @param string $name   a fájl neve
     * @param object $entity osztály példány
     *
     * @return string|bool
     * @throws \ReflectionException
     */
    public static function getXmlFileName($prefix, $name, $entity = null) {
        if (!empty($name) && !empty($entity)) {
            $name .= '-' . (new \ReflectionClass($entity))->getShortName();
        }

        $fileName  = $prefix . '-' . strtolower($name) . '-' . date('YmdHis') . '.xml';
        return self::getAbsPath(SzamlaAgent::XML_FILE_SAVE_PATH, $fileName);
    }

    /**
     * Visszaadja a SimpleXMLElement tartalmát formázott xml-ként
     *
     * @param  \SimpleXMLElement $simpleXMLElement
     * @return \DOMDocument
     */
    public static function formatXml(\SimpleXMLElement $simpleXMLElement) {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($simpleXMLElement->asXML());
        return $xmlDocument;
    }

    /**
     * Ellenőrzi az XML érvényességét
     *
     * @param $xmlContent
     *
     * @return array
     */
    public static function checkValidXml($xmlContent) {
        libxml_use_internal_errors(true);

        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->loadXML($xmlContent);

        $result = libxml_get_errors();
        libxml_clear_errors();

        return $result;
    }

    /**
     * Visszaadja a fájl valódi útvonalát
     *
     * @param $path
     *
     * @return bool|string
     */
    public static function getRealPath($path) {
        if (file_exists($path)) {
            return realpath($path);
        } else {
            return $path;
        }
    }

    /**
     * @param string $dir
     * @param string $fileName
     *
     * @return bool|string
     */
    public static function getAbsPath($dir, $fileName = '') {
        $file = self::getBasePath() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileName;
        return self::getRealPath($file);
    }

    /**
     * @return bool|string
     */
    public static function getBasePath() {
        return self::getRealPath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public static function getDefaultAttachmentPath($fileName) {
        return self::getRealPath(self::getBasePath() . DIRECTORY_SEPARATOR . SzamlaAgent::ATTACHMENTS_SAVE_PATH . DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * A kapott adatokból előállít egy JSON típusú objektumot
     *
     * @param $data
     *
     * @return false|string
     */
    public static function toJson($data) {
        return json_encode($data);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function toArray($data) {
        return json_decode(self::toJson($data),TRUE);
    }

    /**
     * @param $value
     *
     * @return float
     * @throws SzamlaAgentException
     */
    public static function doubleFormat($value) {
        if (is_int($value)) {
            $value = doubleval($value);
        }

        if (is_double($value)) {
            $decimals = strlen(preg_replace('/[\d]+[\.]?/', '', $value, 1));
            if ($decimals == 0) {
                $value = number_format((float)$value, 1, '.', '');
            }
        } else {
            trigger_error('Helytelen típus! Double helyett ' . gettype($value) . ' típus ennél az értéknél: ' . $value, E_USER_WARNING);
        }
        return $value;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isBlank($value) {
        return (is_null($value) || (is_string($value) && $value !== '0' && (empty($value) || trim($value) == '')));
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isNotBlank($value) {
        return !self::isBlank($value);
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     *
     * @throws SzamlaAgentException
     */
    public static function checkStrField($field, $value, $required, $class) {
        $errorMsg = "";
        if (isset($value) && !is_string($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem szöveg!";
        } else if ($required && self::isBlank($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     * @param string $pattern
     *
     * @throws SzamlaAgentException
     */
    public static function checkStrFieldWithRegExp($field, $value, $required, $class, $pattern) {
        $errorMsg = "";
        self::checkStrField($field, $value, $required, __CLASS__);

        if (!preg_match($pattern, $value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem megfelelő!";
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     *
     * @throws SzamlaAgentException
     */
    public static function checkIntField($field, $value, $required, $class) {
        $errorMsg = "";
        if (isset($value) && !is_int($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem egész szám!";
        } else if ($required && !is_numeric($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     *
     * @throws SzamlaAgentException
     */
    public static function checkDoubleField($field, $value, $required, $class) {
        $errorMsg = "";
        if (isset($value) && !is_double($value)) {
            $errorMsg = "A(z) '{$field}' mező értéke nem double!";
        } else if ($required && !is_numeric($value)) {
            $errorMsg = self::getRequiredFieldErrMsg($field);
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     *
     * @throws SzamlaAgentException
     */
    public static function checkDateField($field, $value, $required, $class) {
        $errorMsg = "";
        if (isset($value) && self::isNotValidDate($value)) {
            if ($required) {
                $errorMsg = "A(z) '{$field}' kötelező mező, de nem érvényes dátumot tartalmaz!";
            } else {
                $errorMsg = "A(z) '{$field}' mező értéke nem dátum!";
            }
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param bool   $required
     * @param string $class
     *
     * @throws SzamlaAgentException
     */
    public static function checkBoolField($field, $value, $required, $class) {
        $errorMsg = "";
        if (isset($value) && is_bool($value) === false) {
            if ($required) {
                $errorMsg = "A(z) '{$field}' kötelező mező, de az értéke nem logikai!";
            } else {
                $errorMsg = "A(z) '{$field}' értéke nem logikai!";
            }
        }

        if (!empty($errorMsg)) {
            throw new SzamlaAgentException(SzamlaAgentException::FIELDS_CHECK_ERROR . ": {$errorMsg} (" . $class . ")");
        }
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public static function getRequiredFieldErrMsg($field) {
        return "A(z) '{$field}' kötelező mező, de nincs beállítva az értéke!";
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public static function isNotNull($value) {
        return (null !== $value);
    }
}