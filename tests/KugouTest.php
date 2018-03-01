<?php

namespace Metowolf\Meting;

use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class KugouTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->search('hello');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testSong()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->song('9ed11b0c5a86f33541fa88b57c5ba00a');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testPic()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->pic('9ed11b0c5a86f33541fa88b57c5ba00a');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testUrl()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->url('9ed11b0c5a86f33541fa88b57c5ba00a');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }

    public function testLyric()
    {
        $api = new Meting('kugou');
        $data = $api->format(true)->lyric('9ed11b0c5a86f33541fa88b57c5ba00a');
        $data = json_decode($data, 1);
        $this->assertNotEmpty($data);
    }
}
