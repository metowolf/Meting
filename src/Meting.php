<?php
/*!
 * Meting music framework
 * https://i-meto.com
 * Version 1.3.3
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
        $this->_SITE=$v;
        return $this;
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
        curl_setopt($curl, CURLOPT_COOKIE, $BASE['cookie']);
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
            if (is_null($array)) {
                return null;
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
        if (is_null($raw)) {
            $raw=array();
        } elseif (!isset($raw[0])) {
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
                    'url'    => 'http://music.163.com/weapi/cloudsearch/pc',
                    'body'   => array(
                        's'      => $keyword,
                        'type'   => 1,
                        'limit'  => $limit,
                        'total'  => 'true',
                        'offset' => $page-1,
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'result#songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/soso/fcgi-bin/search_cp',
                    'body'   => array(
                        'p'        => $page,
                        'n'        => $limit,
                        'w'        => $keyword,
                        'aggr'     => 1,
                        'lossless' => 1,
                        'cr'       => 1,
                    ),
                    'decode' => 'jsonp2json',
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
                    'url'    => 'http://mobilecdn.kugou.com/api/v3/search/song',
                    'body'   => array(
                        'iscorrect' => 1,
                        'pagesize'  => $limit,
                        'plat'      => 20,
                        'sver'      => 3,
                        'showtype'  => 14,
                        'page'      => $page,
                        'keyword'   => $keyword,
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
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
                        'version'   => '5.9.5',
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
                    'url'    => 'http://music.163.com/weapi/v3/song/detail',
                    'body'   => array(
                        'c' => '[{"id":'.$id.'}]',
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                    'body'   => array(
                        'songmid' => $id,
                        'format'  => 'json',
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
                    'url'    => 'http://m.kugou.com/app/i/getSongInfo.php?',
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
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.song.play',
                        'songid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.5',
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
                    'url'    => 'http://music.163.com/weapi/v1/album/'.$id,
                    'body'   => array(
                        'id' => $id,
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg',
                    'body'   => array(
                        'albummid' => $id,
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
                        'version'  => 8400,
                    ),
                    'format' => 'data#info',
                );
                break;
            case 'baidu':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                    'body'   => array(
                        'method'   => 'baidu.ting.album.getAlbumInfo',
                        'album_id' => $id,
                        'format'   => 'json',
                        'from'     => 'ios',
                        'channel'  => '(null)',
                        'cuid'     => 'appstore',
                        'from'     => 'ios',
                        'version'  => '5.9.5',
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
                    'url'    => 'http://music.163.com/weapi/v1/artist/'.$id,
                    'body'   => array(
                        'top' => $limit,
                        "id"  => $id,
                        "ext" => "true",
                    ),
                    'encode' => 'netease_AESCBC',
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
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.artist.getSongList',
                        'tinguid' => $id,
                        'limits'  => $limit,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.5',
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
                    'url'    => 'http://music.163.com/weapi/v3/playlist/detail',
                    'body'   => array(
                        'id' => $id,
                        "n"  => 1000,
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'playlist#tracks',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/qzone/fcg-bin/fcg_ucc_getcdinfo_byids_cp.fcg',
                    'body'   => array(
                        'disstid' => $id,
                        'utf8'    => 1,
                        'type'    => 1,
                    ),
                    'decode' => 'jsonp2json',
                    'format' => 'cdlist#0#songlist',
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
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.diy.gedanInfo',
                        'listid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.5',
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
                    'url'    => 'http://music.163.com/weapi/song/enhance/player/url',
                    'body'   => array(
                        'ids' => array($id),
                        'br'  => $br*1000,
                    ),
                    'encode' => 'netease_AESCBC',
                    'decode' => 'netease_url',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                    'body'   => array(
                        'songmid' => $id,
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
        $this->_temp['br']=$br;
        return $this->curl($API);
    }

    public function lyric($id)
    {
        switch ($this->_SITE) {
            case 'netease':
                $API=array(
                    'method' => 'POST',
                    'url'    => 'http://music.163.com/weapi/song/lyric',
                    'body'   => array(
                        'id' => $id,
                        'os' => 'linux',
                        'lv' => -1,
                        'kv' => -1,
                        'tv' => -1,
                    ),
                    'encode' => 'netease_AESCBC',
                    'decode' => 'netease_lyric',
                );
                break;
            case 'tencent':
                $API=array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg',
                    'body'   => array(
                        'songmid'  => $id,
                        'g_tk'     => 5381,
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
                    'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                    'body'   => array(
                        'method'  => 'baidu.ting.song.lry',
                        'songid'  => $id,
                        'format'  => 'json',
                        'from'    => 'ios',
                        'channel' => '(null)',
                        'cuid'    => 'appstore',
                        'from'    => 'ios',
                        'version' => '5.9.5',
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
                $url='https://p3.music.126.net/'.$this->netease_pickey($id).'/'.$id.'.jpg?param='.$size.'z'.$size.'&quality=100';
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
                $url=$data['songinfo']['pic_big']?:$data['songinfo']['pic_small'];
                break;
        }
        return json_encode(array('url'=>$url));
    }

    private function curlset()
    {
        $BASE=array(
            'netease'=>array(
                'referer'   => 'https://music.163.com/',
                'cookie'    => 'os=iPhone OS; osver=10.3.1; appver=4.0.1; MUSIC_U=78d411095f4b022667bc8ec49e9a44cca088df057d987f5feaf066d37458e41c4a7d9447977352cf27ea9fee03f6ec4441049cea1c6bb9b6; __remember_me=true',
                'useragent' => '网易云音乐 4.0.1 rv:806 (iPhone; iOS 10.3.1; zh_CN)',
            ),
            'tencent'=>array(
                'referer'   => 'http://y.qq.com/portal/player.html',
                'cookie'    => 'qqmusic_uin=12345678; qqmusic_key=12345678; qqmusic_fromtag=30; ts_last=y.qq.com/portal/player.html;',
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
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ),
        );
        return $BASE[$this->_SITE];
    }

    /**
     * 乱七八糟的函数，加密解密...
     * 正在努力重构这些代码 TAT
     */
    private function netease_AESCBC($API)
    {
        $VI='0102030405060708';
        $body=json_encode($API['body']);
        $body=openssl_encrypt($body, 'aes-128-cbc', '0CoJUm6Qyw8W8jud', false, $VI);
        $body=openssl_encrypt($body, 'aes-128-cbc', 'MetingxxMetowolf', false, $VI);
        $API['body']=array(
            'params'=>$body,
            'encSecKey'=>'bc195fbe4418512169e8934cc4428e214e073f10227e6604ea11c17de037e18abda2488149083c7ef0ec48796cda7f39ae782eebc0724106ad19e0bd395f5ed3d8695905e9d851304b071bc4e1f3c759a08656cc9e77dee54ae6130aedd53015f779c3e11895341d9bb4ec8e8010bd9b7f1a515cb44b714c694a0af76ac378aa',
        );
        return $API;
    }
    private function jsonp2json($jsonp)
    {
        if ($jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $jsonp = substr($jsonp, strpos($jsonp, '('));
        }
        return trim($jsonp, '();');
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
        if($data['data'][0]['uf'] != null) {
            $data['data'][0]=$data['data'][0]['uf'];
        }
        $url=array(
            'url' => str_replace('http://m8', 'https://m8', $data['data'][0]['url']),
            'br'  => $data['data'][0]['br']/1000,
        );
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
                'json' => 3,
                'guid' => $GUID,
            ),
            'decode' => 'jsonp2json',
        );
        $KEY=json_decode($this->curl($API), 1);
        $KEY=$KEY['key'];

        $type=array(
            'size_320mp3' => array(320,'M800','mp3'),
            'size_128mp3' => array(128,'M500','mp3'),
            'size_96aac'  => array(96 ,'C400','m4a'),
            'size_48aac'  => array(48 ,'C200','m4a'),
        );
        foreach ($type as $key=>$vo) {
            if ($data['data'][0]['file'][$key]&&$vo[0]<=$this->_temp['br']) {
                $url=array(
                    'url' => 'http://dl.stream.qqmusic.qq.com/'.$vo[1].$data['data'][0]['file']['media_mid'].'.'.$vo[2].'?vkey='.$KEY.'&guid='.$GUID.'&uid=0&fromtag=30',
                    'br'  => $vo[0],
                );
                break;
            }
        }
        return json_encode($url);
    }
    private function xiami_url($result)
    {
        $data=json_decode($result, 1);
        if(isset($data['location'])) {
            $location = $data['location'];
            $num = (int)$location[0];
            $str = substr($location, 1);
            $len = floor(strlen($str)/$num);
            $sub = strlen($str) % $num;
            $qrc = array();
            $tmp = 0;
            $urlt = '';
            for(;$tmp<$sub;$tmp++){
                $qrc[$tmp] = substr($str, $tmp*($len+1), $len+1);
            }
            for(;$tmp<$num;$tmp++){
                $qrc[$tmp] = substr($str, $len*$tmp+$sub, $len);
            }
            for($tmpa=0;$tmpa<$len+1;$tmpa++){
                for($tmpb=0;$tmpb<$num;$tmpb++){
                    if(isset($qrc[$tmpb][$tmpa])) { $urlt.=$qrc[$tmpb][$tmpa];
                    }
                }
            }
            for($tmp=0;$tmp<$sub;$tmp++){
                //if(isset($qrc[$tmp][$len])) (string)$urlt.=(string)$qrc[$tmp][$len];
            }
            $urlt=str_replace('^', '0', urldecode($urlt));
            $url=array(
              'url' => urldecode($urlt),
              'br'  => 320,
            );
        }
        else{
            $url=array(
                'url' => "error",//str_replace('http:', 'https:', $data['data']['song']['listen_file']),
                'br'  => 0,
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
            if ($vo['info']['bitrate']<=$this->_temp['br']&&$vo['info']['bitrate']>$max) {
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
        return json_encode($url);
    }
    private function baidu_url($result)
    {
        $data=json_decode($result, 1);
        $url=array(
            'url' => $data['data']['songList'][0]['songLink'],
            'br'  => $data['data']['songList'][0]['rate'],
        );
        $url['url']=str_replace('http://yinyueshiting.baidu.com', 'https://gss0.bdstatic.com/y0s1hSulBw92lNKgpU_Z2jR7b2w6buu', $url['url']);
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
           'lyric'  => (@$result['lrc']['lyric'])?:'',
           'tlyric' => (@$result['tlyric']['lyric'])?:'',
        );
        return json_encode($data);
    }
    private function tencent_lyric($result)
    {
        $result=$this->jsonp2json($result);
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
        $API=array('method'=>'GET','url'=>$result['data']['song']['lyric']);
        $data=$this->curl($API);
        $data=preg_replace('/<[^>]+>/', '', $data);
        $arr=array(
            'lyric' => $data,
        );
        return json_encode($arr);
    }
    private function kugou_lyric($result)
    {
        if (!$this->_FORMAT) {
            return $result;
        }
        $arr=array(
            'lyric' => $result,
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
            'lyric' => (@$result['lrcContent'])?:'',
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
            'pic_id'    => (@$data['al']['pic_str'])?:$data['al']['pic'],
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
            'id'        => $data['songmid'],
            'name'      => $data['songname'],
            'artist'    => array(),
            'pic_id'    => $data['albummid'],
            'url_id'    => $data['songmid'],
            'lyric_id'  => $data['songmid'],
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
            'artist'   => explode(';', (@$data['singers'])?:$data['artist_name']),
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
            'name'     => (@$data['filename'])?:$data['fileName'],
            'artist'   => array(),
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
            'pic_id'   => $data['song_id'],
            'url_id'   => $data['song_id'],
            'lyric_id' => $data['song_id'],
            'source'   => 'baidu',
        );
        return $result;
    }
}
