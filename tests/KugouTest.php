<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class KugouTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->search('hello', array(
            'limit' => 10,
        ));
        $data = json_decode($data, true);
        $this->assertCount(10, $data);
    }

    public function testSong()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->song('d353b69a3b7f1a250000c5daabb8a4f1');
        $data = json_decode($data, true);
        $this->assertEquals(strtolower($data[0]['id']), 'd353b69a3b7f1a250000c5daabb8a4f1');
    }

    public function testPic()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->pic('d353b69a3b7f1a250000c5daabb8a4f1');
        $data = json_decode($data, true);
        $pic = 'http://singerimg.kugou.com/uploadpic/softhead/400/20180528/20180528181654229.jpg';
        $this->assertEquals($pic, $data['url']);
    }

    public function testUrl()
    {
        $api = new Meting('kugou');
        $data = $api->format(false)->url('d353b69a3b7f1a250000c5daabb8a4f1');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data['data'][0]['relate_goods']);
    }

    public function testLyric()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->lyric('d353b69a3b7f1a250000c5daabb8a4f1');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data);
    }
}
