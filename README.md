# php-garmin-connect
Client connector to the Garmin Connect API in php

I needed to connect to Garmin for a [personal project](https://github.com/Roadie-xx/Runners-High) to analyze and show my running data.

Branches:
- Proof of concept (very messy)
- Write clean code, Extract classes, use namespaces
- Add tests
- Reuse key
- Add renew key
- Better error handling

## How to use
````php
<?php

use Roadie\Curl;
use Roadie\GarminClient;
use Roadie\Gpx;

$curl = new Curl();

$client = new GarminClient($curl);

$client->login('Login name', 'Password');

// Update latest runs
// Extract save
// Include import from db.php
// $client->getActivities(0, 10);

// $client->getGpx(15139210734);


$gpx = new Gpx();
$gpx->loadFile('activity_15139210734.gpx');
$gpx->enrich();
$gpx->analyze();

````

## Dependencies
This project has been tested with PHP 7.0+ and relies on `return type declaration` and `scalar type hints`, 
which are only allowed since PHP 7.0. 

Update: Typed properties are only allowed since PHP 7.4

This project is heavily based on: 
- [garth](https://github.com/matin/garth/blob/main/garth/sso.py): Garmin SSO auth + Connect Python client
- [garmin-connect](https://github.com/Pythe1337N/garmin-connect): A powerful JavaScript library for connecting to Garmin Connect for sending and receiving health and workout data.
- [oauth-1.0a](https://github.com/ddo/oauth-1.0a): OAuth 1.0a Request Authorization for Node and Browser
- [tmhOAuth](https://github.com/themattharris/tmhOAuth) : An OAuth library written in PHP
- And so many online resources... Thank you DuckDuck and Google
