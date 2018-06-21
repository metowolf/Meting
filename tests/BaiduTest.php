<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class BaiduTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('baidu');
        $data = $api->format(true)->search('hello', [
            'limit' => 10,
        ]);
        $data = json_decode($data, true);
        $this->assertCount(10, $data);
    }

    public function testSong()
    {
        $api = new Meting('baidu');
        $data = $api->format(true)->song('578055564');
        $data = json_decode($data, true);
        $this->assertEquals($data[0]['id'], 578055564);
    }

    public function testPic()
    {
        $api = new Meting('baidu');
        $data = $api->format(true)->pic('578055564');
        $data = json_decode($data, true);
        $pic = 'http://qukufile2.qianqian.com/data2/pic/a7e5421338fb4e3a0c0fd102a6dbed6b/578053624/578053624.jpg@s_1,w_300,h_300';
        $this->assertEquals($pic, $data['url']);
    }

    public function testUrl()
    {
        $api = new Meting('baidu');
        $data = $api->format(false)->url('578055564');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data['songurl']['url']);
    }

    public function testLyric()
    {
        $api = new Meting('baidu');
        $data = $api->format(true)->lyric('578055564');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data);
    }
}
