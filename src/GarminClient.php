<?php

declare(strict_types=1);

namespace Roadie;

class GarminClient
{
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

    /**
     * @var mixed
     */
    private $token;

    public function __construct(Curl $curl) {
        $this->curl = $curl;
    }

    public function login(string $username, string $password): void
    {
        $this->getLoginTicket($username, $password);
    }

    private function getLoginTicket(string $username, string $password): void
    {
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
        // $this->getActivities(0, 25);
    }

    public function getGpx($activityId): void
    {
        $url = sprintf(
           'https://connectapi.garmin.com/download-service/export/gpx/activity/%s?full=true',
           $activityId
        );

        $this->debug('Step 7: url', $url);

        $headers = [
            'Authorization' => sprintf('Bearer %s', $this->token['access_token']),
        ];

        $response = $this->curl->get($url, $headers);

        $this->debug('Step 7: Get gpx', $response);

        file_put_contents(sprintf('activity_%s.gpx', $activityId), $response);
    }

    // Todo Extract last two rows
    public function getActivities(int $start = 0, int $limit = 1)
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

    private function setCookie(): void
    {
        $params = [
            'clientId' => 'GarminConnect',
            'locale' => 'en',
            'service' => 'https://connect.garmin.com/modern',
        ];

        $url = 'https://sso.garmin.com/sso/embed?' . http_build_query($params);

        $response = $this->curl->get($url);

        $this->debug('Step 1: Set cookie', $response);
    }

    private function getCsrf(): void
    {
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

        if (preg_match($pattern, $response, $match)) {
            $this->csrfToken = $match[1];

            $this->debug('Found token', sprintf('Found token: "%s"', $this->csrfToken));
        } else {
            die('Could not find CSRF token');
        }
    }

    private function getTicket(string $username, string $password): void
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

    private function getOauth1Token(): void
    {
        $params = [
            'ticket' => $this->ticket,
            'login-url' => 'https://sso.garmin.com/sso/embed',
            'accepts-mfa-tokens' => 'true',
        ];

        $url = 'https://connectapi.garmin.com/oauth-service/oauth/preauthorized?' . http_build_query($params);
        // For debugging, this can be handy
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

    private function exchange(): void
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

    private function debug(string $title, string $response): void
    {
        $this->debugLines[] = sprintf('<dt>%s</dt><dd>%s</dd>', $title, nl2br(htmlentities($response)));
    }

    private function handleBLockedAccount(string $response): void
    {
        $pattern = '/Sorry, you have been blocked/';

        if (preg_match($pattern, $response)) {
            die('Login failed (Account Blocked)');
        }
    }

    private function handleLockedAccount(string $response): void
    {
        $pattern = '/var statuss*=s*"([^"]*)"/';

        if (preg_match($pattern, $response, $match)) {
            die(sprintf(
                'Login failed (AccountLocked), please open connect web page to unlock your account\n%s',
                $match[1]
            ));
        }
    }

    private function getAuthorizeDataAsHeader(string $url, array $params): array
    {
        // https://thegarth.s3.amazonaws.com/oauth_consumer.json
        $consumer = [
            'consumer_key' => 'fc3e99d2-118c-44b8-8ae3-03370dde24c0',
            'consumer_secret' => 'E08WAR897WEy2knn7aFBrvegVAf0AFdWBBF',
        ];

        $request = [
            'url' => $url,
            'method' => 'GET',
        ];

        $oAuth = new OAuth($consumer); // @ToDo refactor to dependency injection

        $signature = $oAuth->getSignature($request, $params);

        $oAuthData = $oAuth->mergeParameters($params);
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

    private function getAuthorizeData(array $params, array $token): array
    {
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

        $oAuth = new OAuth($consumer); // @ToDo Don't repeat yourself !

        $params = [
            'oauth_consumer_key' => $oAuth->getConsumerKey(),
            'oauth_nonce' => $oAuth->getNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $token['key'],
            'oauth_token_secret' => $token['secret'],
            'oauth_timestamp' => $oAuth->getTimestamp(),
            'oauth_version' => '1.0',
        ];

        $signature = $oAuth->getSignature($request, $params);

        $oAuthData = [
            'oauth_consumer_key' => $oAuth->getConsumerKey(),
            'oauth_nonce' => $oAuth->getNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $token['key'],
            'oauth_timestamp' => $oAuth->getTimestamp(),
            'oauth_version' => '1.0',
            'oauth_signature' => $signature,
        ];

        $this->debug('Step 5: oAuth Items', print_r($oAuthData, true));

        return $oAuthData;
    }
}
