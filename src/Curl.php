<?php

declare(strict_types=1);

namespace Roadie;

class Curl
{
    public function get(string $url, array $headers = [], bool $useCookie = false): string
    {
        $handle = $this->createHandle($url, $headers, $useCookie);

        if ($handle === false) {
            die('Could not create handle');
        }

        return $this->execute($handle, $url);
    }

    public function post(string $url, array $postData, array $headers = [], bool $useCookie = false): string
    {
        $handle = $this->createHandle($url, $headers, $useCookie);

        if ($handle === false) {
            die('Could not create handle');
        }

        $curl_log = fopen("curl.txt", 'w');
        curl_setopt($handle, CURLOPT_STDERR, $curl_log);
        curl_setopt($handle, CURLINFO_HEADER_OUT, true);
        curl_setopt($handle, CURLOPT_POST, true);

        if (! empty($postData)) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        return $this->execute($handle, $url);
    }

    private function createHandle(string $url, array $headers = [], bool $useCookie = false)
    {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_VERBOSE, true);

        if (! empty($headers)) {
            if (isset($headers['Referer'])) {
                curl_setopt($handle, CURLOPT_REFERER, $headers['Referer']);
            }

            foreach ($headers as $key => $value) {
                if (! is_numeric($key)) {
                    $headers[] = sprintf('%s: %s', $key, $value);
                    unset($headers[$key]);
                }
            }

            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }

        if ($useCookie) {
            curl_setopt($handle, CURLOPT_COOKIEFILE, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        }

        return $handle;
    }

    /**
     * @param resource $handle
     * @param string $url
     * @return bool|string
     */
    private function execute($handle, string $url)
    {
        $output = curl_exec($handle);

        $info = curl_getinfo($handle);

        // Print the information
        echo sprintf('<pre>%s</pre>', print_r($info, true));

        curl_close($handle);

        if ($output === false) {
            die(sprintf('Requested url: %s\nError: "%s" - Code: %s', $url, curl_error($handle), curl_errno($handle)));
        }

        return $output;
    }
}
