# [<img alt="Vanio" src="http://www.vanio.cz/img/vanio-logo.png" width="130" align="top">](http://www.vanio.cz) API Bundle

[![Build Status](https://travis-ci.org/vaniocz/vanio-api-bundle.svg?branch=master)](https://travis-ci.org/vaniocz/vanio-api-bundle)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/vanio-api-bundle/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/vanio-api-bundle?branch=master)
![PHP7.2](https://img.shields.io/badge/php-7.2-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/vanio-api-bundle/license)](https://github.com/vaniocz/vanio-api-bundle/blob/master/LICENSE)

A Symfony3 Bundle providing some additional features for API development.

# Installation
Installation can be done as usually using composer.
`composer require vanio/vanio-api-bundle`

Next step is to register this bundle inside your `AppKernel`.
```php
// app/AppKernel.php
// ...

class AppKernel extends Kernel
{
    // ...

    public function registerBundles(): array
    {
        $bundles = [
            // ...
            new Vanio\ApiBundle\VanioApiBundle,
        ];

        // ...
    }
}
```
