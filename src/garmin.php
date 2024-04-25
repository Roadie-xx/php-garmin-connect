<?php

// Based on
// https://github.com/matin/garth/blob/main/garth/sso.py
// https://github.com/Pythe1337N/garmin-connect/blob/master/src/common/HttpClient.ts
// https://github.com/Pythe1337N/garmin-connect/blob/master/src/garmin/UrlClass.ts
// https://github.com/themattharris/tmhOAuth/blob/master/tmhOAuth.php


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

class GarminClient {
    /**
     * @var string
     */
    private $csrfToken;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var bool
     */
    private $debug = true;

    /**
     * @var array
     */
    private $debugLines = [];

    /**
     * @var string
     */
    private $oauth1Token;

    /**
     * @var string
     */
    private $ticket;

    public function __construct(Curl $curl) {
        $this->curl = $curl;
    }

    public function login(string $username, string $password)
    {
        $this->getLoginTicket($username, $password);
    }

    private function getLoginTicket(string $username, string $password) {
        // Step 1
        $this->setCookie();

        // Step 2
        $this->getCsrf();

        // Step 3
        $this->getTicket($username, $password);

        // Step 4: Oauth1
        $this->getOauth1Token();

        // Step 5: Oauth2
        $this->exchange();

        // Step 6: Get activities
        $this->getActivities(0, 25);
    }

    private function getActivities(int $start = 0, int $limit = 1)
    {
        $params = [
            'start' => $start,
            'limit' => $limit,
            //activityType, subActivityType
        ];

        $url = 'https://connectapi.garmin.com/activitylist-service/activities/search/activities?' . http_build_query($params);

        $this->debug('Step 6: Token', print_r($this->token, true));
        $this->debug('Step 6: access_token', print_r($this->token['access_token'], true));

        $headers = [
            'Authorization' => sprintf('Bearer %s', $this->token['access_token']),
        ];

        $response = $this->curl->get($url, $headers);

        $this->debug('Step 6: Get Activity', $response);

        $result = json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
        file_put_contents("activities_temp.json", $result);
    }

    private function setCookie() {
        $params = [
            'clientId' => 'GarminConnect',
            'locale' => 'en',
            'service' => 'https://connect.garmin.com/modern',
        ];

        $url = 'https://sso.garmin.com/sso/embed?' . http_build_query($params);

        $response = $this->curl->get($url);

        $this->debug('Step 1: Set cookie', $response);
    }

    private function getCsrf() {
        $params = [
            'id' => 'gauth-widget',
            'embedWidget' => true,
            'locale' => 'en',
            'gauthHost' => 'https://sso.garmin.com/sso/embed',
        ];

        $url = 'https://sso.garmin.com/sso/signin?' . http_build_query($params);

        $response = $this->curl->get($url, [], true);

        $this->debug('Step 2: Get csrf', $response);

        $pattern = '/name="_csrf"\\s+value="(.+?)"/';

        if (preg_match($pattern, $response, $match)){
            $this->csrfToken = $match[1];

            $this->debug('Found token', sprintf('Found token: "%s"', $this->csrfToken));
        } else {
            die('Could not find CSRF token');
        }
    }

    private function getTicket(string $username, string $password)
    {
        $params = [
            'id' => 'gauth-widget',
            'embedWidget' => 'true',
            'clientId' => 'GarminConnect',
            'locale' => 'en',
            'gauthHost' => 'https://sso.garmin.com/sso/embed',
            'service' => 'https://sso.garmin.com/sso/embed',
            'source' => 'https://sso.garmin.com/sso/embed',
            'redirectAfterAccountLoginUrl' => 'https://sso.garmin.com/sso/embed',
            'redirectAfterAccountCreationUrl' => 'https://sso.garmin.com/sso/embed',
        ];

        $url = 'https://sso.garmin.com/sso/signin?' . http_build_query($params);

        $postData = [
            'username' => $username,
            'password' => $password,
            'embed' => 'true',
            '_csrf' => $this->csrfToken,
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Dnt' => 1,
            'Origin' => 'https://sso.garmin.com',
            'Referer' => 'https://sso.garmin.com/sso/signin',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        ];

        $response = $this->curl->post($url, $postData, $headers, true);

        $this->debug('Step 3: Get ticket', $response);

        $this->handleBLockedAccount($response);
        $this->handleLockedAccount($response);

        $pattern = '/ticket=([^"]+)"/';

        if (preg_match($pattern, $response, $match)){
            $this->ticket = $match[1];

            $this->debug('Found ticket', sprintf('Found ticket: "%s"', $this->ticket));
        } else {
            die('Login failed (Ticket not found), please check username and password');
        }
    }

    private function getOauth1Token() {
        $params = [
            'ticket' => $this->ticket,
            'login-url' => 'https://sso.garmin.com/sso/embed',
            'accepts-mfa-tokens' => 'true',
        ];

        $url = 'https://connectapi.garmin.com/oauth-service/oauth/preauthorized?' . http_build_query($params);
// $url = 'https://webhook.site/4babb031-1c59-4554-bc58-03244bb3b435?' . http_build_query($params);
        $headers = $this->getAuthorizeDataAsHeader($url, $params);
        $headers['User-Agent'] = 'com.garmin.android.apps.connectmobile';

        $this->debug('Step 4: url', $url);
        $this->debug('Step 4: Headers', print_r($headers, true));

        $response = $this->curl->get($url, $headers, true);

        $this->debug('Step 4: Get oAuth Token', $response);

        parse_str($response, $this->oauth1Token);

        $this->debug('oAuth Token',sprintf('Received token: "%s"', print_r($this->oauth1Token, true)));
    }


    private function exchange()
    {
        $token = [
            'key' => $this->oauth1Token['oauth_token'],
            'secret' => $this->oauth1Token['oauth_token_secret'],
        ];

        $data = [
            'url' => 'https://connectapi.garmin.com/oauth-service/oauth/exchange/user/2.0',
            'method' => 'POST',
            'data' => null,
        ];

        // $url = 'https://connectapi.garmin.com/oauth-service/oauth/exchange/user/2.0?' . http_build_query($data);

        $authData = $this->getAuthorizeData($data, $token);

        $this->debug('Step 5: Exchange oAuth Data', print_r($authData, true));

        $headers = [
            'User-Agent' => 'com.garmin.android.apps.connectmobile',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $url = 'https://connectapi.garmin.com/oauth-service/oauth/exchange/user/2.0?' . http_build_query($authData);

        $response = $this->curl->post($url, [], $headers);

        $this->debug('Step 5: Exchange oAuth Token', $response);

        $this->token = json_decode($response, true);
    }

    public function __destruct()
    {
        if ($this->debug) {
            echo sprintf('<dl>%s</dl>', implode(PHP_EOL, $this->debugLines));
        }

    }

    private function debug(string $title, string $response)
    {
        $this->debugLines[] = sprintf('<dt>%s</dt><dd>%s</dd>', $title, nl2br(htmlentities($response)));
    }


    private function handleBLockedAccount(string $response)
    {
        $pattern = '/Sorry, you have been blocked/';

        if (preg_match($pattern, $response)) {
//            echo $response;
            die('Login failed (Account Blocked)');
        }
    }

    private function handleLockedAccount(string $response)
    {
        $pattern = '/var statuss*=s*"([^"]*)"/';

        if (preg_match($pattern, $response, $match)) {
            die(sprintf(
                'Login failed (AccountLocked), please open connect web page to unlock your account\n%s',
                $match[1]
            ));
        }
    }

    private function getAuthorizeDataAsHeader(string $url, array $params) {
        // https://thegarth.s3.amazonaws.com/oauth_consumer.json
        $consumer = [
            'consumer_key' => 'fc3e99d2-118c-44b8-8ae3-03370dde24c0',
            'consumer_secret' => 'E08WAR897WEy2knn7aFBrvegVAf0AFdWBBF',
        ];

        $request = [
            'url' => $url,
            'method' => 'GET',
        ];

        $oAuth = new OAuth($consumer);

        $signature = $oAuth->getSignature($request, $params);

        $oAuthData = $oAuth->mergeParameters($params, false);
        $oAuthData['oauth_signature'] = rawurlencode($signature);

        $oAuthDataItems = array_map(function ($key, $value) {
            if (strpos($key, 'oauth_') !== 0) {
                return false;
            }
            return sprintf('%s="%s"', $key, $value);
        }, array_keys($oAuthData), $oAuthData);

        $oAuthDataItems = array_filter($oAuthDataItems); // Remove false entries

        sort($oAuthDataItems);

        $this->debug('Step 4: oAuth Items', print_r($oAuthDataItems, true));


        return ['Authorization' => 'OAuth ' .  implode(', ', $oAuthDataItems)];
    }


    private function getAuthorizeData(array $params, array $token) {
        // https://thegarth.s3.amazonaws.com/oauth_consumer.json
        $consumer = [
            'consumer_key' => 'fc3e99d2-118c-44b8-8ae3-03370dde24c0',
            'consumer_secret' => 'E08WAR897WEy2knn7aFBrvegVAf0AFdWBBF',
        ];

        $request = [
            'url' => $params['url'],
            'method' => $params['method'],
            'data' => [],
        ];


        $oAuth = new OAuth($consumer);

        // $params['oauth_token'] = $token['key'];
        // $params['oauth_token_secret'] = $token['secret'];
        $params = [
            // 'oauth_token' => $token['key'],
            'oauth_consumer_key' => $oAuth->getConsumerKey(),
            'oauth_nonce' => $oAuth->getNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $token['key'],
            'oauth_token_secret' => $token['secret'],
            'oauth_timestamp' => $oAuth->getTimestamp(),
            'oauth_version' => '1.0',
            // 'oauth_token_secret' => $token['secret'],
        ];

        $signature = $oAuth->getSignature($request, $params);

        // $oAuthData = $oAuth->mergeParameters($params, false);
        // $oAuthData['oauth_signature'] = rawurlencode($signature);



        $oAuthData = [
            'oauth_consumer_key' => $oAuth->getConsumerKey(),
            'oauth_nonce' => $oAuth->getNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $token['key'],
            // 'oauth_token_secret' => $token['secret'],
            'oauth_timestamp' => $oAuth->getTimestamp(),
            'oauth_version' => '1.0',
            // 'oauth_signature' => rawurlencode($signature),
            'oauth_signature' => $signature,
        ];


        // $oAuthData = array_filter($oAuthData, function ($value, $key) {
        //     return strpos($key, 'oauth_') === 0;
        // }, ARRAY_FILTER_USE_BOTH); 


        $this->debug('Step 5: oAuth Items', print_r($oAuthData, true));

        return $oAuthData;
    }

}

class Curl {
    public function get(string $url, array $headers = [], bool $useCookie = false): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie.txt');
//        $http_headers = array(
//            'Host: www.google.ca',
//            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2',
//            'Accept: */*',
//            'Accept-Language: en-us,en;q=0.5',
//            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
//            'Connection: keep-alive'
//        );
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');


//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

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

//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

//        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

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
        // curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $output = curl_exec($ch);

// Get information about the request
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



class oAuth {
    /**
     * @var mixed
     */
    private $consumerKey;
    /**
     * @var mixed
     */
    private $consumerSecret;

    /**
     * @var string
     */
    private $nonce;
    /**
     * @var string
     */
    private $timestamp;

    public function __construct(array $consumer)
    {
        $this->consumerKey = $consumer['consumer_key'];
        $this->consumerSecret = $consumer['consumer_secret'];

        $this->nonce = $this->createNonce(32);
        $this->timestamp = (string) time();
    }

    // See https://stackoverflow.com/questions/52785040/generating-oauth-1-signature-in-php
    public function getSignature(array $request, array $parameters): string
    {
        $signatureBaseString = $this->getSignatureBaseString($request, $parameters);
        $signatureKey = $this->getSignatureKey($parameters);
        echo '<pre>';
        var_dump($signatureBaseString);
        var_dump($signatureKey);
        echo '</pre>';

        return base64_encode(hash_hmac('sha1', $signatureBaseString, $signatureKey, true));
    }

    public function getSignatureKey(array $parameters): string
    {
        // $parameters = $this->mergeParameters($parameters);
        $signatureKey = rawurlencode($this->consumerSecret) . '&';

        if (isset($parameters['oauth_token_secret']) && $parameters['oauth_token_secret'] !== '') {
            $signatureKey .= rawurlencode($parameters['oauth_token_secret']);
            // $signatureKey .= $parameters['oauth_token_secret'];
        }

        return $signatureKey;
    }

    public function getSignatureBaseString(array $request, array $parameters): string
    {
        $parameters = $this->mergeParameters($parameters);

        $method = strtoupper($request['method']);
        $url = explode('?', $request['url'])[0];

        unset($parameters['oauth_signature']);
        unset($parameters['oauth_token_secret']);

        ksort($parameters);

        $parametersItems = array_map(
            function ($key, $value) {
                return sprintf('%s=%s', rawurlencode($key), rawurlencode($value));
            }, 
            array_keys($parameters), 
            $parameters
        );

        return sprintf(
            '%s&%s&%s',
            $method,
            rawurlencode($url),
            rawurlencode(implode('&', $parametersItems))
        );
    }

    public function setNonce(string $nonce)
    {
        $this->nonce = $nonce;
    }

    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = (string) $timestamp;
    }

    public function mergeParameters(array $parameters, bool $sort = true): array
    {
        $oAuthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            //'oauth_token' => $oauth_access_token,
            'oauth_timestamp' => $this->timestamp,
            'oauth_version' => '1.0',
        ];

        $mergedArray = array_merge($oAuthParams, $parameters);
//        if ($sort === true) {
            ksort($mergedArray);
//        }

        return $mergedArray;
    }

    private function createNonce(int $length): string
    {
        $wordCharacters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $wordCharacters[rand(0, strlen($wordCharacters) - 1)];
        }

        return $result;
    }
    
    public function getNonce()
    {
        return $this->nonce;
    }    
    public function getTimestamp()
    {
        return $this->timestamp;
    }    
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    private function normalizeParameters()
    {

    }
}

function assertSame($expected, $actual, $message = '') {

    if ($expected === $actual) {
        echo sprintf('<h4 style="color: green;">%s</h4>', $message);
        echo sprintf(
            '<div style="font-family: monospace">Value <pre>%s</pre></div>',
            $actual
        );
    } else {
        echo sprintf('<h4 style="color: red;">%s</h4>', $message);
        echo sprintf(
            '<div style="font-family: monospace">Expected <pre>%s</pre><br>Actual <pre>%s</pre></div>',
            $expected,
            $actual
        );
    }
}

