# chubbyphp-session-storageless

[![Build Status](https://api.travis-ci.org/chubbyphp/chubbyphp-session-storageless.png?branch=master)](https://travis-ci.org/chubbyphp/chubbyphp-session-storageless)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/?branch=master)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Latest Unstable Version](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/v/unstable)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)

## Description

[psr7-sessions/storageless][2] persistence adapter for [zendframework/zend-expressive-session][3].

## Requirements

* php: ^7.2
* [psr7-sessions/storageless][2]: ^5.0
* [zendframework/zend-expressive-session][3]: ^1.2

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-session-storageless][1].

```sh
composer require chubbyphp/chubbyphp-session-storageless "^1.0"
```

## Usage

### With zend-stratigility using symmetric key (hmac)

#### Generate key

```sh
openssl rand -base64 32
```

#### Code

```php
<?php

declare(strict_types=1);

namespace App;

use Chubbyphp\Session\Storageless\PSR7StoragelessSessionPersistence;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;
use Zend\Expressive\Session\SessionMiddleware as ZendSessionMiddleware;
use Zend\Stratigility\MiddlewarePipe;

$middlewarePipe = new MiddlewarePipe();
$middlewarePipe->pipe(PSR7SessionMiddleware::fromSymmetricKeyDefaults(
    'JeIn7GmQJRkM4dP3T5ZfVcHk7rxyVoMzR1DptTIquFY=',
    1200
));
$middlewarePipe->pipe(new ZendSessionMiddleware(new PSR7StoragelessSessionPersistence()));
```

### With zend-stratigility using asymmetric key (rsa)

#### Generate key

```sh
openssl genrsa -out signatureKey 512
openssl rsa -in signatureKey -out verificationKey -outform PEM -pubout
```

#### Code

```php
<?php

declare(strict_types=1);

namespace App;

use Chubbyphp\Session\Storageless\PSR7StoragelessSessionPersistence;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;
use Zend\Expressive\Session\SessionMiddleware as ZendSessionMiddleware;
use Zend\Stratigility\MiddlewarePipe;

$middlewarePipe = new MiddlewarePipe();
$middlewarePipe->pipe(PSR7SessionMiddleware::fromAsymmetricKeyDefaults(
    '-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----',
        '-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----',
    1200
));
$middlewarePipe->pipe(new ZendSessionMiddleware(new PSR7StoragelessSessionPersistence()));
```

## Copyright

Dominik Zogg 2019

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless
[2]: https://github.com/psr7-sessions/storageless
[3]: https://github.com/zendframework/zend-expressive-session
