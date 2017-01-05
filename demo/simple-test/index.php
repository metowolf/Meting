<?php
/**
 * 用于 Meting 可用性测试或调试
 * 这是一个简单的调用例子，你可以直接使用类似下面的语句进行 API 调用
 * 其中 format(1) 表示使用内置格式化输出，否则将直接输出原 json 值
 */

require '../../Meting.php';

$data=(new Meting('netease'))->search('我的天空');
// $data=(new Meting('netease'))->format(1)->album('2630008');
// $data=(new Meting('netease'))->format(1)->playlist('3865036');
// $data=(new Meting('netease'))->format(1)->song('28892408');
// $data=(new Meting('netease'))->url('28892408');
// $data=(new Meting('netease'))->lyric('28892408');
// $data=(new Meting('netease'))->pic('5667982441251386');

// $data=(new Meting('tencent'))->format(1)->search('我的天空');
// $data=(new Meting('tencent'))->format(1)->album('003t7fZl36UCVe');
// $data=(new Meting('tencent'))->format(1)->playlist('2347440371');
// $data=(new Meting('tencent'))->format(1)->song('0043WGTm3kacAQ');
// $data=(new Meting('tencent'))->url('0043WGTm3kacAQ');
// $data=(new Meting('tencent'))->lyric('0043WGTm3kacAQ');
// $data=(new Meting('tencent'))->pic('003t7fZl36UCVe');

// $data=(new Meting('xiami'))->format(1)->search('我的天空');
// $data=(new Meting('xiami'))->format(1)->album('378103810');
// $data=(new Meting('xiami'))->format(1)->playlist('242988203');
// $data=(new Meting('xiami'))->format(1)->song('1772152245');
// $data=(new Meting('xiami'))->url('1776332943');
// $data=(new Meting('xiami'))->lyric('1772152245');
// $data=(new Meting('xiami'))->pic('1772152245');

// $data=(new Meting('kugou'))->format(1)->search('我的天空');
// $data=(new Meting('kugou'))->format(1)->album('542163');
// $data=(new Meting('kugou'))->format(1)->playlist('24912');
// $data=(new Meting('kugou'))->format(1)->song('21593575836ad1aa0bdb16a737b23b8a');
// $data=(new Meting('kugou'))->url('21593575836ad1aa0bdb16a737b23b8a');
// $data=(new Meting('kugou'))->lyric('21593575836ad1aa0bdb16a737b23b8a');
// $data=(new Meting('kugou'))->pic('21593575836ad1aa0bdb16a737b23b8a');

/* dure to copyright, use 演员 instead of 我的天空 */
// $data=(new Meting('baidu'))->format(1)->search('演员');
// $data=(new Meting('baidu'))->format(1)->album('241838068');
// $data=(new Meting('baidu'))->format(1)->playlist('364140449');
// $data=(new Meting('baidu'))->format(1)->song('242078437');
// $data=(new Meting('baidu'))->url('242078437');
// $data=(new Meting('baidu'))->lyric('242078437');
// $data=(new Meting('baidu'))->pic('242078437');

header('Content-type: application/json; charset=UTF-8');
echo json_encode(json_decode($data), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
