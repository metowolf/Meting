<p align="center">
<img src="https://user-images.githubusercontent.com/2666735/30165599-36623bea-93a6-11e7-8956-1ddf99ce0e6f.png" alt="Meting">
</p>

<p align="center">
<a href="https://i-meto.com"><img alt="Author" src="https://img.shields.io/badge/Author-METO-blue.svg?style=flat-square"/></a>
<a href="https://packagist.org/packages/metowolf/Meting"><img alt="Version" src="https://img.shields.io/packagist/v/metowolf/Meting.svg?style=flat-square"/></a>
<a href="https://packagist.org/packages/metowolf/meting/stats"><img alt="Downloads" src="https://img.shields.io/packagist/dt/metowolf/Meting.svg?style=flat-square"/></a>
<a href="https://travis-ci.org/metowolf/Meting"><img alt="Travis" src="https://img.shields.io/travis/metowolf/Meting.svg?style=flat-square"></a>
<img alt="License" src="https://img.shields.io/packagist/l/metowolf/Meting.svg?style=flat-square"/>
</p>

 > :cake: Wow, such a powerful music API framework

## Introduction
A powerful music API framework to accelerate your development
 + **Elegant** - Easy to use, a standardized format for all music platforms.
 + **Lightweight** - A single-file library that's less than 51KB.
 + **Powerful** - Support various music platforms, including Tencent, NetEase, Xiami, KuGou, Baidu, Kuwo and more.
 + **Free** - Under MIT license, need I say more?

## Requirement
PHP 5.4+ and BCMath, Curl, OpenSSL extension installed.

## Installation
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

> **Note:** Meting requires [BCMath](http://php.net/manual/en/book.bc.php), [cURL](http://php.net/manual/en/book.curl.php) and [OpenSSL](http://php.net/manual/en/book.openssl.php) extension in order to work.


## Quick Start
```php
require 'vendor/autoload.php';
// require 'Meting.php';

use Metowolf\Meting;

// Initialize to netease API
$api = new Meting('netease');

// Use custom cookie (option)
// $api->cookie('paste your cookie');

// Get data
$data = $api->format(true)->search('Soldier', [
    'page' => 1,
    'limit' => 50
]);

echo $data;
// [{"id":35847388,"name":"Hello","artist":["Adele"],"album":"Hello","pic_id":"1407374890649284","url_id":35847388,"lyric_id":35847388,"source":"netease"},{"id":33211676,"name":"Hello","artist":["OMFG"],"album":"Hello",...

// Parse link
$data = $api->format(true)->url(35847388);

echo $data;
// {"url":"http:\/\/...","size":4729252,"br":128}
```

## More usage
 - [docs](https://github.com/metowolf/Meting/wiki)
 - [special for netease](https://github.com/metowolf/Meting/wiki/special-for-netease)

## Join the Discussion
 - [Telegram Group](https://t.me/adplayer)
 - [Official website](https://i-meto.com)

## Related Projects
 - [MoePlayer/Hermit-X](https://github.com/MoePlayer/Hermit-X)
 - [MoePlayer/APlayer-Typecho](https://github.com/MoePlayer/APlayer-Typecho)
 - [mengkunsoft/MKOnlineMusicPlayer](https://github.com/mengkunsoft/MKOnlineMusicPlayer)
 - [webjyh/WP-Player](https://github.com/webjyh/WP-Player)
 - [yiyungent/Meting4Net](https://github.com/yiyungent/Meting4Net)
 - [injahow/meting-api](https://github.com/injahow/meting-api)

## Author

**Meting** © [metowolf](https://github.com/metowolf), Released under the [MIT](./LICENSE) License.<br>

> Blog [@meto](https://i-meto.com) · GitHub [@metowolf](https://github.com/metowolf) · Twitter [@metowolf](https://twitter.com/metowolf)
