<?php

namespace Roadie;

class oAuth
{
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

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * See https://stackoverflow.com/questions/52785040/generating-oauth-1-signature-in-php
     */
    public function getSignature(array $request, array $parameters): string
    {
        $signatureBaseString = $this->getSignatureBaseString($request, $parameters);
        $signatureKey = $this->getSignatureKey($parameters);

        return base64_encode(hash_hmac('sha1', $signatureBaseString, $signatureKey, true));
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

    public function getSignatureKey(array $parameters): string
    {
        $signatureKey = rawurlencode($this->consumerSecret) . '&';

        if (isset($parameters['oauth_token_secret']) && $parameters['oauth_token_secret'] !== '') {
            $signatureKey .= rawurlencode($parameters['oauth_token_secret']);
        }

        return $signatureKey;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function mergeParameters(array $parameters): array
    {
        $oAuthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $this->timestamp,
            'oauth_version' => '1.0',
        ];

        $mergedArray = array_merge($oAuthParams, $parameters);
        ksort($mergedArray);

        return $mergedArray;
    }

    public function setNonce(string $nonce)
    {
        $this->nonce = $nonce;
    }

    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = (string) $timestamp;
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
}
