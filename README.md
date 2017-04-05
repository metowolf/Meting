
## 改动说明
+ url解析的无封装支持
+ 扩展网易云封装
+ 修复网易云音乐的ape解析
+ 支持虾米音乐320k解析

## TODO
+ 无损格式更宽泛的支持
+ 统一的封装结构
+ ~~虾米音乐320K支持~~
+ 腾讯音乐无损支持
+ 优化大量请求

## 以下为原版介绍


>![](http://ww2.sinaimg.cn/large/a15b4afegw1fbg1l7wn09j20fw05gq34)
>## Meting
>[![Latest Stable Version](https://poser.pugx.org/metowolf/Meting/v/stable)](https://packagist.org/packages/metowolf/Meting)
>[![License](https://poser.pugx.org/metowolf/Meting/license)](https://packagist.org/packages/metowolf/Meting)

> > :lollipop:Wow, such a powerful music API framework

>## Introduction
>A powerful music API framework to accelerate development

>+ **Easy** - Easy to use, suppose format return.
>+ **Light** - 38KB around with only one file.
>+ **Powerful** - Suppose various webserver, include netease, tencent, xiami, kugou, baidu, >kuwo and more.
>+ **Free** - Under MIT license, you can use it anywhere if you want.

>## Requirement
>PHP 5.3+ and Curl extension installed

>## Get Started

>### Install via composer
>Add Meting to composer.json configuration file.
>```
>$ composer require metowolf/meting
>```
>And update the composer
>```
>$ composer update
>```

>### Install via require
>```php
>// If you installed via composer, just use this code to requrie autoloader on the top of your projects.
>require 'vendor/autoload.php';
>```


>// Else use require file
>// require 'Meting.php';

>// Initialize to netease API
>$API = new Meting('netease');

>// Enjoy
>$data = $API->format(true)->search('Soldier');
>```
>
>```


>## More usage
>[wiki/docs](https://github.com/metowolf/Meting/wiki)

>## Related Projects
>- [Hermit-X (Wordpress)](https://github.com/liwanglin12/Hermit-X)
>- [Meting for Typecho](https://github.com/metowolf/Meting-Typecho-Plugin)

>## License
>Meting is under the MIT license.

>## Links
>Official website: https://i-meto.com  
>Demo: https://music.i-meto.com
