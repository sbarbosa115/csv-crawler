<?php

/**
 * Testing URL Class for multiple websites from csv file.
 * @version 1.0
 * @author Sergio Barbosa <sbarbosa115@gmail.com>
 * @package TesterCrawler
 */
class TesterCrawler {

    protected $file = '';
    protected $tempFileName = '';
    protected $homeSites = array('index.html', 'index.htm', 'home.htm', 'home.html');

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
        fwrite($fileWriteResults, "IP;DOMINIO;RECURSO;CODIGO RESPUESTA;\n");
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
    protected function getDomainData($url = 'http://www.example.com/') {
        $fileWriteResults = fopen($this->tempFileName, 'a');
        $bag = $linksBag = array();
        $flag = false;

        $dataUrl = $this->verifyUrlResource($url);
        $doc = $this->getDocumentCurl($dataUrl['data']);

        $links = $doc->getElementsByTagName('a');
        foreach ($links as $tag) {
            if (!in_array($tag->getAttribute('href'), $bag)) {
                $linksBag[] = $this->validateUrl($url, $tag->getAttribute('href'));
                $bag[] = $tag->getAttribute('href');
            }
        }

        $img = $doc->getElementsByTagName('img');
        foreach ($img as $tag) {
            if (!in_array($tag->getAttribute('src'), $bag)) {
                $linksBag[] = $this->validateUrl($url, $tag->getAttribute('src'));
                $bag[] = $tag->getAttribute('src');
            }
        }
        
        print_r($img);
        print_r($links);
        print_r($bag);
        var_dump($dataUrl);
        die;

        foreach ($this->homeSites as $home) {
            $domainStatus = $this->verifyUrlResource($url . '/' . $home);
            if ($domainStatus['code'] >= 200 && $domainStatus['code'] < 400) {
                $doc = $this->getDocument($url . '/' . $home);

                $links = $doc->getElementsByTagName('a');
                foreach ($links as $tag) {
                    if (!in_array($tag->getAttribute('href'), $bag)) {
                        echo $tag->getAttribute('href');
                        $linksBag[] = $this->validateUrl($url, $tag->getAttribute('href'));
                        $bag[] = $tag->getAttribute('href');
                    }
                }

                $img = $doc->getElementsByTagName('img');
                foreach ($img as $tag) {
                    if (!in_array($tag->getAttribute('src'), $bag)) {
                        echo $tag->getAttribute('src');
                        $linksBag[] = $this->validateUrl($url, $tag->getAttribute('src'));
                        $bag[] = $tag->getAttribute('src');
                    }
                }

                for ($i = 0; $i <= sizeof($linksBag) - 1; $i++) {
                    if ($url === substr($linksBag[$i], 0, strlen($url))) {
                        $resourceStatus = $this->verifyUrlResource($linksBag[$i]);
                        $lineResult = $resourceStatus['ip'] . ';' . $linksBag[$i] . ';' . $resourceStatus['code'] . "\n";
                        fwrite($fileWriteResults, $lineResult);
                    }
                }
                $flag = true;
            }
        }

        if (!$flag) {
            $resourceStatus = $this->verifyUrlResource($url);
            $lineResult = $resourceStatus['ip'] . ';' . $url . ';' . $resourceStatus['code'] . "\n";
            fwrite($fileWriteResults, $lineResult);
        }

        fclose($fileWriteResults);
    }

    protected function validateUrl($originalUrl, $urlResource) {
        $url = '';
        if (!preg_match("/(ftp|https|http?)/", $urlResource)) {
            $url = $originalUrl . '/' . $urlResource;
        } else {
            $url = $urlResource;
        }
        return $url;
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
     * Get the html file title.
     * @return null
     */
    protected function getDocument($url) {
        $doc = new DOMDocument();
        @$doc->loadHTMLFile($url);
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

        curl_close($handle);
        return array('code' => $httpCode, 'ip' => $ip, 'data' => $data);
    }

    /**
     * Read file from provides location.
     * @return null
     */
    public function processDomains() {
        $this->readFile();
    }

}

$crawler = new TesterCrawler('lista_dominios.txt');
$crawler->processDomains();
