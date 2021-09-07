<?php

/**
 * Testing URL Class for multiple websites from csv file.
 * @version 1.0 
 * @package TesterCrawler
 */
class TesterCrawler {

    protected $file = '';
    protected $tempFileName = '';

    /**
     * Contruct class
     * @param string $file Name of archivo to process.
     * @return null
     */
    function __construct($file) {
        set_time_limit(0);
        $this->tempFileName = 'list_review_domains_' . time() . '.txt';
        $this->file = $file;
    }

    /**
     * Read file from provides location.
     * @return null    
     */
    protected function readFile() {
        $fileWriteResults = fopen($this->tempFileName, 'wb');
        fwrite($fileWriteResults, "IP;DOMINIO;TITULO;DESCRIPCION;CODIGO RESPUESTA;PESO\n");
        fclose($fileWriteResults);

        if (($handle = fopen($this->file, "r")) !== FALSE) {
            while ($data = fgetcsv($handle, 0, ";")) {
                $url = $this->addProtocol($data[0]);
                if ($url) {
                    $this->getDomainData($url);
                }
            }
        }
        fclose($handle);
    }

    /**
     * Get and create domain data file.
     * @param string $url Url to get data.
     * @return null
     */
    protected function getDomainData($url) {
        if ($url) {
            $fileWriteResults = fopen($this->tempFileName, 'a');
            $dataUrl = $this->verifyUrlResource($url);
            $doc = $this->getDocumentCurl($dataUrl['data']);
            $nodes = $doc->getElementsByTagName('title');
            $metas = $doc->getElementsByTagName('meta');
            $description = '';

            foreach ($metas as $meta) {
                if (strtolower($meta->getAttribute('name')) == 'description') {
                    if ($meta->getAttribute('content')) {
                        $description = $meta->getAttribute('content');
                    } else if ($meta->getAttribute('value')) {
                        $description = $meta->getAttribute('value');
                    }
                }
            }
            if (strpos($dataUrl['data'],'defaultwebpage') !== false) {
                $description = 'defaultwebpage';
            }

            $lineResult = $dataUrl['ip'] . ';' . $url . ';' . $this->cleanString($nodes->item(0)->nodeValue) . ';' . $this->cleanString($description) . ';' . $dataUrl['code'] . ';' . $dataUrl['size'] . "\n";
            fwrite($fileWriteResults, $lineResult);
            fclose($fileWriteResults);
        }
    }

    /**
     * Clean string to print in file.
     * @param string $string String to clean.
     * @return string String clean.
     */
    protected function cleanString($string) {
        return utf8_decode(substr(trim($string), 0, 20));
    }

    /**
     * Add http protocol to url before process.
     * @param string $url Url to process.
     * @return string Url repared.    
     */
    protected function addProtocol($url) {
        if ($url) {
            if (substr($url, 0, strlen('http://')) !== 'http://' || substr($url, 0, strlen('https://')) !== 'https://') {
                return 'http://' . $url;
            } else {
                return $url;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the html file title.
     * @return null    
     */
    protected function getDocumentCurl($data) {
        $doc = new DOMDocument();
        @$doc->loadHTML($data);
        return $doc;
    }

    /**
     * Get http code for the url.
     * @param string $url Url to get http code
     * @return int Code returned.    
     */
    protected function verifyUrlResource($url) {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $ip = curl_getinfo($handle, CURLINFO_PRIMARY_IP);
        $size = curl_getinfo($handle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        curl_close($handle);
        return array('code' => $httpCode, 'ip' => $ip, 'data' => $data, 'size' => $size);
    }

    /**
     * Read file from provides location.
     * @return null     
     */
    public function processDomains() {
        $this->readFile();
    }

}

$crawler = new TesterCrawler('domain_list.txt');
$crawler->processDomains();
