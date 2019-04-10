# chubbyphp-session-storageless

[![Build Status](https://api.travis-ci.org/chubbyphp/chubbyphp-session-storageless.png?branch=master)](https://travis-ci.org/chubbyphp/chubbyphp-session-storageless)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-session-storageless/?branch=master)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)
[![Latest Unstable Version](https://poser.pugx.org/chubbyphp/chubbyphp-session-storageless/v/unstable)](https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless)

## Description

Storageless persistence adapter for zend-expressive-session.

**Important**: If you search for a standalone solution check the original [psr7-sessions/storageless][2] which is
developed by [Marco Pivetta][3], [Jefersson Nathan][4] and [Luís Otávio Cobucci Oblonczyk][5].

## Requirements

* php: ^7.2
* dflydev/fig-cookies: ^2.0
* lcobucci/jwt: ^3.2
* lcobucci/clock: ^1.2
* zendframework/zend-expressive-session: ^1.2

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-session-storageless][1].

```sh
composer require chubbyphp/chubbyphp-session-storageless "^1.0"
```

## Usage

### Symmetric key (hmac)

#### Generate

```sh
openssl rand -base64 32
```

#### Use middleware with storageless

```php
<?php

declare(strict_types=1);

namespace App;

use Chubbyphp\Session\Storageless\StoragelessSessionPersistence;
use Zend\Expressive\Session\SessionMiddleware;

$middleware = new SessionMiddleware(
    StoragelessSessionPersistence::fromSymmetricKeyDefaults(
        'JeIn7GmQJRkM4dP3T5ZfVcHk7rxyVoMzR1DptTIquFY='
    ),
    1200
);
```


### Asymmetric keys (rsa)

#### Generate

```sh
openssl genrsa -out signatureKey 512
openssl rsa -in signatureKey -out verificationKey -outform PEM -pubout
```

#### Use middleware with storageless

```php
<?php

declare(strict_types=1);

namespace App;

use Chubbyphp\Session\Storageless\StoragelessSessionPersistence;
use Zend\Expressive\Session\SessionMiddleware;

$signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

$verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

$middleware = new SessionMiddleware(
    StoragelessSessionPersistence::fromAsymmetricKeyDefaults(
        $signatureKey
        $verificationKey,
        1200
   )
);
```

## Copyright

Dominik Zogg 2019

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-session-storageless
[2]: https://github.com/psr7-sessions/storageless
[3]: https://github.com/Ocramius
[4]: https://github.com/malukenho
[5]: https://github.com/lcobucci
