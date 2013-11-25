<?php

/**
 * Make tests to of domains and returns all error codes and the IP of each of the domains.
 * @author Sergio Barbosa <sbarbosa115@gmail.com>
 */
set_time_limit(0);
ini_set("memory_limit", "512M");

class Comparator {

    protected $file = 'lista_dominios.txt';
    protected $tempFileName;
    protected $urlFile;

    function __construct() {
        $this->tempFileName = 'list_review_domains_' . time() . '.txt';
    }

    /**
     * Read file from provided location.
     * @return null 
     */
    public function readFile() {
        $fileWriteResults = fopen($this->tempFileName, 'wb');
        if (($handle = fopen($this->file, "r")) !== FALSE) {
            while ($data = fgetcsv($handle, 0, ";")) {
                $domain = explode('/', $data[0]);
                $ip = $this->getIpAddress($domain[0]);
                $result = $this->verifyUrlResource($data[0]);
                fwrite($fileWriteResults, $data[0] . ';' . $result . ';' . $ip . "\n");
            }
        }
        fclose($handle);
        fclose($fileWriteResults);
    }

    /**
     * Get http code for the url.
     * @param string $url Url to get http code
     * @return int Code returned. 
     */
    protected function verifyUrlResource($url) {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_TIMEOUT, 2);

        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);
        return $httpCode;
    }

    /**
     * Get ip address from url.
     * @param string $url Url to get ip address.
     * @return string Ip address from url.
     */
    protected function getIpAddress($url) {
        $url = preg_replace(array('#^http?://#', '#/#', '#^http?://#'), '', $url);
        return gethostbyname($url);
    }

}

$run = new Comparator();
$run->readFile();
