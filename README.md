<p align="center">
<img src="https://user-images.githubusercontent.com/2666735/30165599-36623bea-93a6-11e7-8956-1ddf99ce0e6f.png" alt="Meting">
</p>

## Meting
[![Build Status](https://img.shields.io/travis/metowolf/Meting.svg?style=flat-square)](https://travis-ci.org/metowolf/Meting)
[![Latest Stable Version](https://poser.pugx.org/metowolf/Meting/v/stable?format=flat-square)](https://packagist.org/packages/metowolf/Meting)
[![Total Downloads](https://poser.pugx.org/metowolf/meting/downloads?format=flat-square)](https://packagist.org/packages/metowolf/meting)
[![License](https://poser.pugx.org/metowolf/Meting/license?format=flat-square)](https://packagist.org/packages/metowolf/Meting)

 > :lollipop:Wow, such a powerful music API framework

## Introduction
A powerful music API framework to accelerate development

 + **Easy** - Easy to use, suppose format return.
 + **Light** - 42KB around with only one file.
 + **Powerful** - Suppose various webserver, include netease, tencent, xiami, kugou, baidu and more.
 + **Free** - Under MIT license, you can use it anywhere if you want.

## Requirement
PHP 5.4+ and Curl, OpenSSL extension installed

## Install
Require this package, with [Composer](https://getcomposer.org), in the root directory of your project.

```bash
$ composer require metowolf/meting
```

Then you can import the class into your application:

```php
use Metowolf\Meting;

$api = new Meting('netease');

$data = $api->format(true)->search('Soldier');
```

> **Note:** Meting requires the [cURL](http://php.net/manual/en/book.curl.php) and [OpenSSL](http://php.net/manual/en/book.openssl.php) extension in order to work.


## Quick Start
```php
require 'vendor/autoload.php';
// require 'Meting.php';

use Metowolf\Meting;

// Initialize to netease API
$api = new Meting('netease');

// Use custom cookie (option)
$api->cookie('paste your cookie');

// Get data
$data = $api->format(true)->search('Soldier');

// Enjoy
echo $data;
```

## More usage
 - [wiki/docs](https://github.com/metowolf/Meting/wiki)

## Join the Discussion
 - [Telegram Group](https://t.me/adplayer)
 - [Official website](https://i-meto.com)

## Related Projects
 - [Hermit-X (Wordpress)](https://github.com/MoePlayer/Hermit-X)
 - [APlayer-Typecho](https://github.com/MoePlayer/APlayer-Typecho)
 - [MKOnlineMusicPlayer](https://github.com/mengkunsoft/MKOnlineMusicPlayer)
 - [WP-Player](https://github.com/webjyh/WP-Player)

## Author

**Meting** © [metowolf](https://github.com/metowolf), Released under the [MIT](./LICENSE) License.<br>

> Blog [@meto](https://i-meto.com) · GitHub [@metowolf](https://github.com/metowolf) · Twitter [@metowolf](https://twitter.com/metowolf) · Telegram Channel [@metooooo](https://t.me/metooooo)
