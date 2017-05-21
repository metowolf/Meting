<?php
/**
 * Created by PhpStorm.
 * User: Dj-CLAMP
 * Date: 2017/4/12
 * Time: 下午6:31
 */

require __DIR__.'./../vendor/autoload.php';

$api = new \Metowolf\Meting();
$data = $api->format(true)->album('65795');
echo $data;