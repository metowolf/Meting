import BaseProvider from './base.js';

/**
 * 酷我音乐平台提供者
 */
export default class KuwoProvider extends BaseProvider {
  constructor(meting) {
    super(meting);
    this.name = 'kuwo';
  }

  /**
   * 获取酷我音乐的请求头配置
   */
  getHeaders() {
    return {
      'Cookie': 'Hm_lvt_cdb524f42f0ce19b169a8071123a4797=1623339177,1623339183; _ga=GA1.2.1195980605.1579367081; Hm_lpvt_cdb524f42f0ce19b169a8071123a4797=1623339982; kw_token=3E7JFQ7MRPL; _gid=GA1.2.747985028.1623339179; _gat=1',
      'csrf': '3E7JFQ7MRPL',
      'Host': 'www.kuwo.cn',
      'Referer': 'http://www.kuwo.cn/',
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36'
    };
  }

  /**
   * 搜索歌曲
   */
  search(keyword, option = {}) {
    return {
      method: 'GET',
      url: 'http://www.kuwo.cn/api/www/search/searchMusicBykeyWord',
      body: {
        key: keyword,
        pn: option.page || 1,
        rn: option.limit || 30,
        httpsStatus: 1
      },
      format: 'data.list'
    };
  }

  /**
   * 获取歌曲详情
   */
  song(id) {
    return {
      method: 'GET',
      url: 'http://www.kuwo.cn/api/www/music/musicInfo',
      body: {
        mid: id,
        httpsStatus: 1
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
      url: 'http://www.kuwo.cn/api/www/album/albumInfo',
      body: {
        albumId: id,
        pn: 1,
        rn: 1000,
        httpsStatus: 1
      },
      format: 'data.musicList'
    };
  }

  /**
   * 获取艺术家作品
   */
  artist(id, limit = 50) {
    return {
      method: 'GET',
      url: 'http://www.kuwo.cn/api/www/artist/artistMusic',
      body: {
        artistid: id,
        pn: 1,
        rn: limit,
        httpsStatus: 1
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
      url: 'http://www.kuwo.cn/api/www/playlist/playListInfo',
      body: {
        pid: id,
        pn: 1,
        rn: 1000,
        httpsStatus: 1
      },
      format: 'data.musicList'
    };
  }

  /**
   * 获取音频播放链接
   */
  url(id, br = 320) {
    return {
      method: 'GET',
      url: 'http://www.kuwo.cn/api/v1/www/music/playUrl',
      body: {
        mid: id,
        type: 'music',
        httpsStatus: 1
      },
      decode: 'kuwo_url'
    };
  }

  /**
   * 获取歌词
   */
  lyric(id) {
    return {
      method: 'GET',
      url: 'http://m.kuwo.cn/newh5/singles/songinfoandlrc',
      body: {
        musicId: id,
        httpsStatus: 1
      },
      decode: 'kuwo_lyric'
    };
  }

  /**
   * 获取封面图片
   */
  async pic(id, size = 300) {
    const format = this.meting.isFormat;
    const data = await this.meting.format(false).song(id);
    this.meting.isFormat = format;
    const songData = JSON.parse(data);
    const url = songData.data.pic || songData.data.albumpic;
    return JSON.stringify({ url: url });
  }

  /**
   * 格式化酷我音乐数据
   */
  format(data) {
    return {
      id: data.rid,
      name: data.name,
      artist: data.artist ? data.artist.split('&') : [],
      album: data.album || '',
      pic_id: data.rid,
      url_id: data.rid,
      lyric_id: data.rid,
      source: 'kuwo'
    };
  }

  /**
   * 处理酷我音乐的解码逻辑
   */
  async handleDecode(decodeType, data) {
    if (decodeType === 'kuwo_url') {
      return this.urlDecode(data);
    } else if (decodeType === 'kuwo_lyric') {
      return this.lyricDecode(data);
    }
    return data;
  }

  /**
   * 酷我音乐 URL 解码
   */
  urlDecode(result) {
    const data = JSON.parse(result);
    
    let url;
    if (data.code === 200 && data.data && data.data.url) {
      url = {
        url: data.data.url,
        br: 128
      };
    } else {
      url = {
        url: '',
        br: -1
      };
    }
    
    return JSON.stringify(url);
  }

  /**
   * 酷我音乐歌词解码
   */
  lyricDecode(result) {
    const data = JSON.parse(result);
    
    let lyric = '';
    if (data.data && data.data.lrclist && data.data.lrclist.length > 0) {
      data.data.lrclist.forEach(item => {
        const time = parseFloat(item.time);
        const min = Math.floor(time / 60).toString().padStart(2, '0');
        const sec = Math.floor(time % 60).toString().padStart(2, '0');
        const msec = ((time % 1) * 100).toFixed(0).padStart(2, '0');
        
        lyric += `[${min}:${sec}.${msec}]${item.lineLyric}\n`;
      });
    }
    
    const lyricData = {
      lyric: lyric,
      tlyric: ''
    };
    
    return JSON.stringify(lyricData);
  }
}