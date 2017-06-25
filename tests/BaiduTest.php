<?php
namespace Metowolf\Meting;
use Metowolf\Meting;
use PHPUnit\Framework\TestCase;

class BaiduTest extends TestCase
{
    public function testSearch()
    {
        $api = new Meting('baidu');
        $data = $api->format(true)->search('hello');
        $data = json_decode($data,1);
        $this->assertNotEmpty($data);
    }

    public function testSong(){
        $api = new Meting('baidu');
        $data = $api->format(true)->song('14672450');
        $data = json_decode($data,1);
        $this->assertNotEmpty($data);
    }

    // public function testPic(){
    //     $api = new Meting('baidu');
    //     $data = $api->format(true)->pic('14672450');
    //     $data = json_decode($data,1);
    //     $this->assertNotEmpty($data);
    // }

    public function testUrl(){
        $api = new Meting('baidu');
        $data = $api->format(true)->url('14672450');
        $data = json_decode($data,1);
        $this->assertNotEmpty($data);
    }

    public function testLyric(){
        $api = new Meting('baidu');
        $data = $api->format(true)->lyric('14672450');
        $data = json_decode($data,1);
        $this->assertNotEmpty($data);
    }
}
