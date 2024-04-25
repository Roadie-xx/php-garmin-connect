<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

include '../src/garmin.php';

function dump($subject) {
    echo '<pre>';
    var_dump($subject);
    echo '</pre>';
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


// Based on js garmin-connect data
$consumer = [
    'consumer_key' => 'fc3e99d2-118c-44b8-8ae3-03370dde24c0',
    'consumer_secret' => 'E08WAR897WEy2knn7aFBrvegVAf0AFdWBBF',
];

$params = [
    'ticket' => 'ST-243247-qZ0dpXl37TZHa9yALjTD-cas',
    'login-url' => 'https://sso.garmin.com/sso/embed',
    'accepts-mfa-tokens' => 'true',
];

$request = [
    'method' => 'get',
    'url' => 'https://connectapi.garmin.com/oauth-service/oauth/preauthorized?ticket=ST-243247-qZ0dpXl37TZHa9yALjTD-cas&login-url=https%3A%2F%2Fsso.garmin.com%2Fsso%2Fembed&accepts-mfa-tokens=true',
];

$oAuth = new oAuth($consumer);

$oAuth->setNonce('71w1VCQTzVY9PIUcBbAwlfTnK8BU47YX');
$oAuth->setTimestamp(1713421457);



$calculatedSignature = $oAuth->getSignature($request, $params);
$expectedSignature = '1NgeHOBaTdusfO+HP8GT679gGaY=';


assertSame($expectedSignature, $calculatedSignature, 'Returned signature');



$params = [
    'file' => 'vacation.jpg',
    'size' => 'original',
];

$request = [
    'method' => 'get',
    'url' => 'http://photos.example.net/photos?' . http_build_query($params),
];

$parameters = [
    'oauth_token' => "nnch734d00sl2jdk",
    // 'oauth_signature' => "tR3%2BTy81lMeYAr%2FFid0kMTYa%2FWM%3D",
];

$consumer = [
    'consumer_key' => 'dpf43f3p2l4k3l03',
    'consumer_secret' => 'kd94hf93k423kf44',
];

$oAuth = new oAuth($consumer);

$oAuth->setNonce('kllo9940pd9333jh');
$oAuth->setTimestamp(1191242096);


$calculatedSignature = $oAuth->getSignatureBaseString($request, array_merge($parameters, $params));
$expectedSignature = 'GET&http%3A%2F%2Fphotos.example.net%2Fphotos&file%3Dvacation.jpg%26oauth_consumer_key%3Ddpf43f3p2l4k3l03%26oauth_nonce%3Dkllo9940pd9333jh%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1191242096%26oauth_token%3Dnnch734d00sl2jdk%26oauth_version%3D1.0%26size%3Doriginal';

assertSame($expectedSignature, $calculatedSignature, 'Returned BaseString');


$parameters['oauth_token_secret'] = 'pfkkdhi9sl3r4s00';

$calculatedSignatureKey = $oAuth->getSignatureKey(array_merge($parameters, $params));
$expectedSignatureKey = 'kd94hf93k423kf44&pfkkdhi9sl3r4s00';

assertSame($expectedSignatureKey, $calculatedSignatureKey, 'Returned signature key');


$calculatedSignature = $oAuth->getSignature($request, array_merge($parameters, $params));
$expectedSignature = 'tR3+Ty81lMeYAr/Fid0kMTYa/WM=';


assertSame($expectedSignature, $calculatedSignature, 'Returned signature');

// // https://github.com/thephpleague/oauth1-client/blob/master/tests/HmacSha1SignatureTest.php


// $consumer = [
//     'consumer_key' => '1434affd-4d69-4a1a-bace-cc5c6fe493bc',
//     'consumer_secret' => '932a216f-fb94-43b6-a2d2-e9c6b345cbea',
// ];

// $oAuth = new oAuth($consumer);

// $oAuth->setNonce('t62lMDp9DLwKZJJbZTpmSAhRINGBEOcF');
// $oAuth->setTimestamp(1484599369);
// $params = [
//     'foo' => 'bar',
//     'baz' => null
// ];

// $request = [
//     'method' => 'GET',
//     'url' => 'http://www.example.com/?qux=corge',
// ];


// $calculatedSignature = $oAuth->getSignature($request, $params, $consumer);
// $expectedSignature = 'A3Y7C1SUHXR1EBYIUlT3d6QT1cQ=';


// assertSame($expectedSignature, $calculatedSignature, 'Returned signature');



// // https://github.com/ddo/oauth-1.0a/blob/master/test/oauth_body_hash.js


// $consumer = [
//     'consumer_key' => '1434affd-4d69-4a1a-bace-cc5c6fe493bc',
//     'consumer_secret' => '932a216f-fb94-43b6-a2d2-e9c6b345cbea',
// ];

// $oAuth = new oAuth($consumer);

// $oAuth->setNonce('t62lMDp9DLwKZJJbZTpmSAhRINGBEOcF');
// $oAuth->setTimestamp(1484599369);
// $params = [
//     'foo' => 'bar',
// ];

// $request = [
//     'method' => 'POST',
//     'url' => 'http://canvas.docker/api/lti/accounts/1/tool_proxy',
// ];


// $calculatedSignature = $oAuth->getSignature($request, $params, $consumer);
// $expectedSignature = '1Q0U8yhK1bWYguRxlUDs9KHywOE=';


// assertSame($expectedSignature, $calculatedSignature, 'Returned signature');





// // https://github.com/J7mbo/twitter-api-php/blob/master/TwitterAPIExchange.php#L205
// // https://github.com/litl/rauth/blob/master/tests/test_oauth.py

// $consumer = [
//     'consumer_key' => '654',
//     'consumer_secret' => '456',
// ];

// $oAuth = new oAuth($consumer);

// $params = [
//     'foo' => 'bar',
// ];

// $request = [
//     'method' => 'get',
//     'url' => 'http://example.com/',
// ];


// $calculatedSignature = $oAuth->getSignature($request, $params, $consumer);
// $expectedSignature = 'cYzjVXCOk62KoYmJ+iCvcAcgfp8=';


// assertSame($expectedSignature, $calculatedSignature, 'Returned signature');


// def test_sign_with_data(self):
// # in the event a string is already UTF-8
// req_kwargs = {'data': {'foo': 'bar'}}
// method = 'POST'
// sig = HmacSha1Signature().sign(self.consumer_secret,
//                                self.access_token_secret,
//                                method,
//                                self.url,
//                                self.oauth_params,
//                                req_kwargs)
// self.assertEqual('JzmJUmqjdNYBJsJWbtQKXnc0W8w=',  sig)



