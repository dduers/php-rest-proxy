# PHP Rest Proxy

## What can it do?

- Redirect API Request to target API
- Output response body with response headers
- Multiple API endpoints can be mounted with a name

## Installation

`composer require dduers/php-rest-proxy`

## Usage

```php
<?php
$_proxy = new \Dduers\PhpRestProxy\RestProxy();

$_proxy->mount('myapi', 'http://localhost:8080/v1');

$_proxy->exec();

// set response headers
foreach ($_proxy->getHeaders() as $name_ => $value_) 
    header($name_.': '.$value_[0]);

// output response body
echo $_proxy->getBody();
```

Or simpler:

```php
<?php
$_proxy = new \Dduers\PhpRestProxy\RestProxy();

$_proxy->mount('myapi', 'http://localhost:8080/v1');

$_proxy->exec();

// output response body with response headers
echo $_proxy->dump();
```

Or, since v1.0.2, pass client options to the constructor:


```php
require_once $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';

$_proxy = new \Dduers\PhpRestProxy\RestProxy([

    // for dev, don't verify ssl certs
    'verify' => false,

    // use HTTP/2
    'version' => 2
]);

$_proxy->mount('srocki', 'https://domain19.local/v1');
$_proxy->exec();
$_proxy->dump();
```

## Cross Site Cookies

- The target API can issue cookies for the domain, where the Rest Proxy is running
- Same Site attribute of such cookies must be set to `Strict`

```php
<?php
setcookie('TestCookie', 'The Cookie Value', [
    'expires' => time() + 60*60*24*30,
    // set path to avoid multiple cookies generated for each api route
    'path' => '/',
    // domain, here the rest proxy is running
    'domain' => '.domain17.local', 
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict' 
]);
```
