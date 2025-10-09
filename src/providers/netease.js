import crypto from 'crypto';
import BaseProvider from './base.js';

// eapi 相关常量
const EAPI_KEY = 'e82ckenh8dichen8';
const EAPI_IV = Buffer.from('0102030405060708');

/**
 * 网易云音乐平台提供者
 */
export default class NeteaseProvider extends BaseProvider {
  constructor(meting) {
    super(meting);
    this.name = 'netease';
  }

  /**
   * 获取网易云音乐的请求头配置（EAPI）
   */
  getHeaders() {
    const timestamp = Date.now().toString();
    const deviceId = this._generateDeviceId();

    return {
      'Referer': 'music.163.com',
      'Cookie': `osver=android; appver=8.7.01; os=android; deviceId=${deviceId}; channel=netease; requestId=${timestamp}_${Math.floor(Math.random() * 1000).toString().padStart(4, '0')}; __remember_me=true`,
      'User-Agent': 'Mozilla/5.0 (Linux; Android 11; M2007J3SC Build/RKQ1.200826.002; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/77.0.3865.120 MQQBrowser/6.2 TBS/045714 Mobile Safari/537.36 NeteaseMusic/8.7.01',
      'Accept': '*/*',
      'Accept-Language': 'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
      'Connection': 'keep-alive',
      'Content-Type': 'application/x-www-form-urlencoded'
    };
  }

  /**
   * 搜索歌曲
   */
  search(keyword, option = {}) {
    return {
      method: 'POST',
      url: 'http://music.163.com/api/cloudsearch/pc',
      body: {
        s: keyword,
        type: option.type || 1,
        limit: option.limit || 30,
        total: 'true',
        offset: (option.page && option.limit) ? (option.page - 1) * option.limit : 0
      },
      encode: 'netease_eapi',
      format: 'result.songs'
    };
  }

  /**
   * 获取歌曲详情
   */
  song(id) {
    return {
      method: 'POST',
      url: 'http://music.163.com/api/v3/song/detail/',
      body: {
        c: `[{"id":${id},"v":0}]`
      },
      encode: 'netease_eapi',
      format: 'songs'
    };
  }

  /**
   * 获取专辑信息
   */
  album(id) {
    return {
      method: 'POST',
      url: `http://music.163.com/api/v1/album/${id}`,
      body: {
        total: 'true',
        offset: '0',
        id: id,
        limit: '1000',
        ext: 'true',
        private_cloud: 'true'
      },
      encode: 'netease_eapi',
      format: 'songs'
    };
  }

  /**
   * 获取艺术家作品
   */
  artist(id, limit = 50) {
    return {
      method: 'POST',
      url: `http://music.163.com/api/v1/artist/${id}`,
      body: {
        ext: 'true',
        private_cloud: 'true',
        top: limit,
        id: id
      },
      encode: 'netease_eapi',
      format: 'hotSongs'
    };
  }

  /**
   * 获取播放列表
   */
  playlist(id) {
    return {
      method: 'POST',
      url: 'http://music.163.com/api/v6/playlist/detail',
      body: {
        s: '0',
        id: id,
        n: '1000',
        t: '0'
      },
      encode: 'netease_eapi',
      format: 'playlist.tracks'
    };
  }

  /**
   * 获取音频播放链接
   */
  url(id, br = 320) {
    return {
      method: 'POST',
      url: 'http://music.163.com/api/song/enhance/player/url',
      body: {
        ids: [id],
        br: br * 1000
      },
      encode: 'netease_eapi',
      decode: 'netease_url'
    };
  }

  /**
   * 获取歌词
   */
  lyric(id) {
    return {
      method: 'POST',
      url: 'http://music.163.com/api/song/lyric',
      body: {
        id: id,
        os: 'linux',
        lv: -1,
        kv: -1,
        tv: -1
      },
      encode: 'netease_eapi',
      decode: 'netease_lyric'
    };
  }

  /**
   * 获取封面图片
   */
  async pic(id, size = 300) {
    const url = `https://p3.music.126.net/${this._encryptId(id)}/${id}.jpg?param=${size}y${size}`;
    return JSON.stringify({ url: url });
  }

  /**
   * 格式化网易云音乐数据
   */
  format(data) {
    const result = {
      id: data.id,
      name: data.name,
      artist: [],
      album: data.al.name,
      pic_id: data.al.pic_str || data.al.pic,
      url_id: data.id,
      lyric_id: data.id,
      source: 'netease'
    };
    
    if (data.al.picUrl) {
      const match = data.al.picUrl.match(/\/(\d+)\./);
      if (match) {
        result.pic_id = match[1];
      }
    }
    
    data.ar.forEach(artist => {
      result.artist.push(artist.name);
    });
    
    return result;
  }

  /**
   * 处理网易云音乐的编码逻辑
   */
  async handleEncode(api) {
    if (api.encode === 'netease_eapi') {
      return this.eapiEncrypt(api);
    }
    return api;
  }

  /**
   * 网易云音乐 EAPI 加密
   */
  async eapiEncrypt(api) {
    const text = JSON.stringify(api.body);
    const url = api.url.replace(/https?:\/\/[^\/]+/, '');

    // 构建 eapi 加密消息
    const message = `nobody${url}use${text}md5forencrypt`;
    const digest = crypto.createHash('md5').update(message).digest('hex');
    const data = `${url}-36cd479b6b5-${text}-36cd479b6b5-${digest}`;

    // AES-128-ECB 加密
    const cipher = crypto.createCipheriv('aes-128-ecb', Buffer.from(EAPI_KEY, 'utf8'), null);
    cipher.setAutoPadding(true);
    let encrypted = cipher.update(data, 'utf8', 'hex');
    encrypted += cipher.final('hex');

    // 转换 URL 路径
    api.url = api.url.replace('/api/', '/eapi/');

    // 构建 eapi 请求体
    api.body = {
      params: encrypted.toUpperCase()
    };

    return api;
  }

  /**
   * 网易云音乐 URL 解码
   */
  urlDecode(result) {
    const data = JSON.parse(result);
    let url;
    
    if (data.data[0].uf && data.data[0].uf.url) {
      data.data[0].url = data.data[0].uf.url;
    }
    
    if (data.data[0].url) {
      url = {
        url: data.data[0].url,
        size: data.data[0].size,
        br: data.data[0].br / 1000
      };
    } else {
      url = {
        url: '',
        size: 0,
        br: -1
      };
    }
    
    return JSON.stringify(url);
  }

  /**
   * 网易云音乐歌词解码
   */
  lyricDecode(result) {
    const data = JSON.parse(result);
    const lyricData = {
      lyric: (data.lrc && data.lrc.lyric) ? data.lrc.lyric : '',
      tlyric: (data.tlyric && data.tlyric.lyric) ? data.tlyric.lyric : ''
    };
    
    return JSON.stringify(lyricData);
  }

  // ========== 私有工具方法 ==========

  /**
   * 生成随机 IP 地址
   */
  _generateRandomIP() {
    const min = 1884815360; // 112.74.200.0
    const max = 1884890111; // 112.74.243.255
    const randomInt = Math.floor(Math.random() * (max - min + 1)) + min;
    
    return [
      (randomInt >>> 24) & 0xFF,
      (randomInt >>> 16) & 0xFF,
      (randomInt >>> 8) & 0xFF,
      randomInt & 0xFF
    ].join('.');
  }

  /**
   * 生成随机十六进制字符串
   */
  _getRandomHex(length) {
    return crypto.randomBytes(Math.ceil(length / 2))
      .toString('hex')
      .slice(0, length);
  }

  /**
   * 生成设备 ID
   */
  _generateDeviceId() {
    // 生成类似移动端的设备 ID
    const randomBytes = crypto.randomBytes(16);
    const deviceId = randomBytes.toString('hex').toUpperCase();
    return deviceId;
  }

  /**
   * 网易云音乐 ID 加密
   */
  _encryptId(id) {
    const magic = '3go8&$8*3*3h0k(2)2'.split('');
    const song_id = String(id).split('');
    
    for (let i = 0; i < song_id.length; i++) {
      song_id[i] = String.fromCharCode(
        song_id[i].charCodeAt(0) ^ magic[i % magic.length].charCodeAt(0)
      );
    }
    
    const result = crypto.createHash('md5')
      .update(song_id.join(''), 'binary')
      .digest('base64')
      .replace(/\//g, '_')
      .replace(/\+/g, '-');
    
    return result;
  }

  /**
   * 大数运算相关工具方法
   */
  _bchexdec(hex) {
    return BigInt('0x' + hex);
  }

  _str2hex(str) {
    return Buffer.from(str, 'utf8').toString('hex');
  }

  /**
   * 大数幂模运算
   */
  _powMod(base, exponent, modulus) {
    if (modulus === 1n) return 0n;
    let result = 1n;
    base = base % modulus;
    while (exponent > 0n) {
      if (exponent % 2n === 1n) {
        result = (result * base) % modulus;
      }
      exponent = exponent >> 1n;
      base = (base * base) % modulus;
    }
    return result;
  }
}
