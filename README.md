# Meting

![](http://ww2.sinaimg.cn/large/a15b4afegw1fbg1l7wn09j20fw05gq34)

 > :lollipop:Wow, such a powerful music API framework

## Introduction
A powerful music API framework to accelerate development

 + **Easy** - Easy to use, suppose format return.
 + **Light** - 32KB around with only one file.
 + **Powerful** - Suppose various webserver, include netease, tencent, xiami, kugou, baidu and more.
 + **Free** - Under MIT license, you can use it anywhere if you want.

## Get Started

### Install via composer
```
$ composer require metowolf/meting
```

### Install via require
```php
// if you just download the Meting.php into directory, require it with the correct path.
require_once 'Meting.php';
```

### Basic usage
```php
// Initialize to netease API
$API = new Meting('netease');

// Enjoy
$data = $API->format(true)->search('Soldier');
```

## Usage
 - [x] search songs
 - [x] get playlist detail
 - [x] get album detail
 - [x] get song detail
 - [x] get artists detail
 - [x] get url
 - [x] get pic
 - [x] get lyric
 - [x] raw data / format data

## Demo
```bash
$ git clone https://github.com/metowolf/NeteaseCloudMusicApi.git
$ cd Meting
$ php -S 127.0.0.1:8080
```
then open http://127.0.0.1:8080/demo/simple-test in Browser

## Related Projects
 - [Hermit-X (Wordpress)](https://github.com/liwanglin12/Hermit-X)
 - [Meting for Typecho](https://github.com/metowolf/Meting-Typecho-Plugin)

## License
Meting is under the MIT license.

## Links
Official website: https://i-meto.com  
Demo: https://music.i-meto.com
