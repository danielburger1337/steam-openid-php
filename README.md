[![PHPUnit](https://github.com/danielburger1337/steam-openid-php/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danielburger1337/steam-openid-php/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/danielburger1337/steam-openid-php/actions/workflows/phpstan.yml/badge.svg)](https://github.com/danielburger1337/steam-openid-php/actions/workflows/phpstan.yml)

# Steam OpenID Authentication Library

A simple, modern and modular OpenID client library implementation for Steam.

## Why this library?

This library aims to make the proccess of OpenID authentication with Steam as painless as possible.

As you may know, there are already a couple of libraries that do more or less the exact same thing as this library:

-   https://github.com/xPaw/SteamOpenID.php
-   https://github.com/SmItH197/SteamAuthentication

But as you may know or will see, those libraries are exactly what people hate about PHP. They do not use any real coding or [php-fig (psr)](https://www.php-fig.org/) standards, are not modular (they require cURL) and are generally a pain to work with because they do not throw exceptions.

## How To Use

```php
<?php declare(strict_types=1);

use danielburger1337\SteamOpenId\SteamOpenID;

// RECOMMENDATION: register this in your psr-11 container.
$openId = new SteamOpenID(
    // REQUIRED: your OpenID "realm" (your host)
    'http://localhost:5000',
    // REQUIRED: your OpenID "return_to" URI (your callback URI)
    'http://localhost:5000/Callback.php',
    // OPTIONAL: your psr-18 implementation (defaults to symfony/http-client)
    new GuzzleHttp\Client(),
    // OPTIONAL: your psr-17 implementation (default to nyholm/psr7)
    new Nyholm\Psr7\Factory\Psr17Factory()
);

// start the authentication flow by redirecting the user to Steam.
header('Location: ' . $openId->constructCheckIdSetupUri());

// ... in Callback.php (or your callback controller method)

use danielburger1337\SteamOpenId\Exception\ExceptionInterface;

// When steam redirects the user back to your "return_to" URI,
// verify the provided parameters in the query string.

try {
    // This method returns the users 64-bit SteamID.
    $steamId = $openId->verifyCallback($_GET);
} catch (ExceptionInterface $e) {
    exit(var_dump('Failed to verify steam authentication :('));
}

// we also provide a very simple ISteamUser::GetPlayerSummaries implementation
// https://developer.valvesoftware.com/wiki/Steam_Web_API#GetPlayerSummaries_.28v0001.29
$user = $openId->fetchUserInfo($steamId, $YOUR_STEAM_WEB_API_KEY);
```

### Terms & Conditions

Before using this library, please read and accept Valve's terms and conditions [here](https://steamcommunity.com/dev).
