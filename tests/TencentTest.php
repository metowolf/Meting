<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class TencentTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->search('hello', array(
            'limit' => 10,
        ));
        $data = json_decode($data, true);
        $this->assertCount(10, $data);
    }

    public function testSong()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->song('000bdxcy3ta8Q3');
        $data = json_decode($data, true);
        $this->assertEquals($data[0]['id'], '000bdxcy3ta8Q3');
    }

    public function testPic()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->pic('000bdxcy3ta8Q3');
        $data = json_decode($data, true);
        $pic = 'https://y.gtimg.cn/music/photo_new/T002R300x300M000000bdxcy3ta8Q3.jpg?max_age=2592000';
        $this->assertEquals($pic, $data['url']);
    }

    public function testUrl()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->url('000bdxcy3ta8Q3');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data['url']);
    }

    public function testLyric()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->lyric('000bdxcy3ta8Q3');
        $data = json_decode($data, true);
        $this->assertNotEmpty($data);
    }
}
