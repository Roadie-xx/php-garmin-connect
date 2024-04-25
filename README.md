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
$curl = new Curl();

$client = new GarminClient($curl);

$client->login('Login name', 'Password');
````

This project is heavily based on: 
- [garmin-connect](https://github.com/Pythe1337N/garmin-connect): A powerful JavaScript library for connecting to Garmin Connect for sending and receiving health and workout data.
- [oauth-1.0a](https://github.com/ddo/oauth-1.0a): OAuth 1.0a Request Authorization for Node and Browser
- [tmhOAuth](https://github.com/themattharris/tmhOAuth) : An OAuth library written in PHP
- And so many online resources... Thank you DuckDuck and Google
