<?php
/*!
 * Meting music framework
 *
 * @author   METO Sheel <i@i-meto.com>
 * @website  https://i-meto.com
 * @license  http://opensource.org/licenses/MIT
 * @version  0.9.8 RC
 *
 * Suppose  search   song    album   playlist    lyric  artist
 * netease  *        *       *       *           *      *
 * tencent  *        *       *       *           *      *
 * xiami    *        *       *       *           *      *
 * kugou    *        *       *       *           *      *
 * baidu    *        *       *       *           *      *
 */
class Meting
{
    protected $_SITE;
    protected $_TEMP;
    protected $_FORMAT = false;

    function __construct($v){
        $this->_SITE=$v;
    }

    public function format($v = true){
        $this->_FORMAT=$v;
        return $this;
    }

    private function curl($API){
        if(isset($API['encode']))$API=call_user_func_array(array($this,$API['encode']),array($API));

        $BASE=self::curlset();
        $curl=curl_init();
        if($API['method']=='POST'){
            if(is_array($API['body']))$API['body']=http_build_query($API['body']);
            curl_setopt($curl,CURLOPT_URL,$API['url']);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$API['body']);
            curl_setopt($curl,CURLOPT_POST,1);
        }
        elseif($API['method']=='GET'){
            if(isset($API['body']))$API['url']=$API['url'].'?'.http_build_query($API['body']);
            curl_setopt($curl,CURLOPT_URL,$API['url']);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curl,CURLOPT_COOKIE,$BASE['cookie']);
        curl_setopt($curl,CURLOPT_REFERER,$BASE['referer']);
        curl_setopt($curl,CURLOPT_USERAGENT,$BASE['useragent']);
        $result=curl_exec($curl);
        curl_close($curl);

        if(isset($API['decode']))$result=call_user_func_array(array($this,$API['decode']),array($result));

        if($this->_FORMAT){
            if(isset($API['format'])){
                $result=json_decode($result,1);
                $result=self::clean($result,$API['format']);
                $result=json_encode($result);
            }
            $this->_FORMAT=false;
        }
        return $result;
    }

    private function pickup($array,$rule){
        $t=explode('#',$rule);
        foreach($t as $vo){
            if($array==null)return null;
            $array=$array[$vo];
        }
        return $array;
    }

    private function clean($raw,$rule){
        if(!empty($rule))$raw=self::pickup($raw,$rule);
        if($raw==null)$raw=array();
        elseif(!isset($raw[0]))$raw=array($raw);
        $result=array();
        foreach($raw as $vo){
            $result[]=call_user_func_array(array($this,'format_'.$this->_SITE),array($vo));
        }
        return $result;
    }

    public function search($keyword,$page=1,$limit=30){
        $API=array(
            'netease' => array(
                'method' => 'POST',
                'url'    => 'http://music.163.com/api/linux/forward',
                'body'   => array(
                    'method' => 'POST',
                    'params' => array(
                        's'      => $keyword,
                        'type'   => 1,
                        'limit'  => $limit,
                        'total'  => 'true',
                        'offset' => $page-1,
                    ),
                    'url' => 'http://music.163.com/api/cloudsearch/pc',
                ),
                'encode' => 'netease_AESECB',
                'format' => 'result#songs',
            ),
            'tencent' => array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/soso/fcgi-bin/search_cp',
                'body'   => array(
                    'p'       => $page,
                    'n'       => $limit,
                    'w'       => $keyword,
                    'aggr'    => 1,
                    'lossless'=> 1,
                    'cr'      => 1,
                ),
                'decode' => 'jsonp2json',
                'format' => 'data#song#list',
            ),
            'xiami' => array(
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
            ),
            'kugou' => array(
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
            ),
            'baidu' => array(
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
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function song($id){
        $API=array(
            'netease' => array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                'body'   => array(
                    'songmid' => $id,
                    'format'  => 'json',
                ),
                'decode' => 'tencent_singlesong',
                'format' => 'data',
            ),
            'xiami'=>array(
                'method' => 'GET',
                'url'    => 'http://api.xiami.com/web',
                'body'   => array(
                    'v'       => '2.0',
                    'app_key' => '1',
                    'id'      => $id,
                    'r'       => 'song/detail',
                ),
                'format' => 'data#song',
            ),
            'kugou'=>array(
                'method' => 'POST',
                'url'    => 'http://m.kugou.com/app/i/getSongInfo.php?',
                'body'   => array(
                    "cmd"  => "playInfo",
                    "hash" => $id,
                    "from" => "mkugou",
                ),
                'format' => '',
            ),
            'baidu' => array(
                'method' => 'GET',
                'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                'body'   => array(
                    'method' => 'baidu.ting.song.play',
                    'songid' => $id,
                    'format' => 'json',
                    'from'   => 'ios',
                    'channel'=> '(null)',
                    'cuid'   => 'appstore',
                    'from'   => 'ios',
                    'version'=> '5.9.5',
                ),
                'format' => 'songinfo',
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function album($id){
        $API=array(
            'netease'=>array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg',
                'body'   => array(
                    'albummid' => $id,
                ),
                'format' => 'data#list',
            ),
            'xiami'=>array(
                'method' => 'GET',
                'url'    => 'http://api.xiami.com/web',
                'body'   => array(
                    'v'       => '2.0',
                    'app_key' => '1',
                    'id'      => $id,
                    'r'       => 'album/detail',
                ),
                'format' => 'data#songs',
            ),
            'kugou'=>array(
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
            ),
            'baidu' => array(
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
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function artist($id,$limit=50){
        $API=array(
            'netease'=>array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_singer_track_cp.fcg',
                'body'   => array(
                    'singermid' => $id,
                    'begin'     => 0,
                    'num'       => $limit,
                ),
                'format' => 'data#list',
            ),
            'xiami' => array(
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
            ),
            'kugou'=>array(
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
            ),
            'baidu' => array(
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
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function playlist($id){
        $API=array(
            'netease'=>array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/qzone/fcg-bin/fcg_ucc_getcdinfo_byids_cp.fcg',
                'body'   => array(
                    'disstid' => $id,
                    'utf8'    => 1,
                    'type'    => 1,
                ),
                'decode' => 'jsonp2json',
                'format' => 'cdlist#0#songlist',
            ),
            'xiami'=>array(
                'method' => 'GET',
                'url'    => 'http://api.xiami.com/web',
                'body'   => array(
                    'v'       => '2.0',
                    'app_key' => '1',
                    'id'      => $id,
                    'r'       => 'collect/detail',
                ),
                'format' => 'data#songs',
            ),
            'kugou'=>array(
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
            ),
            'baidu' => array(
                'method' => 'GET',
                'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                'body'   => array(
                    'method' => 'baidu.ting.diy.gedanInfo',
                    'listid' => $id,
                    'format' => 'json',
                    'from'   => 'ios',
                    'channel'=> '(null)',
                    'cuid'   => 'appstore',
                    'from'   => 'ios',
                    'version'=> '5.9.5',
                ),
                'format' => 'content',
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function url($id,$br=320){
        $API=array(
            'netease'=>array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
                'body'   => array(
                    'songmid' => $id,
                    'format'  => 'json',
                ),
                'decode' => 'tencent_url',
            ),
            'xiami'=>array(
                'method' => 'GET',
                'url'    => 'http://www.xiami.com/song/playlist/id/'.$id.'/object_name/default/object_id/0/cat/json',
                'body'   => array(),
                'decode' => 'xiami_url',
            ),
            'kugou'=>array(
                'method' => 'POST',
                'url'    => 'http://media.store.kugou.com/v1/get_res_privilege',
                'body'   => array(
                    "relate"    => 1,
                    "userid"    => 0,
                    "vip"       => 0,
                    "appid"     => 1390,
                    "token"     => "",
                    "behavior"  => "download",
                    "clientver" => "1",
                    "resource"=>array(array(
                        "id"   => 0,
                        "type" => "audio",
                        "hash" => $id,
                    )),
                ),
                'encode' => 'kugou_json',
                'decode' => 'kugou_url',
            ),
            'baidu' => array(
                'method' => 'GET',
                'url'    => 'http://music.baidu.com/data/music/fmlink',
                'body'   => array(
                    'songIds' => $id,
                    'rate'    => $br,
                    'type'    => 'mp3',
                ),
                'decode' => 'baidu_url',
            ),
        );
        $this->_temp['br']=$br;
        return self::curl($API[$this->_SITE]);
    }

    public function lyric($id){
        $API=array(
            'netease'=>array(
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
            ),
            'tencent'=>array(
                'method' => 'GET',
                'url'    => 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric.fcg',
                'body'   => array(
                    'songmid'  => $id,
                    'nobase64' => 0,
                ),
                'decode' => 'jsonp2json',
            ),
            'xiami'=>array(
                'method' => 'GET',
                'url'    => 'http://api.xiami.com/web',
                'body'   => array(
                    'v'       => '2.0',
                    'app_key' => '1',
                    'id'      => $id,
                    'r'       => 'song/detail',
                ),
                'decode' => 'xiami_lyric',
            ),
            'kugou'=>array(
                'method' => 'GET',
                'url'    => 'http://m.kugou.com/app/i/krc.php',
                'body'   => array(
                    'keyword'    => '%20-%20',
                    'timelength' => 1000000,
                    'cmd'        => 100,
                    'hash'       => $id,
                ),
                'decode' => 'kugou_lyric'
            ),
            'baidu' => array(
                'method' => 'GET',
                'url'    => 'http://tingapi.ting.baidu.com/v1/restserver/ting',
                'body'   => array(
                    'method'=>'baidu.ting.song.lry',
                    'songid'   => $id,
                    'format'=>'json',
                    'from'=>'ios',
                    'channel'=>'(null)',
                    'cuid'=>'appstore',
                    'from'=>'ios',
                    'version'=>'5.9.5',
                ),
            ),
        );
        return self::curl($API[$this->_SITE]);
    }

    public function pic($id){
        switch($this->_SITE){
            case 'netease':
                $url='https://p4.music.126.net/'.self::netease_pickey($id).'/'.$id.'.jpg?param=300z300&quality=100';
                break;
            case 'tencent':
                $url='https://y.gtimg.cn/music/photo_new/T002R300x300M000'.$id.'.jpg?max_age=2592000';
                break;
            case 'xiami':
                $data=$this->format(false)->song($id);
                $url=json_decode($data,1)['data']['song']['logo'];
                $url=str_replace(['_1.','http:','img.'],['.','https:','pic.'],$url).'@!c-400-400';
                break;
            case 'kugou':
                $API=array(
                    'method' =>'GET',
                    'url'    => 'http://tools.mobile.kugou.com/api/v1/singer_header/get_by_hash',
                    'body'   => array(
                        'hash'   => $id,
                        'size'   => 400,
                        'format' => 'json',
                    ),
                );
                $data=$this->curl($API);
                $url=json_decode($data,1)['url'];
                break;
            case 'baidu':
                $data=self::song($id);
                $data=json_decode($data,1);
                $url=$data['songinfo']['pic_big']?:$data['songinfo']['pic_small'];
        }
        $arr=array('url'=>$url);
        return json_encode($arr);
    }

    private function curlset(){
        $BASE=array(
            'netease'=>array(
                'referer'   => 'http://music.163.com/',
                'cookie'    => 'os=linux; appver=1.0.0.1026; osver=Ubuntu%2016.10; MUSIC_U=78d411095f4b022667bc8ec49e9a44cca088df057d987f5feaf066d37458e41c4a7d9447977352cf27ea9fee03f6ec4441049cea1c6bb9b6; __remember_me=true',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36',
            ),
            'tencent'=>array(
                'referer'   => 'http://y.qq.com/portal/player.html',
                'cookie'    => 'qqmusic_uin=12345678; qqmusic_key=12345678; qqmusic_fromtag=30; ts_last=y.qq.com/portal/player.html;',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36',
            ),
            'xiami'=>array(
                'referer'   => 'http://h.xiami.com/',
                'cookie'    => 'user_from=2;XMPLAYER_addSongsToggler=0;XMPLAYER_isOpen=0;_xiamitoken=123456789;',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36',
            ),
            'kugou'=>array(
                'referer'   => 'http://www.kugou.com/webkugouplayer/flash/webKugou.swf',
                'cookie'    => '_WCMID=123456789',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36',
            ),
            'baidu'=>array(
                'referer'   => 'http://ting.baidu.com/',
                'cookie'    => 'BAIDUID=123456789',
                'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.30 Safari/537.36',
            ),
        );
        return $BASE[$this->_SITE];
    }

    /**
     * 乱七八糟的函数，加密解密...
     * 正在努力重构这些代码 TAT
     */
    private function netease_AESECB($API){
        $KEY='7246674226682325323F5E6544673A51';
        $body=json_encode($API['body']);
        $body=openssl_encrypt($body,'aes-128-ecb',hex2bin($KEY));
        $body=strtoupper(bin2hex(base64_decode($body)));

        $API['body']=array(
            'eparams'=>$body,
        );
        return $API;
    }
    private function kugou_json($API){
        $API['body']=json_encode($API['body']);
        return $API;
    }
    private function jsonp2json($jsonp){
        if($jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $jsonp = substr($jsonp, strpos($jsonp, '('));
        }
        return trim($jsonp,'();');
    }
    private function tencent_singlesong($result){
        $result=json_decode($result,1);
        $data=$result['data'][0];
        $t=array(
            'songmid' => $data['mid'],
            'songname' => $data['name'],
            'albummid' => $data['album']['mid'],
        );
        foreach($t as $key=>$vo)$result['data'][0][$key]=$vo;
        return json_encode($result);
    }
    private function netease_pickey($id){
        $magic=str_split('3go8&$8*3*3h0k(2)2');
        $song_id=str_split($id);
        for($i=0;$i<count($song_id);$i++)$song_id[$i]=chr(ord($song_id[$i])^ord($magic[$i%count($magic)]));
        $result=base64_encode(md5(implode('',$song_id),1));
        $result=str_replace(['/','+'],['_','-'],$result);
        return $result;
    }
    /**
     * URL - 歌曲地址转换函数
     * 用于返回不高于指定 bitRate 的歌曲地址（默认规范化）
     */
    private function netease_url($result){
        $data=json_decode($result,1);
        $url=array(
            'url' => str_replace('http:','https:',$data['data'][0]['url']),
            'br'  => $data['data'][0]['br']/1000,
        );
        return json_encode($url);
    }
    private function tencent_url($result){
        $data=json_decode($result,1);
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
        $KEY=json_decode($this->curl($API),1)['key'];

        $type=array(
            'size_320mp3'=>array(320,'M800','mp3'),
            'size_128mp3'=>array(128,'M500','mp3'),
            'size_96aac'=>array(96,'C400','m4a'),
            'size_48aac'=>array(48,'C200','m4a'),
        );
        foreach($type as $key=>$vo){
            if($data['data'][0]['file'][$key]&&$vo[0]<=$this->_temp['br']){
                $url=array(
                    'url' => 'http://dl.stream.qqmusic.qq.com/'.$vo[1].$data['data'][0]['file']['media_mid'].'.'.$vo[2].'?vkey='.$KEY.'&guid='.$GUID.'&uid=0&fromtag=30',
                    'br'  => $vo[0],
                );
                break;
            }
        }
        return json_encode($url);
    }
    private function xiami_url($result){
        $data=json_decode($result,1);
        $max=0;
        foreach($data['data']['trackList'][0]['allAudios'] as $vo){
            if($vo['rate']<=$this->_temp['br']&&$vo['rate']>$max){
                $max=$vo['rate'];
                $url=array(
                    'url' => str_replace('http:','https:',$vo['filePath']),
                    'br'  => $vo['rate'],
                );
            }
        }
        return json_encode($url);
    }
    private function kugou_url($result){
        $data=json_decode($result,1);

        $max=0;
        $url=array();
        foreach($data['data'][0]['relate_goods'] as $vo){
            if($vo['info']['bitrate']<=$this->_temp['br']&&$vo['info']['bitrate']>$max){
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
                $t=json_decode($this->curl($API),1);
                if(isset($t['url'])){
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
    private function baidu_url($result){
        $data=json_decode($result,1);
        $url=array(
            'url' => $data['data']['songList'][0]['songLink'],
            'br'  => $data['data']['songList'][0]['rate'],
        );
        $url['url']=str_replace('http://yinyueshiting.baidu.com','https://gss0.bdstatic.com/y0s1hSulBw92lNKgpU_Z2jR7b2w6buu',$url['url']);
        return json_encode($url);
    }
    /**
     * 歌词处理模块
     * 用于规范化歌词输出
     */
    private function xiami_lyric($result){
        $result=json_decode($result,1);
        $API=array(
            'method' => 'GET',
            'url'    => $result['data']['song']['lyric'],
        );
        $data=$this->curl($API);
        $data=preg_replace('/<\d{1,8}>/','',$data);
        $arr=array(
            'lyric' => $data,
        );
        return json_encode($arr);
    }
    private function kugou_lyric($result){
        $arr=array(
            'lyric' => $result,
        );
        return json_encode($arr);
    }
    /**
     * Format - 规范化函数
     * 用于统一返回的参数，可用 ->format() 一次性开关开启
     */
    private function format_netease($data){
        $result=array(
            'id'        => $data['id'],
            'name'      => $data['name'],
            'artist'    => array(),
            'pic_id'    => $data['al']['pic_str']?:$data['al']['pic'],
            'url_id'    => $data['id'],
            'lyric_id'  => $data['id'],
        );
        if(isset($data['al']['picUrl'])){
            preg_match('/\/(\d+)\./',$data['al']['picUrl'],$match);
            $result['pic_id']=$match[1];
        }
        foreach($data['ar'] as $vo)$result['artist'][]=$vo['name'];
        return $result;
    }
    private function format_tencent($data){
        if(isset($data['musicData']))$data=$data['musicData'];
        $result=array(
            'id'        => $data['songmid'],
            'name'      => $data['songname'],
            'artist'    => array(),
            'pic_id'    => $data['albummid'],
            'url_id'    => $data['songmid'],
            'lyric_id'  => $data['songmid'],
        );
        foreach($data['singer'] as $vo)$result['artist'][]=$vo['name'];
        return $result;
    }
    private function format_xiami($data){
        $result=array(
            'id'       => $data['song_id'],
            'name'     => $data['song_name'],
            'artist'   => explode(';',$data['singers']?:$data['artist_name']),
            'pic_id'   => $data['song_id'],
            'url_id'   => $data['song_id'],
            'lyric_id' => $data['song_id'],
        );
        return $result;
    }
    private function format_kugou($data){
        $result=array(
            'id'       => $data['hash'],
            'name'     => "",
            'artist'   => array(),
            'url_id'   => $data['hash'],
            'pic_id'   => $data['hash'],
            'lyric_id' => $data['hash'],
        );
        list($result['artist'],$result['name'])=explode(' - ',$data['filename']?:$data['fileName']);
        $result['artist']=explode('、',$result['artist']);
        return $result;
    }
    private function format_baidu($data){
        $result=array(
            'id'       => $data['song_id'],
            'name'     => $data['title'],
            'artist'   => explode(',',$data['author']),
            'pic_id'   => $data['song_id'],
            'url_id'   => $data['song_id'],
            'lyric_id' => $data['song_id'],
        );
        return $result;
    }
}
