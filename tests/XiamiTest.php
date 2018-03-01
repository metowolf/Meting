<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class XiamiTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->search('hello');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testSong()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->song('1774998338');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    // public function testPic(){
    //     $api = new Meting('xiami');
    //     $data = $api->format(true)->pic('1774998338');
    //     $data = json_decode($data,1);
    //     $this->assertNotEmpty($data);
    // }

    public function testUrl()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->url('1774998338');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testLyric()
    {
        $api = new Meting('xiami');
        $data = $api->format(true)->lyric('1774998338');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }
}
