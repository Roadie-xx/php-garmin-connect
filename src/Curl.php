<?php

namespace Roadie;

class Curl
{
    public function get(string $url, array $headers = [], bool $useCookie = false): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (! empty($headers)) {
            foreach ($headers as $key => $value) {
                if (! is_numeric($key)) {
                    $headers[] = sprintf('%s: %s', $key, $value);
                    unset($headers[$key]);
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($useCookie) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        }

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $output = curl_exec($ch);

        curl_close($ch);

        if ($output === false) {
            die(sprintf('Requested url: %s\nError: "%s" - Code: %s', $url, curl_error($ch), curl_errno($ch)));
        }

        return $output;
    }

    public function post(string $url, array $postData, array $headers = [], bool $useCookie = false): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);

        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        if ($useCookie) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
        }

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if (isset($headers['Referer'])) {
                curl_setopt($ch, CURLOPT_REFERER, $headers['Referer']);
            }
        }


        $curl_log = fopen("curl.txt", 'w');
        curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $output = curl_exec($ch);

        $info = curl_getinfo($ch);

        // Print the information
        echo sprintf('<pre>%s</pre>', print_r($info, true));

        curl_close($ch);

        if ($output === false) {
            die(sprintf('Requested url: %s\nError: "%s" - Code: %s', $url, curl_error($ch), curl_errno($ch)));
        }

        return $output;
    }
}
