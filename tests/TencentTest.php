<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class TencentTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->search('hello');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testSong()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->song('001icUif3vTGcO');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testPic()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->pic('002rBshp4WPAut');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testUrl()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->url('001icUif3vTGcO');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testLyric()
    {
        $api = new Meting('tencent');
        $data = $api->format(true)->lyric('001icUif3vTGcO');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }
}
