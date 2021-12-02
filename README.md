# PHP Rest Proxy

## What can it do?

- Redirect API Request to target API
- Output response body with response headers

## Installation

`composer require dduers/php-rest-proxy`

## Usage

```php
<?php
$_proxy = new \Dduers\PhpRestProxy\RestProxy();

// set response headers
foreach ($_proxy->getHeaders() as $name_ => $value_) 
    header($name_.': '.$value_[0]);

// output response body
echo $_proxy->getBody();
```

## Cross Site Cookies

- The target API can issue cookies for the domain, where the Rest Proxy is running
