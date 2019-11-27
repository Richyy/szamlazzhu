<?php

namespace SzamlaAgent;

use SzamlaAgent\Document\Document;

/**
 * A Számla Agent kéréseket kezelő osztály
 *
 * @package SzamlaAgent
 */
class Request {

    /**
     * Sikesességet jelző válaszkód
     */
    const HTTP_OK = 200;

    const CRLF = "\r\n";

    /**
     * Számla Agent XML séma alapértelmezett URL
     * (az XML generálásához használjuk, ne változtasd meg)
     */
    const XML_BASE_URL = 'http://www.szamlazz.hu/';

    /**
     * Felhasználói munkamenethez használt változó neve
     */
    const SESSION_CONNECTION_TYPE = 'connectionType';

    /**
     * Számla Agent kérés maximális idő másodpercben
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * Számla Agent kérés módja: natív
     */
    const CALL_METHOD_LEGACY = 1;

    /**
     * Számla Agent kérés módja: CURL
     */
    const CALL_METHOD_CURL = 2;

    /**
     * Számla Agent kérés módja: automatikus
     */
    const CALL_METHOD_AUTO = 3;

    /**
     * Számlakészítéshez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/agent/xmlszamla.xsd
     */
    const XML_SCHEMA_CREATE_INVOICE = 'xmlszamla';

    /**
     * Számla sztornózásához használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/agentst/xmlszamlast.xsd
     */
    const XML_SCHEMA_CREATE_REVERSE_INVOICE = 'xmlszamlast';

    /**
     * Jóváírás rögzítéséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/agentkifiz/xmlszamlakifiz.xsd
     */
    const XML_SCHEMA_PAY_INVOICE = 'xmlszamlakifiz';

    /**
     * Számla adatok lekéréséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/agentxml/xmlszamlaxml.xsd
     */
    const XML_SCHEMA_REQUEST_INVOICE_XML = 'xmlszamlaxml';

    /**
     * Számla PDF lekéréséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/agentpdf/xmlszamlapdf.xsd
     */
    const XML_SCHEMA_REQUEST_INVOICE_PDF = 'xmlszamlapdf';

    /**
     * Nyugta készítéséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/nyugtacreate/xmlnyugtacreate.xsd
     */
    const XML_SCHEMA_CREATE_RECEIPT = 'xmlnyugtacreate';

    /**
     * Nyugta sztornóhoz használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/nyugtast/xmlnyugtast.xsd
     */
    const XML_SCHEMA_CREATE_REVERSE_RECEIPT = 'xmlnyugtast';

    /**
     * Nyugta kiküldéséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/nyugtasend/xmlnyugtasend.xsd
     */
    const XML_SCHEMA_SEND_RECEIPT = 'xmlnyugtasend';

    /**
     * Nyugta megjelenítéséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/nyugtaget/xmlnyugtaget.xsd
     */
    const XML_SCHEMA_GET_RECEIPT = 'xmlnyugtaget';

    /**
     * Adózó adatainak lekérdezéséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/taxpayer/xmltaxpayer.xsd
     */
    const XML_SCHEMA_TAXPAYER = 'xmltaxpayer';

    /**
     * Díjbekérő törléséhez használt XML séma
     * @see https://www.szamlazz.hu/szamla/docs/xsds/dijbekerodel/xmlszamladbkdel.xsd
     */
    const XML_SCHEMA_DELETE_PROFORMA = 'xmlszamladbkdel';

    /**
     * @var SzamlaAgent
     */
    private $agent;

    /**
     * A Számla Agent kérés típusa
     *
     * @see Request::getActionName()
     * @var string
     */
    private $type;

    /**
     * Az az entitás, amelynek adatait XML formátumban továbbítani fogjuk
     * (számla, díjbekérő, szállítólevél, adózó, stb.)
     *
     * @var object
     */
    private $entity;

    /**
     * Az Agent kéréshez összeállított XML adatok
     *
     * @var string
     */
    private $xmlData;

    /**
     * XML gyökérelem neve
     *
     * @var string
     */
    private $xmlName;

    /**
     * XML fájl elérési útvonala
     *
     * @var string
     */
    private $xmlFilePath;

    /**
     * XSD könyvtárának neve
     *
     * @var string
     */
    private $xsdDir;

    /**
     * Számla Agent kérés XML fájlneve
     *
     * @var string
     */
    private $fileName;

    /**
     * Egyedi elválasztó azonosító az XML kéréshez
     *
     * @var string
     */
    private $delim;

    /**
     * Az Agent kérésnél továbbított POST adatok
     *
     * @var string
     */
    private $postFields;

    /**
     * Az Agent kéréshez tartozó adatok CDATA-ként lesznek átadva
     *
     * @var boolean
     */
    private $cData = true;

    /**
     * Számla Agent kérés létrehozása
     *
     * @param SzamlaAgent $agent
     * @param string      $type
     * @param object      $entity
     */
    public function __construct(SzamlaAgent $agent, $type, $entity) {
        $this->setAgent($agent);
        $this->setType($type);
        $this->setEntity($entity);
        $this->setCData(true);
    }

    /**
     * Összeállítja a kérés elküldéséhez szükséges XML adatokat
     *
     * @throws SzamlaAgentException
     * @throws \ReflectionException
     */
    private function buildXmlData() {
        $this->setXmlFileData($this->getType());
        $this->agent->getLogger()->debug('XML adatok összeállítása elkezdődött.');

        $xmlData = $this->getEntity()->buildXmlData($this);

        $xml = new SimpleXMLExtended($this->getXmlBase());
        $this->arrayToXML($xmlData, $xml);

        $result = Util::checkValidXml($xml->saveXML());
        // Ha nincs hiba az XML-ben, elmentjük
        if (empty($result)) {
            $formatXml = Util::formatXml($xml);
            $this->setXmlData($formatXml->saveXML());

            $this->agent->getLogger()->debug('XML adatok létrehozása kész.');
            $this->createXmlFile($formatXml);
        } else {
            throw new SzamlaAgentException(SzamlaAgentException::XML_NOT_VALID . " a {$result[0]->line}. sorban: {$result[0]->message}. ");
        }
    }

    /**
     * @param array             $xmlData
     * @param SimpleXMLExtended $xmlFields
     */
    private function arrayToXML(array $xmlData, SimpleXMLExtended &$xmlFields) {
        foreach($xmlData as $key => $value) {
            if (is_array($value)) {
                $fieldKey = $key;
                if (strpos($key, "item") !== false) $fieldKey = 'tetel';
                if (strpos($key, "note") !== false) $fieldKey = 'kifizetes';
                $subNode = $xmlFields->addChild("$fieldKey");
                $this->arrayToXML($value, $subNode);
            } else {
                if (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                } else if(!$this->isCData()) {
                    $value = htmlspecialchars("$value");
                }

                if ($this->isCData()) {
                    $xmlFields->addChildWithCData("$key", $value);
                } else {
                    $xmlFields->addChild("$key", $value);
                }
            }
        }
    }

    /**
     * Létrehozza a kérés adatait tartalmazó XML fájlt
     *
     * @param  \DOMDocument $xml
     *
     * @throws SzamlaAgentException
     * @throws \ReflectionException
     */
    private function createXmlFile(\DOMDocument $xml) {
        $fileName = Util::getXmlFileName('request', $this->getXmlName(), $this->getEntity() );
        $xml->save($fileName);

        $this->setXmlFilePath(Util::getRealPath($fileName));
        
        $this->agent->getLogger()->debug('XML fájl mentése sikeres.', [
            'file_path' => Util::getRealPath($fileName),
        ]);
    }

    /**
     * Visszaadja az alapértelmezett XML fejlécet
     *
     * @return string
     */
    private function getXmlBase() {
        $xmlName = $this->getXmlName();

        $queryData  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $queryData .= '<'.$xmlName.' xmlns="'.$this->getXmlNs($xmlName).'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="'.$this->getSchemaLocation($xmlName).'">'.PHP_EOL;
        $queryData .= '</'.$xmlName.'>' . self::CRLF;

        return $queryData;
    }

    /**
     * @param $xmlName
     *
     * @return string
     */
    private function getSchemaLocation($xmlName) {
        return self::XML_BASE_URL . "szamla/{$xmlName} http://www.szamlazz.hu/szamla/docs/xsds/{$this->getXsdDir()}/{$xmlName}.xsd";
    }

    /**
     * Visszaadja az XML séma névterét
     *
     * @param $xmlName
     *
     * @return string
     */
    private function getXmlNs($xmlName) {
        return self::XML_BASE_URL . "{$xmlName}";
    }

    /**
     * Összeállítja az elküldendő POST adatokat
     */
    private function buildQuery() {
        $this->setDelim(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16));

        $queryData  = '--' . $this->getDelim() . self::CRLF;
        $queryData .= 'Content-Disposition: form-data; name="' . $this->getFileName() . '"; filename="' . $this->getFileName() . '"' . self::CRLF;
        $queryData .= 'Content-Type: text/xml' . self::CRLF . self::CRLF;
        $queryData .= $this->getXmlData() . self::CRLF;
        $queryData .= "--" . $this->getDelim() . "--" . self::CRLF;

        $this->setPostFields($queryData);
    }

    /**
     * Inicializálja az Agent kérés fejlécét
     */
    private function init() {
        header('Content-type: text/html; charset=' . SzamlaAgent::CHARSET);
    }

    /**
     * Beállítja a Számla Agent XML fájl adatait
     * (xml gyökérelem neve, xml fájlnév)
     *
     * @param $type
     *
     * @throws SzamlaAgentException
     */
    private function setXmlFileData($type) {
        switch ($type) {
            // Számlakészítés (normál, előleg, végszámla)
            case 'generateProforma':
            case 'generateInvoice':
            case 'generatePrePaymentInvoice':
            case 'generateFinalInvoice':
            case 'generateCorrectiveInvoice':
            case 'generateDeliveryNote':
                $fileName = 'action-xmlagentxmlfile';
                $xmlName  = self::XML_SCHEMA_CREATE_INVOICE;
                $xsdDir  = 'agent';
                break;
            // Számla sztornó
            case 'generateReverseInvoice':
                $fileName = 'action-szamla_agent_st';
                $xmlName  = self::XML_SCHEMA_CREATE_REVERSE_INVOICE;
                $xsdDir  = 'agentst';
                break;
            // Jóváírás rögzítése
            case 'payInvoice':
                $fileName = 'action-szamla_agent_kifiz';
                $xmlName  = self::XML_SCHEMA_PAY_INVOICE;
                $xsdDir  = 'agentkifiz';
                break;
            // Számla adatok lekérése
            case 'requestInvoiceData':
                $fileName =  'action-szamla_agent_xml';
                $xmlName  = self::XML_SCHEMA_REQUEST_INVOICE_XML;
                $xsdDir  = 'agentxml';
                break;
            // Számla PDF lekérése
            case 'requestInvoicePDF':
                $fileName =  'action-szamla_agent_pdf';
                $xmlName  = self::XML_SCHEMA_REQUEST_INVOICE_PDF;
                $xsdDir  = 'agentpdf';
                break;
            // Nyugta készítés
            case 'generateReceipt':
                $fileName = 'action-szamla_agent_nyugta_create';
                $xmlName  = self::XML_SCHEMA_CREATE_RECEIPT;
                $xsdDir  = 'nyugtacreate';
                break;
            // Nyugta sztornó
            case 'generateReverseReceipt':
                $fileName = 'action-szamla_agent_nyugta_storno';
                $xmlName  = self::XML_SCHEMA_CREATE_REVERSE_RECEIPT;
                $xsdDir  = 'nyugtast';
                break;
            // Nyugta kiküldés
            case 'sendReceipt':
                $fileName = 'action-szamla_agent_nyugta_send';
                $xmlName  = self::XML_SCHEMA_SEND_RECEIPT;
                $xsdDir  = 'nyugtasend';
                break;
            // Nyugta adatok lekérése
            case 'requestReceiptData':
            case 'requestReceiptPDF':
                $fileName = 'action-szamla_agent_nyugta_get';
                $xmlName  = self::XML_SCHEMA_GET_RECEIPT;
                $xsdDir   = 'nyugtaget';
                break;
            // Adózó adatainak lekérdezése
            case 'getTaxPayer':
                $fileName = 'action-szamla_agent_taxpayer';
                $xmlName  = self::XML_SCHEMA_TAXPAYER;
                $xsdDir   = 'taxpayer';
                break;
            // Díjbekérő törlése
            case 'deleteProforma':
                $fileName = 'action-szamla_agent_dijbekero_torlese';
                $xmlName  = self::XML_SCHEMA_DELETE_PROFORMA;
                $xsdDir   = 'dijbekerodel';
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::REQUEST_TYPE_NOT_EXISTS . ": {$type}");
        }

        $this->setFileName($fileName);
        $this->setXmlName($xmlName);
        $this->setXsdDir($xsdDir);
    }

    /**
     * Visszaadja a kapcsolat módjának elnevezését
     *
     * @param $type
     *
     * @return string
     * @throws SzamlaAgentException
     */
    public function getConnectionModeName($type) {
        switch ($type) {
            case self::CALL_METHOD_CURL:   $result = 'CURL';   break;
            case self::CALL_METHOD_LEGACY: $result = 'LEGACY'; break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::CONNECTION_METHOD_CANNOT_BE_DETERMINED);
        }
        return $result;
    }

    /**
     * Számla Agent kérés küldése a szamlazz.hu felé
     *
     * Először megpróbáljuk CURL-el elküldeni a kérést.
     * Ha nem sikerül, akkor átváltunk a legacy módra.
     *
     * @return array
     * @throws \Exception
     */
    public function send() {
        try {
            if(!isset($_SESSION)) {
                session_start();
            }

            $this->init();
            $this->buildXmlData();
            $this->buildQuery();

            $method = $this->agent->getCallMethod();
            switch ($method) {
                case self::CALL_METHOD_AUTO:
                    $response = $this->checkConnection();
                    break;
                case self::CALL_METHOD_CURL:
                    $response = $this->makeCurlCall();
                    break;
                case self::CALL_METHOD_LEGACY:
                    $response = $this->makeLegacyCall();
                    break;
                default:
                    throw new SzamlaAgentException(SzamlaAgentException::CALL_TYPE_NOT_EXISTS . ": {$method}");
            }
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Ellenőrzi a kapcsolódási módokat és kiválasztja a legmegfelelőbbet
     *
     * @return array
     * @throws SzamlaAgentException
     * @throws \Exception
     */
    private function checkConnection() {
        $type = self::SESSION_CONNECTION_TYPE;
        $agent = $this->getAgent();

        if (!isset($_SESSION[$type])) {
            try {
                $ch = curl_init(SzamlaAgent::API_URL);
                // setting ssl verification ok, defininig root certificate to validate connection to remote server
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                // never ever set to false, as this carries huge security risk!!!
                // if you experience problems with ssl validation, use legacy data call instead! (see below)
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, $agent->getCertificationFile());
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_exec($ch);

                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code == self::HTTP_OK) {
                    $_SESSION[$type] = $this->getConnectionModeName(self::CALL_METHOD_CURL);
                    
                    $agent->getLogger()->debug('A kapcsolódás típusa beállítva a következőre: CURL');
                    return $this->makeCurlCall();
                } else {
                    $_SESSION[$type] = $this->getConnectionModeName(self::CALL_METHOD_LEGACY);
                    $agent->getLogger()->warn('A kapcsolódás típusa beállítva a következőre: LEGACY, mert a CURL nem használható.');
                    return $this->makeLegacyCall();
                }
            } catch (\Exception $e) {
                throw $e;
            }
        } elseif (isset($_SESSION[$type]) && $_SESSION[$type] == $this->getConnectionModeName(self::CALL_METHOD_CURL)) {
            return $this->makeCurlCall();
        } else {
            return $this->makeLegacyCall();
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function makeCurlCall() {
        try {
            $agent = $this->getAgent();

            $ch = curl_init(SzamlaAgent::API_URL);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $agent->getCertificationFile());
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $xmlFile = new \CURLFile($this->getXmlFilePath());
            $xmlFile->setPostFilename(basename($this->getXmlFilePath()));
            $xmlFile->setMimeType('text/xml');
            $postFields = array($this->getFileName() => $xmlFile);

            curl_setopt($ch, CURLOPT_HTTPHEADER , array(
                'charset: ' . SzamlaAgent::CHARSET,
                'PHP: ' . PHP_VERSION,
                'API: ' . SzamlaAgent::API_VERSION
            ));

            if ($this->isAttachments()) {
                $attachments = $this->getEntity()->getAttachments();
                if (!empty($attachments)) {
                    for ($i = 0; $i < count($attachments); $i++) {
                        $attachCount = ($i + 1);
                        if (file_exists($attachments[$i])) {
                            $isAttachable = true;
                            foreach ($postFields as $field) {
                                if ($field->name === $attachments[$i]) {
                                    $isAttachable = false;
                                    $agent->getLogger()->warn('A számlamelléklet már csatolva van.', [
                                        'attachment_count' => $attachCount,
                                        'attachment' => $attachments[$i],
                                    ]);
                                }
                            }

                            if ($isAttachable) {
                                $attachment = new \CURLFile($attachments[$i]);
                                $attachment->setPostFilename(basename($attachments[$i]));
                                $postFields["attachfile" . $attachCount] = $attachment;

                                $agent->getLogger()->debug('Számlamelléklet csatolva.', [
                                    'attachment_count' => $attachCount,
                                    'attachment' => $attachments[$i],
                                ]);
                            }
                        }
                    }
                }
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

            if (!empty($agent->getCookieFileName())) {
                $cookieFile = $this->getCookieFilePath();
                if (file_exists($cookieFile) && filesize($cookieFile > 0 && strpos(file_get_contents($cookieFile),'curl') === false)) {
                    file_put_contents($cookieFile, "");
                    $agent->getLogger()->debug('A cookie fájl tartalma megváltozott.');
                }
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

                if (file_exists($cookieFile) && filesize($cookieFile) > 0) {
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
                }
            }

            $agent->getLogger()->debug('CURL adatok elküldése elkezdődött.', $this->getPostFields());
            $result = curl_exec($ch);

            $headerSize = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
            $header     = substr($result, 0, $headerSize);
            $headers    = preg_split('/\n|\r\n?/', $header);
            $body       = substr($result, $headerSize);

            $response = array(
                'headers' => $this->getHeadersFromResponse($headers),
                'body'    => $body
            );

            $error = curl_error($ch);
            if (!empty($error)) {
                throw new SzamlaAgentException($error);
            } else {
                $keys = implode(",", array_keys($headers));
                if ($response['headers']['Content-Type'] == 'application/pdf' || (!preg_match('/(szlahu_)/', $keys, $matches))) {
                    $msg = $response['headers'];
                } else {
                    $msg = $response;
                }

                $response['headers']['Schema-Type'] = $this->getXmlSchemaType();
                $agent->getLogger()->debug('CURL adatok elküldése sikeresen befejeződött.', [
                    'response' => $msg,
                ]);
            }
            curl_close($ch);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function makeLegacyCall() {
        try {
            $agent = $this->getAgent();

            if ($this->isAttachments()) {
                throw new SzamlaAgentException(SzamlaAgentException::SENDING_ATTACHMENT_NOT_ALLOWED);
            }

            $cookieText = "";
            $cookies = array();
            $stored_cookies = array();

            $cookieFile = $this->getCookieFilePath();
            if (isset($cookieFile) && file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile),'curl') === false) {
                $stored_cookies = unserialize(file_get_contents($cookieFile));
                $cookieText = "\r\n"."Cookie: JSESSIONID=".$stored_cookies["JSESSIONID"];
            }

            $context = stream_context_create(array(
                'http' => array(
                    "method" => "POST",
                    "header" => "Content-Type: multipart/form-data; boundary=".$this->getDelim().$cookieText."; charset= ".SzamlaAgent::CHARSET."; PHP= ".PHP_VERSION."; API= ".SzamlaAgent::API_VERSION,
                    "content" => $this->getPostFields()
                )
            ));

            $agent->getLogger()->debug('LEGACY adatok elküldése elkezdődött.', $this->getPostFields());
            
            $body = file_get_contents(SzamlaAgent::API_URL, false, $context);

            foreach ($http_response_header as $header) {
                if (preg_match('/^Set-Cookie:\s*([^;]+)/', $header, $matches)) {
                    parse_str($matches[1], $temp);
                    $cookies += $temp;
                }
            }

            $response = array(
                'headers' => $this->getHeadersFromResponse($http_response_header),
                'body'    => $body
            );

            if ($response['headers']['Content-Type'] == 'application/pdf') {
                $msg = $response['headers'];
            } else {
                $msg = $response;
            }
            $response['headers']['Schema-Type'] = $this->getXmlSchemaType();
            $agent->getLogger()->debug('LEGACY adatok elküldése befejeződött.', [
                'response' => $msg
            ]);

            if (isset($cookieFile) && isset($cookies['JSESSIONID'])) {
                if (file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile),'curl') !== false) {
                    file_put_contents($cookieFile, serialize($cookies));
                    $agent->getLogger()->debug('Cookie tartalma megváltozott.');
                } elseif (file_exists($cookieFile) && filesize($cookieFile) > 0 && strpos(file_get_contents($cookieFile),'curl') === false && ($stored_cookies != $cookies)) {
                    file_put_contents($cookieFile, serialize($cookies));
                    $agent->getLogger()->debug('Cookie tartalma megváltozott.');
                } elseif (file_exists($cookieFile) && filesize($cookieFile) == 0) {
                    file_put_contents($cookieFile, serialize($cookies));
                    $agent->getLogger()->debug('Cookie tartalma megváltozott.');
                }
            }
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Visszaadja a válasz fejléc adatait
     *
     * @param $headerContent
     *
     * @return array
     */
    private function getHeadersFromResponse($headerContent) {
        $headers = array();
        foreach ($headerContent as $index => $content) {
            if (Util::isNotBlank($content)) {
                if ($index === 0) {
                    $headers['http_code'] = $content;
                } else {
                    $pos = strpos($content, ":");
                    if ($pos !== false) {
                        list ($key, $value) = explode(': ', $content);
                        $headers[$key] = $value;
                    }
                }
            }
        }
        return $headers;
    }

    /**
     * @return string
     */
    public function getCookieFilePath() {
        $fileName = $this->getAgent()->getCookieFileName();
        if (Util::isBlank($this->getAgent()->getCookieFileName())) {
            $fileName = SzamlaAgent::COOKIE_FILENAME;
        }
        return Util::getBasePath() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @return SzamlaAgent
     */
    public function getAgent() {
        return $this->agent;
    }

    /**
     * @param SzamlaAgent $agent
     */
    private function setAgent($agent) {
        $this->agent = $agent;
    }

    /**
     * @return string
     */
    private function getType() {
        return $this->type;
    }

    /**
     * Beállítja a kérés típusát
     *
     * @see   Request::getActionName()
     * @param string $type
     */
    private function setType($type) {
        $this->type = $type;
    }

    /**
     * @return object
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * @param object $entity
     */
    private function setEntity($entity) {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    private function getXmlData() {
        return $this->xmlData;
    }

    /**
     * @param string $xmlData
     */
    private function setXmlData($xmlData) {
        $this->xmlData = $xmlData;
    }

    /**
     * @return string
     */
    private function getDelim() {
        return $this->delim;
    }

    /**
     * @param string $delim
     */
    private function setDelim($delim) {
        $this->delim = $delim;
    }

    /**
     * @return string
     */
    private function getPostFields() {
        return $this->postFields;
    }

    /**
     * @param string $postFields
     */
    private function setPostFields($postFields) {
        $this->postFields = $postFields;
    }

    /**
     * @return bool
     */
    private function isCData() {
        return $this->cData;
    }

    /**
     * @param bool $cData
     */
    private function setCData($cData) {
        $this->cData = $cData;
    }

    /**
     * @return string
     */
    public function getXmlName() {
        return $this->xmlName;
    }

    /**
     * @param string $xmlName
     */
    private function setXmlName($xmlName) {
        $this->xmlName = $xmlName;
    }

    /**
     * @return string
     */
    private function getFileName() {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    private function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getXmlFilePath() {
        return $this->xmlFilePath;
    }

    /**
     * @param string $xmlFilePath
     */
    private function setXmlFilePath($xmlFilePath) {
        $this->xmlFilePath = $xmlFilePath;
    }

    /**
     * @return string
     */
    private function getXsdDir() {
        return $this->xsdDir;
    }

    /**
     * @param string $xsdDir
     */
    private function setXsdDir($xsdDir) {
        $this->xsdDir = $xsdDir;
    }

    /**
     * Visszaadja az XML séma típusát
     * (számla, nyugta, adózó)
     *
     * @return string
     * @throws SzamlaAgentException
     */
    private function getXmlSchemaType() {
        switch ($this->getXmlName()) {
            case self::XML_SCHEMA_CREATE_INVOICE:
            case self::XML_SCHEMA_CREATE_REVERSE_INVOICE:
            case self::XML_SCHEMA_PAY_INVOICE:
            case self::XML_SCHEMA_REQUEST_INVOICE_XML:
            case self::XML_SCHEMA_REQUEST_INVOICE_PDF:
                $type = Document::DOCUMENT_TYPE_INVOICE;
                break;
            case self::XML_SCHEMA_DELETE_PROFORMA:
                $type = Document::DOCUMENT_TYPE_PROFORMA;
                break;
            case self::XML_SCHEMA_CREATE_RECEIPT:
            case self::XML_SCHEMA_CREATE_REVERSE_RECEIPT:
            case self::XML_SCHEMA_SEND_RECEIPT:
            case self::XML_SCHEMA_GET_RECEIPT:
                $type = Document::DOCUMENT_TYPE_RECEIPT;
                break;
            case self::XML_SCHEMA_TAXPAYER:
                $type = 'taxpayer';
                break;
            default:
                throw new SzamlaAgentException(SzamlaAgentException::XML_SCHEMA_TYPE_NOT_EXISTS . ": {$this->getXmlName()}");
        }
        return $type;
    }

    private function isAttachments() {
        $entity = $this->getEntity();
        if (is_a($entity, '\SzamlaAgent\Document\Invoice\Invoice')) {
            return (count($entity->getAttachments()) > 0);
        }
        return false;
    }
}