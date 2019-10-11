![Laravel logo](http://laravel.com/assets/img/laravel-logo.png) Laravel Config Value Parser
===========================================================================================
<a name="top" id="top"></a>
[![GitHub Version](https://img.shields.io/github/tag/laradic/config.svg?style=flat-square&label=version)](http://badge.fury.io/gh/laradic%2Fconfig)
[![Total Downloads](https://img.shields.io/packagist/dt/laradic/config.svg?style=flat-square)](https://packagist.org/packages/laradic/config)
[![License](http://img.shields.io/badge/license-MIT-ff69b4.svg?style=flat-square)](http://radic.mit-license.org)

## Version 2.0

### Introduction
`laradic/config` integrates Symfony's ExpressionLanguage into your laravel config. This enables developers to create complex self-resolving configuration values. A few exmples:
```php
# config/app.php
use Illuminate\Support\ServiceProvider;return [
    'app' => [
        'author' => 'Me, Myself and I',
        'license' => 'MIT',
        'year' => '{{ date("YYYY") }}',
        'copyright' => 'Copyright {{ app.author }} {{ app.year }} - All rights reserverd'
    ]   
];


# SomeServicePRovider.php
class SomeServicePRovider extends ServiceProvider {
    public function register(){
        /** @var \Illuminate\Contracts\Config\Repository $conifg */
        $config = $this->app->config;
        $config->raw('app.copyright'); //> 'Copyright {{ app.author }} {{ app.year }} - All rights reserverd'
        $config->get('app.copyright'); //> 'Copyright Me, Myself and I 2019 - All rights reserverd'
        $config->get('app');           //> array
    }
}
```

### Installation
1. `composer require laradic/config`
2. Add the `Laradic\Config\ParsesConfigData` trait to your config repository
3. Use the `Laradic\Config\ConfigBootstrapper` class to integrate the config value parser like so:
```php
# bootstrap/app.php
$app = new Illuminate\Foundation\Application(
    realpath(__DIR__ . '/../')
);
Laradic\Config\ConfigBootstrapper::make($app)->bootstrap();
```

#### Customised installation
```php
Laradic\Config\ConfigBootstrapper::make($app)->bootstrap(); // uses the default repository located in \Laradic\Config\Repository
```

  
<a name="copyright"></a>
### Copyright/License
Copyright 2015 [Robin Radic](https://github.com/RobinRadic) - [MIT Licensed](http://radic.mit-license.org)
