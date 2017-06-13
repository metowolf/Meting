![](http://ww2.sinaimg.cn/large/a15b4afegw1fbg1l7wn09j20fw05gq34)

## Meting
[![Latest Stable Version](https://poser.pugx.org/metowolf/Meting/v/stable)](https://packagist.org/packages/metowolf/Meting)
[![License](https://poser.pugx.org/metowolf/Meting/license)](https://packagist.org/packages/metowolf/Meting)

 > :lollipop:Wow, such a powerful music API framework

## Introduction
A powerful music API framework to accelerate development

 + **Easy** - Easy to use, suppose format return.
 + **Light** - 40KB around with only one file.
 + **Powerful** - Suppose various webserver, include netease, tencent, xiami, kugou, baidu and more.
 + **Free** - Under MIT license, you can use it anywhere if you want.

## Requirement
PHP 5.4+ and Curl, OpenSSL extension installed

## Get Started

### Install via composer
Add Meting to composer.json configuration file.
```
$ composer require metowolf/meting
```
And update the composer
```
$ composer update
```

### Install via require
```php
// If you installed via composer, just use this code to requrie autoloader on the top of your projects.
require 'vendor/autoload.php';

// Or require file
// require 'Meting.php';

// Using Metowolf namespace
use Metowolf\Meting;

// Initialize to netease API
$API = new Meting('netease');

// Use custom cookie*
$API->cookie('paste your cookie');

// Get data
$data = $API->format(true)->search('Soldier');

// Enjoy
echo $data;

```

## More usage
[wiki/docs](https://github.com/metowolf/Meting/wiki)

## Related Projects
 - [Hermit-X (Wordpress)](https://github.com/liwanglin12/Hermit-X)
 - [Meting for Typecho](https://github.com/metowolf/Meting-Typecho-Plugin)
 - [MKOnlineMusicPlayer](https://github.com/mengkunsoft/MKOnlineMusicPlayer)
 - [WP-Player](https://github.com/webjyh/WP-Player)

## License
Meting is under the MIT license.

## Links
Official website: https://i-meto.com  
Demo: https://music.i-meto.com
