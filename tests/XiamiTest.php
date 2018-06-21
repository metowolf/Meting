<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class XiamiTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->search('hello', array(
            'limit' => 10,
        ));
        $data = json_decode($data, true);
        $this->assertCount(10, $data);
    }

    public function testSong()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->song('1774998338');
        $data = json_decode($data, true);
        $this->assertEquals($data[0]['id'], 1774998338);
    }

    public function testPic()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->pic('1774998338');
        $data = json_decode($data, true);
        $pic = 'https://pic.xiami.net/images/album/img85/23485/21002261901445585261.jpeg@1e_1c_100Q_300h_300w';
        $this->assertEquals($pic, $data['url']);
    }

    public function testUrl()
    {
        $api = new Meting('xiami');
        $data = $api->format(false)->url('1774998338');
        $data = json_decode($data, true);
        $this->assertEquals($data['ret'][0], 'SUCCESS::调用成功');
    }

    public function testLyric()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->lyric('1774998338');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data);
    }
}
