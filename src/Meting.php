<?php
/*!
 * Meting music framework
 * https://i-meto.com
 * https://github.com/metowolf/Meting
 * Version 1.3.9
 *
 * Copyright 2017, METO Sheel <i@i-meto.com>
 * Released under the MIT license
 */

namespace Metowolf;

class Meting
{
    protected $_SITE;
    protected $_TEMP;
    protected $_RETRY = 3;
    protected $_FORMAT = false;

    public function __construct($v = 'netease')
    {
        $this->site($v);
    }

    public function site($v)
    {
        $suppose=array('netease','tencent','xiami','kugou','baidu');
        $this->_SITE=in_array($v,$suppose)?$v:'netease';
        return $this;
    }

    public function cookie($v = '')
    {
        if (!empty($v)) {
            $this->_TEMP['cookie']=$v;
        }
    }

    public function format($v = true)
    {
        $this->_FORMAT=$v;
        return $this;
    }

    private function curl($API)
    {
        if (isset($API['encode'])) {
            $API=call_user_func_array(array($this,$API['encode']), array($API));
        }
        $BASE=$this->curlset();
        $curl=curl_init();
        if ($API['method']=='POST') {
            if (is_array($API['body'])) {
                $API['body']=http_build_query($API['body']);
            }
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $API['body']);
        } elseif ($API['method']=='GET') {
            if (isset($API['body'])) {
                $API['url']=$API['url'].'?'.http_build_query($API['body']);
            }
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $API['url']);
        curl_setopt($curl, CURLOPT_COOKIE, isset($this->_TEMP['cookie'])?$this->_TEMP['cookie']:$BASE['cookie']);
        curl_setopt($curl, CURLOPT_REFERER, $BASE['referer']);
        curl_setopt($curl, CURLOPT_USERAGENT, $BASE['useragent']);
        for ($i=0;$i<=$this->_RETRY;$i++) {
            $data=curl_exec($curl);
            $info=curl_getinfo($curl);
            $error=curl_errno($curl);
            $status=$error?curl_error($curl):'';
            if (!$error) {
                break;
            }
        }
        curl_close($curl);
        if ($error) {
            return json_encode(
                array(
                    'error'  => $error,
                    'info'   => $info,
                    'status' => $status,
                )
            );
        }
        if ($this->_FORMAT&&isset($API['decode'])) {
            $data=call_user_func_array(array($this,$API['decode']), array($data));
        }
        if ($this->_FORMAT&&isset($API['format'])) {
            $data=json_decode($data, 1);
            $data=$this->clean($data, $API['format']);
            $data=json_encode($data);
        }
        return $data;
    }

    private function pickup($array, $rule)
    {
        $t=explode('#', $rule);
        foreach ($t as $vo) {
            if (!isset($array[$vo])){
                return array();
            }
            $array=$array[$vo];
        }
        return $array;
    }

    private function clean($raw, $rule)
    {
        if (!empty($rule)) {
            $raw=$this->pickup($raw, $rule);
        }
        if (!isset($raw[0])&&sizeof($raw)) {
            $raw=array($raw);
        }
        $result=array_map(array($this,'format_'.$this->_SITE), $raw);
        return $result;
    }

    public function search($keyword, $page=1, $limit=30)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'POST',
                        'params' => array(
                            's'      => $keyword,
                            'type'   => 1,
                            'limit'  => $limit,
                            'total'  => 'true',
                            'offset' => ($page-1)*$limit,
                        ),
                        'url' => 'http://music.163.com/api/cloudsearch/pc',
                    ),
                    'encode' => 'netease_AESECB',
                    'format' => 'result#songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/soso/fcgi-bin/client_search_cp',
                    'body'   => array(
                        'format'   => 'json',
                        'p'        => $page,
                        'n'        => $limit,
                        'w'        => $keyword,
                        'aggr'     => 1,
                        'lossless' => 1,
                        'cr'       => 1,
                        'new_json' => 1,
                    ),
                    'format' => 'data#song#list',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'key'     => $keyword,
                        'page'    => $page,
                        'limit'   => $limit,
                        'r'       => 'search/songs',
                    ),
                    'format' => 'data#songs',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://ioscdn.kugou.com/api/v3/search/song',
                    'body'   => array(
                        'iscorrect' => 1,
                        'pagesize'  => $limit,
                        'plat'      => 2,
                        'tag'       => 1,
                        'sver'      => 5,
                        'showtype'  => 10,
                        'page'      => $page,
                        'keyword'   => $keyword,
                        'version'   => 8550
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'    => 'baidu.ting.search.merge',
                        'isNew'     => 1,
                        'query'     => $keyword,
                        'page_size' => $limit,
                        'page_no'   => $page,
                        'type'      => 0,
                        'format'    => 'json',
                        'from'      => 'ios',
                        'channel'   => '(null)',
                        'cuid'      => 'appstore',
                        'from'      => 'ios',
                        'version'   => '5.9.12',
                    ),
                    'format' => 'result#song_info#song_list',
                );
                break;
        }
        return $this->curl($API);
    }

    public function song($id)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'POST',
                        'params' => array(
                            'c' => '[{"id":'.$id.'}]',
                        ),
                        'url' => 'http://music.163.com/api/v3/song/detail',
                    ),
                    'encode' => 'netease_AESECB',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                    'body'   => array(
                        'songmid'  => $id,
                        'platform' => 'yqq',
                        'format'   => 'json',
                    ),
                    'decode' => 'tencent_singlesong',
                    'format' => 'data',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'r'       => 'song/detail',
                    ),
                    'format' => 'data#song',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://m.kugou.com/app/i/getSongInfo.php',
                    'body'   => array(
                        "cmd"  => "playInfo",
                        "hash" => $id,
                        "from" => "mkugou",
                    ),
                    'format' => '',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.song.play',
                        'songid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.12',
                    ),
                    'format' => 'songinfo',
                );
                break;
        }
        return $this->curl($API);
    }

    public function album($id)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'GET',
                        'params' => array(
                            'id' => $id,
                        ),
                        'url' => 'http://music.163.com/api/v1/album/'.$id,
                    ),
                    'encode' => 'netease_AESECB',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_detail_cp.fcg',
                    'body'   => array(
                        'albummid' => $id,
                        'platform' => 'mac',
                        'format'   => 'json',
                        'newsong'  => 1,
                    ),
                    'format' => 'data#getSongInfo',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'r'       => 'album/detail',
                    ),
                    'format' => 'data#songs',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://mobilecdn.kugou.com/api/v3/album/song',
                    'body'   => array(
                        'albumid'  => $id,
                        'plat'     => 2,
                        'page'     => 1,
                        'pagesize' => -1,
                        'version'  => 8550,
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'   => 'baidu.ting.album.getAlbumInfo',
                        'album_id' => $id,
                        'format'   => 'json',
                        'from'     => 'ios',
                        'channel'  => '(null)',
                        'cuid'     => 'appstore',
                        'from'     => 'ios',
                        'version'  => '5.9.12',
                    ),
                    'format' => 'songlist',
                );
                break;
        }
        return $this->curl($API);
    }

    public function artist($id, $limit=50)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'GET',
                        'params' => array(
                            'top' => $limit,
                            "id"  => $id,
                            "ext" => "true",
                        ),
                        'url' => 'http://music.163.com/api/v1/artist/'.$id,
                    ),
                    'encode' => 'netease_AESECB',
                    'format' => 'hotSongs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_singer_track_cp.fcg',
                    'body'   => array(
                        'singermid' => $id,
                        'begin'     => 0,
                        'num'       => $limit,
                        'order'     => 'listen',
                        'platform'  => 'mac',
                        'newsong'   => 1,
                    ),
                    'format' => 'data#list',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'limit'   => $limit,
                        'page'    => 1,
                        'r'       => 'artist/hot-songs',
                    ),
                    'format' => 'data',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://mobilecdn.kugou.com/api/v3/singer/song',
                    'body'   => array(
                        'singerid' => $id,
                        'page'     => 1,
                        'plat'     => 0,
                        'pagesize' => $limit,
                        'version'  => 8400,
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.artist.getSongList',
                        'tinguid' => $id,
                        'limits'  => $limit,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.12',
                    ),
                    'format' => 'songlist',
                );
                break;
        }
        return $this->curl($API);
    }

    public function playlist($id)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'POST',
                        'params' => array(
                            'id' => $id,
                            "n"  => 1000,
                        ),
                        'url' => 'http://music.163.com/api/v3/playlist/detail',
                    ),
                    'encode' => 'netease_AESECB',
                    'format' => 'playlist#tracks',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_playlist_cp.fcg',
                    'body'   => array(
                        'id'       => $id,
                        'format'   => 'json',
                        'newsong'  => 1,
                        'platform' => 'jqspaframe.json',
                    ),
                    'format' => 'data#cdlist#0#songlist',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'r'       => 'collect/detail',
                    ),
                    'format' => 'data#songs',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://mobilecdn.kugou.com/api/v3/special/song',
                    'body'   => array(
                        'specialid' => $id,
                        'page'      => 1,
                        'plat'      => 2,
                        'pagesize'  => -1,
                        'version'   => 8400,
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.diy.gedanInfo',
                        'listid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.12',
                    ),
                    'format' => 'content',
                );
                break;
        }
        return $this->curl($API);
    }

    public function url($id, $br=320)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'POST',
                        'params' => array(
                            'ids' => array($id),
                            'br'  => $br*1000,
                        ),
                        'url' => 'http://music.163.com/api/song/enhance/player/url',
                    ),
                    'encode' => 'netease_AESECB',
                    'decode' => 'netease_url',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                    'body'   => array(
                        'songmid' => $id,
                        'platform' => 'yqq',
                        'format'  => 'json',
                    ),
                    'decode' => 'tencent_url',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://www.xiami.com/song/gethqsong/sid/'.$id,
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'r'       => 'song/detail',
                    ),
                    'decode' => 'xiami_url',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://media.store.kugou.com/v1/get_res_privilege',
                    'body'   => json_encode(array(
                        "relate"    => 1,
                        "userid"    => 0,
                        "vip"       => 0,
                        "appid"     => 1005,
                        "token"     => "",
                        "behavior"  => "download",
                        "clientver" => "8493",
                        "resource"  => array(array(
                            "id"   => 0,
                            "type" => "audio",
                            "hash" => $id,
                        )))
                    ),
                    'decode' => 'kugou_url',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://music.baidu.com/data/music/fmlink',
                    'body'   => array(
                        'songIds' => $id,
                        'rate'    => $br,
                        'type'    => 'mp3',
                    ),
                    'decode' => 'baidu_url',
                );
                break;
        }
        $this->_TEMP['br']=$br;
        return $this->curl($API);
    }

    public function lyric($id)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/api/linux/forward',
                    'body'   => array(
                        'method' => 'POST',
                        'params' => array(
                            'id' => $id,
                            'os' => 'linux',
                            'lv' => -1,
                            'kv' => -1,
                            'tv' => -1,
                        ),
                        'url' => 'http://music.163.com/api/song/lyric',
                    ),
                    'encode' => 'netease_AESECB',
                    'decode' => 'netease_lyric',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg',
                    'body'   => array(
                        'songmid'  => $id,
                        'g_tk'     => '5381',
                    ),
                    'decode' => 'tencent_lyric',
                );
                break;
            case 'xiami':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://api.xiami.com/web',
                    'body'   => array(
                        'v'       => '2.0',
                        'app_key' => '1',
                        'id'      => $id,
                        'r'       => 'song/detail',
                    ),
                    'decode' => 'xiami_lyric',
                );
                break;
            case 'kugou':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://m.kugou.com/app/i/krc.php',
                    'body'   => array(
                        'keyword'    => '%20-%20',
                        'timelength' => 1000000,
                        'cmd'        => 100,
                        'hash'       => $id,
                    ),
                    'decode' => 'kugou_lyric'
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://musicapi.qianqian.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.song.lry',
                        'songid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.12',
                    ),
                    'decode' => 'baidu_lyric'
                );
                break;
        }
        return $this->curl($API);
    }

    public function pic($id, $size=300)
    {
        switch ($this->_SITE) {
            case 'netease':
                $url='https://p3.music.126.net/'.$this->netease_pickey($id).'/'.$id.'.jpg?param='.$size.'y'.$size;
                break;
            case 'tencent':
                $url='https://y.gtimg.cn/music/photo_new/T002R'.$size.'x'.$size.'M000'.$id.'.jpg?max_age=2592000';
                break;
            case 'xiami':
                $format=$this->_FORMAT;
                $data=$this->format(false)->song($id);
                $this->format($format);
                $data=json_decode($data, 1);
                $url=$data['data']['song']['logo'];
                $url=str_replace(array('_1.','http:','img.'), array('.','https:','pic.'), $url).'@'.$size.'h_'.$size.'w_100q_1c.jpg';
                break;
            case 'kugou':
                $format=$this->_FORMAT;
                $data=$this->format(false)->song($id);
                $this->format($format);
                $data=json_decode($data, 1);
                $url=$data['imgUrl'];
                $url=str_replace('{size}', '400', $url);
                break;
            case 'baidu':
                $format=$this->_FORMAT;
                $data=$this->format(false)->song($id);
                $this->format($format);
                $data=json_decode($data, 1);
                $url=isset($data['songinfo']['pic_big'])?$data['songinfo']['pic_big']:$data['songinfo']['pic_small'];
                break;
        }
        return json_encode(array('url'=>$url));
    }

    private function curlset()
    {
        $BASE=array(
            'netease'=>array(
                'referer'   => 'https://music.163.com/',
                'cookie'    => 'os=linux; appver=1.0.0.1026; osver=Ubuntu%2016.10; MUSIC_U=78d411095f4b022667bc8ec49e9a44cca088df057d987f5feaf066d37458e41c4a7d9447977352cf27ea9fee03f6ec4441049cea1c6bb9b6; __remember_me=true',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ),
            'tencent'=>array(
                'referer'   => 'https://y.qq.com/portal/player.html',
                'cookie'    => 'pgv_pvi=22038528; pgv_si=s3156287488; pgv_pvid=5535248600; yplayer_open=1; ts_last=y.qq.com/portal/player.html; ts_uid=4847550686; yq_index=0; qqmusic_fromtag=66; player_exist=1',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ),
            'xiami'=>array(
                'referer'   => 'http://h.xiami.com/',
                'cookie'    => 'user_from=2;XMPLAYER_addSongsToggler=0;XMPLAYER_isOpen=0;_xiamitoken=123456789;',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ),
            'kugou'=>array(
                'referer'   => 'http://www.kugou.com/webkugouplayer/flash/webKugou.swf',
                'cookie'    => '_WCMID=123456789',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ),
            'baidu'=>array(
                'referer'   => 'http://ting.baidu.com/',
                'cookie'    => 'BAIDUID=123456789',
                'useragent' => 'ios_5.9.12',
            ),
        );
        return $BASE[$this->_SITE];
    }

    /**
     * 乱七八糟的函数，加密解密...
     * 正在努力重构这些代码 TAT
     */
    private function netease_AESECB($API)
    {
        $KEY='7246674226682325323F5E6544673A51';
        $body=json_encode($API['body']);
        if (function_exists('openssl_encrypt')) {
            $body=openssl_encrypt($body, 'aes-128-ecb', pack('H*', $KEY));
        } else {
            $PAD=16-(strlen($body)%16);
            $body=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, hex2bin($KEY), $body.str_repeat(chr($PAD), $PAD), MCRYPT_MODE_ECB));
        }
        $body=strtoupper(bin2hex(base64_decode($body)));

        $API['body']=array(
            'eparams'=>$body,
        );
        return $API;
    }
    private function tencent_singlesong($result)
    {
        $result=json_decode($result, 1);
        $data=$result['data'][0];
        $t=array(
            'songmid' => $data['mid'],
            'songname' => $data['name'],
            'albummid' => $data['album']['mid'],
        );
        foreach ($t as $key=>$vo) {
            $result['data'][0][$key]=$vo;
        }
        return json_encode($result);
    }
    private function netease_pickey($id)
    {
        $magic=str_split('3go8&$8*3*3h0k(2)2');
        $song_id=str_split($id);
        for ($i=0;$i<count($song_id);$i++) {
            $song_id[$i]=chr(ord($song_id[$i])^ord($magic[$i%count($magic)]));
        }
        $result=base64_encode(md5(implode('', $song_id), 1));
        $result=str_replace(array('/','+'), array('_','-'), $result);
        return $result;
    }
    /**
     * URL - 歌曲地址转换函数
     * 用于返回不高于指定 bitRate 的歌曲地址（默认规范化）
     */
    private function netease_url($result)
    {
        $data=json_decode($result, 1);
        if (isset($data['data'][0]['uf']['url'])) {
            $data['data'][0]['url']=$data['data'][0]['uf']['url'];
        }
        if (isset($data['data'][0]['url'])) {
            $url=array(
                'url' => $data['data'][0]['url'],
                'br'  => $data['data'][0]['br']/1000,
            );
        } else {
            $url=array(
                'url' => '',
                'br'  => -1,
            );
        }
        return json_encode($url);
    }
    private function tencent_url($result)
    {
        $data=json_decode($result, 1);
        $GUID=mt_rand()%10000000000;
        $API=array(
            'method' => 'GET',
            'url'    => 'https://c.y.qq.com/base/fcgi-bin/fcg_musicexpress.fcg',
            'body'   => array(
                'json'   => 3,
                'guid'   => $GUID,
                'format' => 'json',
            ),
        );
        $KEY=json_decode($this->curl($API), 1);
        $KEY=$KEY['key'];

        $type=array(
            'size_320mp3' => array(320,'M800','mp3'),
            'size_192aac' => array(192,'C600','m4a'),
            'size_128mp3' => array(128,'M500','mp3'),
            'size_96aac'  => array(96 ,'C400','m4a'),
            'size_48aac'  => array(48 ,'C200','m4a'),
        );
        foreach ($type as $key=>$vo) {
            if ($data['data'][0]['file'][$key]&&$vo[0]<=$this->_TEMP['br']) {
                $url=array(
                    'url' => 'https://dl.stream.qqmusic.qq.com/'.$vo[1].$data['data'][0]['file']['media_mid'].'.'.$vo[2].'?vkey='.$KEY.'&guid='.$GUID.'&uid=0&fromtag=30',
                    'br'  => $vo[0],
                );
                break;
            }
        }
        if (!isset($url['url'])) {
            $url=array(
                'url' => '',
                'br'  => -1,
            );
        }
        return json_encode($url);
    }
    private function xiami_url($result)
    {
        $data=json_decode($result, 1);
        if (!empty($data['location'])) {
            $location = $data['location'];
            $num = (int)$location[0];
            $str = substr($location, 1);
            $len = floor(strlen($str)/$num);
            $sub = strlen($str) % $num;
            $qrc = array();
            $tmp = 0;
            $urlt = '';
            for (;$tmp<$sub;$tmp++) {
                $qrc[$tmp] = substr($str, $tmp*($len+1), $len+1);
            }
            for (;$tmp<$num;$tmp++) {
                $qrc[$tmp] = substr($str, $len*$tmp+$sub, $len);
            }
            for ($tmpa=0;$tmpa<$len+1;$tmpa++) {
                for ($tmpb=0;$tmpb<$num;$tmpb++) {
                    if (isset($qrc[$tmpb][$tmpa])) {
                        $urlt.=$qrc[$tmpb][$tmpa];
                    }
                }
            }
            $urlt=str_replace('^', '0', urldecode($urlt));
            $url=array(
                'url' => str_replace('http://','https://',urldecode($urlt)),
                'br'  => 320,
            );
        } else {
            $url=array(
                'url' => '',
                'br'  => -1,
            );
        }
        return json_encode($url);
    }
    private function kugou_url($result)
    {
        $data=json_decode($result, 1);

        $max=0;
        $url=array();
        foreach ($data['data'][0]['relate_goods'] as $vo) {
            if ($vo['info']['bitrate']<=$this->_TEMP['br']&&$vo['info']['bitrate']>$max) {
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://trackercdn.kugou.com/i/v2/',
                    'body'   => array(
                        'hash'     => $vo['hash'],
                        'key'      => md5($vo['hash'].'kgcloudv2'),
                        'pid'      => 1,
                        'behavior' => 'play',
                        'cmd'      => '23',
                        'version'  => 8400,
                    ),
                );
                $t=json_decode($this->curl($API), 1);
                if (isset($t['url'])) {
                    $max=$t['bitRate']/1000;
                    $url=array(
                        'url' => $t['url'],
                        'br'  => $t['bitRate']/1000,
                    );
                }
            }
        }
        if (!isset($url['url'])) {
            $url=array(
                'url' => '',
                'br'  => -1,
            );
        }
        return json_encode($url);
    }
    private function baidu_url($result)
    {
        $data=json_decode($result, 1);
        if (isset($data['data']['songList'][0]['songLink'])) {
            $url=array(
                'url' => $data['data']['songList'][0]['songLink'],
                'br'  => $data['data']['songList'][0]['rate'],
            );
            $url['url']=str_replace('http://yinyueshiting.baidu.com', 'https://gss0.bdstatic.com/y0s1hSulBw92lNKgpU_Z2jR7b2w6buu', $url['url']);
        } else {
            $url=array(
                'url' => '',
                'br'  => -1,
            );
        }
        return json_encode($url);
    }
    /**
     * 歌词处理模块
     * 用于规范化歌词输出
     */
    private function netease_lyric($result)
    {
        if (!$this->_FORMAT) {
            return $result;
        }
        $result=json_decode($result, 1);
        $data=array(
           'lyric'  => isset($result['lrc']['lyric'])?$result['lrc']['lyric']:'',
           'tlyric' => isset($result['tlyric']['lyric'])?$result['tlyric']['lyric']:'',
        );
        return json_encode($data);
    }
    private function tencent_lyric($result)
    {
        $result=substr($result,18,-1);
        if (!$this->_FORMAT) {
            return $result;
        }
        $result=json_decode($result, 1);
        $data=array(
             'lyric'  => isset($result['lyric'])?base64_decode($result['lyric']):'',
             'tlyric' => isset($result['trans'])?base64_decode($result['trans']):'',
         );
        return json_encode($data);
    }
    private function xiami_lyric($result)
    {
        if (!$this->_FORMAT) {
            return $result;
        }
        $result=json_decode($result, 1);
        $data='';
        if(!empty($result['data']['song']['lyric'])){
            $API=array('method'=>'GET','url'=>$result['data']['song']['lyric']);
            $data=$this->curl($API);
            $data=preg_replace('/<[^>]+>/', '', $data);
        }
        preg_match_all('/\[([\d:\.]+)\](.*)\s\[x-trans\](.*)/i',$data,$match);
        if(sizeof($match[0])){
            for($i=0;$i<sizeof($match[0]);$i++){
                $A[]='['.$match[1][$i].']'.$match[2][$i];
                $B[]='['.$match[1][$i].']'.$match[3][$i];
            }
            $arr=array(
                'lyric'  => str_replace($match[0],$A,$data),
                'tlyric' => str_replace($match[0],$B,$data),
            );
        }
        else{
            $arr=array(
                'lyric'  => $data,
                'tlyric' => '',
            );
        }
        return json_encode($arr);
    }
    private function kugou_lyric($result)
    {
        if (!$this->_FORMAT) {
            return $result;
        }
        $arr=array(
            'lyric'  => $result,
            'tlyric' => '',
        );
        return json_encode($arr);
    }
    private function baidu_lyric($result)
    {
        if (!$this->_FORMAT) {
            return $result;
        }
        $result=json_decode($result, 1);
        $data=array(
            'lyric'  => isset($result['lrcContent'])?$result['lrcContent']:'',
            'tlyric' => '',
        );
        return json_encode($data);
    }
    /**
     * Format - 规范化函数
     * 用于统一返回的参数，可用 ->format() 一次性开关开启
     */
    private function format_netease($data)
    {
        $result=array(
            'id'        => $data['id'],
            'name'      => $data['name'],
            'artist'    => array(),
            'album'     => $data['al']['name'],
            'pic_id'    => isset($data['al']['pic_str'])?$data['al']['pic_str']:$data['al']['pic'],
            'url_id'    => $data['id'],
            'lyric_id'  => $data['id'],
            'source'    => 'netease',
        );
        if (isset($data['al']['picUrl'])) {
            preg_match('/\/(\d+)\./', $data['al']['picUrl'], $match);
            $result['pic_id']=$match[1];
        }
        foreach ($data['ar'] as $vo) {
            $result['artist'][]=$vo['name'];
        }
        return $result;
    }
    private function format_tencent($data)
    {
        if (isset($data['musicData'])) {
            $data=$data['musicData'];
        }
        $result=array(
            'id'        => $data['mid'],
            'name'      => $data['name'],
            'artist'    => array(),
            'album'     => trim($data['album']['title']),
            'pic_id'    => $data['album']['mid'],
            'url_id'    => $data['mid'],
            'lyric_id'  => $data['mid'],
            'source'    => 'tencent',
        );
        foreach ($data['singer'] as $vo) {
            $result['artist'][]=$vo['name'];
        }
        return $result;
    }
    private function format_xiami($data)
    {
        $result=array(
            'id'       => $data['song_id'],
            'name'     => $data['song_name'],
            'artist'   => explode(';', isset($data['singers'])?$data['singers']:$data['artist_name']),
            'album'    => $data['album_name'],
            'pic_id'   => $data['song_id'],
            'url_id'   => $data['song_id'],
            'lyric_id' => $data['song_id'],
            'source'   => 'xiami',
        );
        return $result;
    }
    private function format_kugou($data)
    {
        $result=array(
            'id'       => $data['hash'],
            'name'     => isset($data['filename'])?$data['filename']:$data['fileName'],
            'artist'   => array(),
            'album'    => isset($data['album_name'])?$data['album_name']:'',
            'url_id'   => $data['hash'],
            'pic_id'   => $data['hash'],
            'lyric_id' => $data['hash'],
            'source'   => 'kugou',
        );
        list($result['artist'], $result['name'])=explode(' - ', $result['name'], 2);
        $result['artist']=explode('、', $result['artist']);
        return $result;
    }
    private function format_baidu($data)
    {
        $result=array(
            'id'       => $data['song_id'],
            'name'     => $data['title'],
            'artist'   => explode(',', $data['author']),
            'album'    => $data['album_title'],
            'pic_id'   => $data['song_id'],
            'url_id'   => $data['song_id'],
            'lyric_id' => $data['song_id'],
            'source'   => 'baidu',
        );
        return $result;
    }
}
