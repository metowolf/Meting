import crypto from 'crypto';
import BaseProvider from './base.js';

/**
 * 虾米音乐平台提供者
 */
export default class XiamiProvider extends BaseProvider {
  constructor(meting) {
    super(meting);
    this.name = 'xiami';
  }

  /**
   * 获取虾米音乐的请求头配置
   */
  getHeaders() {
    return {
      'Cookie': '_m_h5_tk=15d3402511a022796d88b249f83fb968_1511163656929; _m_h5_tk_enc=b6b3e64d81dae577fc314b5c5692df3c',
      'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_5) AppleWebKit/537.36 (KHTML, like Gecko) XIAMI-MUSIC/3.1.1 Chrome/56.0.2924.87 Electron/1.6.11 Safari/537.36',
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept-Language': 'zh-CN'
    };
  }

  /**
   * 搜索歌曲
   */
  search(keyword, option = {}) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.search.searchservice.searchsongs/1.0/',
      body: {
        data: {
          key: keyword,
          pagingVO: {
            page: option.page || 1,
            pageSize: option.limit || 30
          }
        },
        r: 'mtop.alimusic.search.searchservice.searchsongs'
      },
      encode: 'xiami_sign',
      format: 'data.data.songs'
    };
  }

  /**
   * 获取歌曲详情
   */
  song(id) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.songservice.getsongdetail/1.0/',
      body: {
        data: {
          songId: id
        },
        r: 'mtop.alimusic.music.songservice.getsongdetail'
      },
      encode: 'xiami_sign',
      format: 'data.data.songDetail'
    };
  }

  /**
   * 获取专辑信息
   */
  album(id) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.albumservice.getalbumdetail/1.0/',
      body: {
        data: {
          albumId: id
        },
        r: 'mtop.alimusic.music.albumservice.getalbumdetail'
      },
      encode: 'xiami_sign',
      format: 'data.data.albumDetail.songs'
    };
  }

  /**
   * 获取艺术家作品
   */
  artist(id, limit = 50) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.songservice.getartistsongs/1.0/',
      body: {
        data: {
          artistId: id,
          pagingVO: {
            page: 1,
            pageSize: limit
          }
        },
        r: 'mtop.alimusic.music.songservice.getartistsongs'
      },
      encode: 'xiami_sign',
      format: 'data.data.songs'
    };
  }

  /**
   * 获取播放列表
   */
  playlist(id) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.list.collectservice.getcollectdetail/1.0/',
      body: {
        data: {
          listId: id,
          isFullTags: false,
          pagingVO: {
            page: 1,
            pageSize: 1000
          }
        },
        r: 'mtop.alimusic.music.list.collectservice.getcollectdetail'
      },
      encode: 'xiami_sign',
      format: 'data.data.collectDetail.songs'
    };
  }

  /**
   * 获取音频播放链接
   */
  url(id, br = 320) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.songservice.getsongs/1.0/',
      body: {
        data: {
          songIds: [id]
        },
        r: 'mtop.alimusic.music.songservice.getsongs'
      },
      encode: 'xiami_sign',
      decode: 'xiami_url'
    };
  }

  /**
   * 获取歌词
   */
  lyric(id) {
    return {
      method: 'GET',
      url: 'https://acs.m.xiami.com/h5/mtop.alimusic.music.lyricservice.getsonglyrics/1.0/',
      body: {
        data: {
          songId: id
        },
        r: 'mtop.alimusic.music.lyricservice.getsonglyrics'
      },
      encode: 'xiami_sign',
      decode: 'xiami_lyric'
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
    let url = songData.data.data.songDetail.albumLogo;
    url = url.replace('http:', 'https:') + `@1e_1c_100Q_${size}h_${size}w`;
    return JSON.stringify({ url: url });
  }

  /**
   * 格式化虾米音乐数据
   */
  format(data) {
    const result = {
      id: data.songId,
      name: data.songName,
      artist: [],
      album: data.albumName,
      pic_id: data.songId,
      url_id: data.songId,
      lyric_id: data.songId,
      source: 'xiami'
    };
    
    data.singerVOs.forEach(singer => {
      result.artist.push(singer.artistName);
    });
    
    return result;
  }

  /**
   * 处理虾米音乐的编码/解码逻辑
   */
  async handleEncode(api) {
    if (api.encode === 'xiami_sign') {
      return this.signEncrypt(api);
    }
    return api;
  }

  async handleDecode(decodeType, data) {
    if (decodeType === 'xiami_url') {
      return this.urlDecode(data);
    } else if (decodeType === 'xiami_lyric') {
      return this.lyricDecode(data);
    }
    return data;
  }

  /**
   * 虾米音乐签名加密
   */
  async signEncrypt(api) {
    // 获取 token
    const tokenUrl = 'https://acs.m.xiami.com/h5/mtop.alimusic.recommend.songservice.getdailysongs/1.0/?appKey=12574478&t=1560663823000&dataType=json&data=%7B%22requestStr%22%3A%22%7B%5C%22header%5C%22%3A%7B%5C%22platformId%5C%22%3A%5C%22mac%5C%22%7D%2C%5C%22model%5C%22%3A%5B%5D%7D%22%7D&api=mtop.alimusic.recommend.songservice.getdailysongs&v=1.0&type=originaljson&sign=22ad1377ee193f3e2772c17c6192b17c';
    
    await this.meting._curl(tokenUrl, null, true);
    const cookieMatch = this.meting.raw.match(/_m_h5[^;]+/g);
    if (cookieMatch && cookieMatch.length >= 2) {
      this.meting.header['Cookie'] = cookieMatch[0] + '; ' + cookieMatch[1];
    }
    
    const data = JSON.stringify({
      requestStr: JSON.stringify({
        header: {
          platformId: 'mac'
        },
        model: api.body.data
      })
    });
    
    const appkey = '12574478';
    const cookie = this.meting.header['Cookie'];
    const tokenMatch = cookie.match(/_m_h5_tk=([^_]+)/);
    const token = tokenMatch ? tokenMatch[1] : '';
    const t = Date.now();
    
    const signStr = `${token}&${t}&${appkey}&${data}`;
    const sign = crypto.createHash('md5').update(signStr).digest('hex');
    
    api.body = {
      appKey: appkey,
      t: t,
      dataType: 'json',
      data: data,
      api: api.body.r,
      v: '1.0',
      type: 'originaljson',
      sign: sign
    };
    
    return api;
  }

  /**
   * 虾米音乐 URL 解码
   */
  urlDecode(result) {
    const data = JSON.parse(result);
    
    const qualityMap = {
      's': 740,
      'h': 320,
      'l': 128,
      'f': 64,
      'e': 32
    };
    
    let maxBr = 0;
    let url;
    
    data.data.data.songs[0].listenFiles.forEach(file => {
      const br = qualityMap[file.quality];
      if (br <= this.meting.temp.br && br > maxBr) {
        maxBr = br;
        url = {
          url: file.listenFile,
          size: file.fileSize,
          br: br
        };
      }
    });
    
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
   * 虾米音乐歌词解码
   */
  lyricDecode(result) {
    const data = JSON.parse(result);
    
    let lyricData;
    if (data.data.data.lyrics.length > 0) {
      let content = data.data.data.lyrics[0].content;
      content = content.replace(/<[^>]+>/g, '');
      
      const matches = content.match(/\[([\d:\.]+)\](.*)\s\[x-trans\](.*)/gi);
      if (matches) {
        const lyricLines = [];
        const tlyricLines = [];
        
        matches.forEach(match => {
          const parts = match.match(/\[([\d:\.]+)\](.*)\s\[x-trans\](.*)/i);
          if (parts) {
            lyricLines.push(`[${parts[1]}]${parts[2]}`);
            tlyricLines.push(`[${parts[1]}]${parts[3]}`);
          }
        });
        
        lyricData = {
          lyric: content.replace(/\[([\d:\.]+)\](.*)\s\[x-trans\](.*)/gi, (match, time, lyric) => `[${time}]${lyric}`),
          tlyric: content.replace(/\[([\d:\.]+)\](.*)\s\[x-trans\](.*)/gi, (match, time, lyric, trans) => `[${time}]${trans}`)
        };
      } else {
        lyricData = {
          lyric: content,
          tlyric: ''
        };
      }
    } else {
      lyricData = {
        lyric: '',
        tlyric: ''
      };
    }
    
    return JSON.stringify(lyricData);
  }
}