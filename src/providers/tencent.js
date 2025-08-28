import BaseProvider from './base.js';

/**
 * 腾讯音乐平台提供者
 */
export default class TencentProvider extends BaseProvider {
  constructor(meting) {
    super(meting);
    this.name = 'tencent';
  }

  /**
   * 获取腾讯音乐的请求头配置
   */
  getHeaders() {
    return {
      'Referer': 'http://y.qq.com',
      'Cookie': 'pgv_pvi=22038528; pgv_si=s3156287488; pgv_pvid=5535248600; yplayer_open=1; ts_last=y.qq.com/portal/player.html; ts_uid=4847550686; yq_index=0; qqmusic_fromtag=66; player_exist=1',
      'User-Agent': 'QQ%E9%9F%B3%E4%B9%90/54409 CFNetwork/901.1 Darwin/17.6.0 (x86_64)',
      'Accept': '*/*',
      'Accept-Language': 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
      'Connection': 'keep-alive',
      'Content-Type': 'application/x-www-form-urlencoded'
    };
  }

  /**
   * 搜索歌曲
   */
  search(keyword, option = {}) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/soso/fcgi-bin/client_search_cp',
      body: {
        format: 'json',
        p: option.page || 1,
        n: option.limit || 30,
        w: keyword,
        aggr: 1,
        lossless: 1,
        cr: 1,
        new_json: 1
      },
      format: 'data.song.list'
    };
  }

  /**
   * 获取歌曲详情
   */
  song(id) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
      body: {
        songmid: id,
        platform: 'yqq',
        format: 'json'
      },
      format: 'data'
    };
  }

  /**
   * 获取专辑信息
   */
  album(id) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_detail_cp.fcg',
      body: {
        albummid: id,
        platform: 'mac',
        format: 'json',
        newsong: 1
      },
      format: 'data.getSongInfo'
    };
  }

  /**
   * 获取艺术家作品
   */
  artist(id, limit = 50) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_singer_track_cp.fcg',
      body: {
        singermid: id,
        begin: 0,
        num: limit,
        order: 'listen',
        platform: 'mac',
        newsong: 1
      },
      format: 'data.list'
    };
  }

  /**
   * 获取播放列表
   */
  playlist(id) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_playlist_cp.fcg',
      body: {
        id: id,
        format: 'json',
        newsong: 1,
        platform: 'jqspaframe.json'
      },
      format: 'data.cdlist.0.songlist'
    };
  }

  /**
   * 获取音频播放链接
   */
  url(id, br = 320) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
      body: {
        songmid: id,
        platform: 'yqq',
        format: 'json'
      },
      decode: 'tencent_url'
    };
  }

  /**
   * 获取歌词
   */
  lyric(id) {
    return {
      method: 'GET',
      url: 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg',
      body: {
        songmid: id,
        g_tk: '5381'
      },
      decode: 'tencent_lyric'
    };
  }

  /**
   * 获取封面图片
   */
  async pic(id, size = 300) {
    const url = `https://y.gtimg.cn/music/photo_new/T002R${size}x${size}M000${id}.jpg?max_age=2592000`;
    return JSON.stringify({ url: url });
  }

  /**
   * 格式化腾讯音乐数据
   */
  format(data) {
    if (data.musicData) {
      data = data.musicData;
    }
    
    const result = {
      id: data.mid,
      name: data.name,
      artist: [],
      album: data.album.title.trim(),
      pic_id: data.album.mid,
      url_id: data.mid,
      lyric_id: data.mid,
      source: 'tencent'
    };
    
    data.singer.forEach(singer => {
      result.artist.push(singer.name);
    });
    
    return result;
  }

  /**
   * 处理腾讯音乐的解码逻辑
   */
  async handleDecode(decodeType, data) {
    if (decodeType === 'tencent_url') {
      return this.urlDecode(data);
    } else if (decodeType === 'tencent_lyric') {
      return this.lyricDecode(data);
    }
    return data;
  }

  /**
   * 腾讯音乐 URL 解码
   */
  async urlDecode(result) {
    const data = JSON.parse(result);
    const guid = Math.floor(Math.random() * 10000000000);
    
    const qualityMap = [
      ['size_flac', 999, 'F000', 'flac'],
      ['size_320mp3', 320, 'M800', 'mp3'],
      ['size_192aac', 192, 'C600', 'm4a'],
      ['size_128mp3', 128, 'M500', 'mp3'],
      ['size_96aac', 96, 'C400', 'm4a'],
      ['size_48aac', 48, 'C200', 'm4a'],
      ['size_24aac', 24, 'C100', 'm4a']
    ];
    
    let uin = '0';
    const uinMatch = this.meting.header.Cookie && this.meting.header.Cookie.match(/uin=(\d+)/);
    if (uinMatch) {
      uin = uinMatch[1];
    }
    
    const payload = {
      req_0: {
        module: 'vkey.GetVkeyServer',
        method: 'CgiGetVkey',
        param: {
          guid: String(guid),
          songmid: [],
          filename: [],
          songtype: [],
          uin: uin,
          loginflag: 1,
          platform: '20'
        }
      }
    };
    
    qualityMap.forEach(([sizeKey, br, prefix, ext]) => {
      payload.req_0.param.songmid.push(data.data[0].mid);
      payload.req_0.param.filename.push(`${prefix}${data.data[0].file.media_mid}.${ext}`);
      payload.req_0.param.songtype.push(data.data[0].type);
    });
    
    const api = {
      method: 'GET',
      url: 'https://u.y.qq.com/cgi-bin/musicu.fcg',
      body: {
        format: 'json',
        platform: 'yqq.json',
        needNewCode: 0,
        data: JSON.stringify(payload)
      }
    };
    
    const response = JSON.parse(await this.meting._exec(api));
    const vkeys = response.req_0.data.midurlinfo;
    
    let url;
    for (let i = 0; i < qualityMap.length; i++) {
      const [sizeKey, br, prefix, ext] = qualityMap[i];
      if (data.data[0].file[sizeKey] && br <= this.meting.temp.br) {
        if (vkeys[i].vkey) {
          url = {
            url: response.req_0.data.sip[0] + vkeys[i].purl,
            size: data.data[0].file[sizeKey],
            br: br
          };
          break;
        }
      }
    }
    
    if (!url) {
      url = {
        url: '',
        size: 0,
        br: -1
      };
    }
    
    return JSON.stringify(url);
  }

  /**
   * 腾讯音乐歌词解码
   */
  lyricDecode(result) {
    const jsonStr = result.substring(18, result.length - 1);
    const data = JSON.parse(jsonStr);
    
    const lyricData = {
      lyric: data.lyric ? Buffer.from(data.lyric, 'base64').toString() : '',
      tlyric: data.trans ? Buffer.from(data.trans, 'base64').toString() : ''
    };
    
    return JSON.stringify(lyricData);
  }
}