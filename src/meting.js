/**
 * Meting music framework - Node.js version
 * https://i-meto.com
 * https://github.com/metowolf/Meting
 *
 * Copyright 2019, METO Sheel <i@i-meto.com>
 * Released under the MIT license
 */

import crypto from 'crypto';
import { URLSearchParams } from 'url';

class Meting {
  constructor(server = 'netease') {
    this.VERSION = '1.5.11';
    this.raw = null;
    this.data = null;
    this.info = null;
    this.error = null;
    this.status = null;
    this.temp = {};
    
    this.server = null;
    this.proxy = null;
    this.isFormat = false;
    this.header = {};
    
    this.site(server);
  }

  // 设置音乐平台
  site(server) {
    const supportedSites = ['netease', 'tencent', 'xiami', 'kugou', 'baidu', 'kuwo'];
    this.server = supportedSites.includes(server) ? server : 'netease';
    this.header = this._curlset();
    return this;
  }

  // 设置 Cookie
  cookie(cookie) {
    this.header['Cookie'] = cookie;
    return this;
  }

  // 设置数据格式化
  format(format = true) {
    this.isFormat = format;
    return this;
  }

  // 设置代理
  proxy(proxy) {
    this.proxy = proxy;
    return this;
  }

  // 执行 API 请求的主方法
  async _exec(api) {
    // 如果有编码方法，先进行编码
    if (api.encode) {
      api = await this[api.encode](api);
    }

    // 处理 GET 请求的参数
    if (api.method === 'GET' && api.body) {
      const params = new URLSearchParams(api.body);
      api.url += '?' + params.toString();
      api.body = null;
    }

    // 发送 HTTP 请求
    await this._curl(api.url, api.body);

    // 如果不需要格式化，直接返回原始数据
    if (!this.isFormat) {
      return this.raw;
    }

    this.data = this.raw;

    // 如果有解码方法，进行解码
    if (api.decode) {
      this.data = await this[api.decode](this.data);
    }

    // 如果有格式化规则，进行数据清理
    if (api.format) {
      this.data = this._clean(this.data, api.format);
    }

    return this.data;
  }

  // HTTP 请求方法 - 使用 fetch API
  async _curl(url, payload = null, headerOnly = false) {
    const options = {
      method: payload ? 'POST' : 'GET',
      headers: { ...this.header }
    };

    // 处理请求体
    if (payload) {
      if (typeof payload === 'object' && !Buffer.isBuffer(payload) && typeof payload !== 'string') {
        payload = new URLSearchParams(payload).toString();
        options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      }
      options.body = payload;
    }

    // 添加超时控制
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 20000);
    options.signal = controller.signal;

    let retries = 3;
    const makeRequest = async () => {
      try {
        const response = await fetch(url, options);
        
        clearTimeout(timeoutId);
        
        // 存储响应信息
        this.info = {
          statusCode: response.status,
          headers: Object.fromEntries(response.headers.entries())
        };

        // 获取响应数据
        const data = await response.text();
        this.raw = data;
        this.error = null;
        this.status = '';
        
        return this;
      } catch (err) {
        clearTimeout(timeoutId);
        
        // 处理错误
        if (err.name === 'AbortError') {
          this.error = 'TIMEOUT';
          this.status = 'Request timeout';
        } else {
          this.error = err.code || err.name;
          this.status = err.message;
        }
        
        // 重试机制
        if (retries > 0) {
          retries--;
          await new Promise(resolve => setTimeout(resolve, 1000));
          return makeRequest();
        } else {
          return this;
        }
      }
    };

    return await makeRequest();
  }

  // 数据提取方法
  _pickup(array, rule) {
    const parts = rule.split('.');
    let result = array;
    
    for (const part of parts) {
      if (!result || typeof result !== 'object' || !(part in result)) {
        return {};
      }
      result = result[part];
    }
    
    return result;
  }

  // 数据清理方法
  _clean(raw, rule) {
    let data;
    try {
      data = JSON.parse(raw);
    } catch (e) {
      return JSON.stringify([]);
    }

    if (rule) {
      data = this._pickup(data, rule);
    }

    if (!Array.isArray(data) && typeof data === 'object' && data !== null) {
      data = [data];
    }

    if (!Array.isArray(data)) {
      return JSON.stringify([]);
    }

    const formatMethod = `_format_${this.server}`;
    if (typeof this[formatMethod] === 'function') {
      const result = data.map(item => this[formatMethod](item));
      return JSON.stringify(result);
    }

    return JSON.stringify(data);
  }

  // 为不同平台设置请求头
  _curlset() {
    switch (this.server) {
      case 'netease':
        return {
          'Referer': 'https://music.163.com/',
          'Cookie': 'appver=8.2.30; os=iPhone OS; osver=15.0; EVNSM=1.0.0; buildver=2206; channel=distribution; machineid=iPhone13.3',
          'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 CloudMusic/0.1.1 NeteaseMusic/8.2.30',
          'X-Real-IP': this._generateRandomIP(),
          'Accept': '*/*',
          'Accept-Language': 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
          'Connection': 'keep-alive',
          'Content-Type': 'application/x-www-form-urlencoded'
        };
      
      case 'tencent':
        return {
          'Referer': 'http://y.qq.com',
          'Cookie': 'pgv_pvi=22038528; pgv_si=s3156287488; pgv_pvid=5535248600; yplayer_open=1; ts_last=y.qq.com/portal/player.html; ts_uid=4847550686; yq_index=0; qqmusic_fromtag=66; player_exist=1',
          'User-Agent': 'QQ%E9%9F%B3%E4%B9%90/54409 CFNetwork/901.1 Darwin/17.6.0 (x86_64)',
          'Accept': '*/*',
          'Accept-Language': 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
          'Connection': 'keep-alive',
          'Content-Type': 'application/x-www-form-urlencoded'
        };
      
      case 'xiami':
        return {
          'Cookie': '_m_h5_tk=15d3402511a022796d88b249f83fb968_1511163656929; _m_h5_tk_enc=b6b3e64d81dae577fc314b5c5692df3c',
          'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_5) AppleWebKit/537.36 (KHTML, like Gecko) XIAMI-MUSIC/3.1.1 Chrome/56.0.2924.87 Electron/1.6.11 Safari/537.36',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept-Language': 'zh-CN'
        };
      
      case 'kugou':
        return {
          'User-Agent': 'IPhone-8990-searchSong',
          'UNI-UserAgent': 'iOS11.4-Phone8990-1009-0-WiFi'
        };
      
      case 'baidu':
        return {
          'Cookie': `BAIDUID=${this._getRandomHex(32)}:FG=1`,
          'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) baidu-music/1.2.1 Chrome/66.0.3359.181 Electron/3.0.5 Safari/537.36',
          'Accept': '*/*',
          'Content-Type': 'application/json;charset=UTF-8',
          'Accept-Language': 'zh-CN'
        };
      
      case 'kuwo':
        return {
          'Cookie': 'Hm_lvt_cdb524f42f0ce19b169a8071123a4797=1623339177,1623339183; _ga=GA1.2.1195980605.1579367081; Hm_lpvt_cdb524f42f0ce19b169a8071123a4797=1623339982; kw_token=3E7JFQ7MRPL; _gid=GA1.2.747985028.1623339179; _gat=1',
          'csrf': '3E7JFQ7MRPL',
          'Host': 'www.kuwo.cn',
          'Referer': 'http://www.kuwo.cn/',
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36'
        };
      
      default:
        return {};
    }
  }

  // 生成随机十六进制字符串
  _getRandomHex(length) {
    return crypto.randomBytes(Math.ceil(length / 2))
      .toString('hex')
      .slice(0, length);
  }

  // 生成随机 IP 地址
  _generateRandomIP() {
    const min = 1884815360; // 112.74.0.0
    const max = 1884890111; // 112.74.18.255
    const randomInt = Math.floor(Math.random() * (max - min + 1)) + min;
    
    return [
      (randomInt >>> 24) & 0xFF,
      (randomInt >>> 16) & 0xFF,
      (randomInt >>> 8) & 0xFF,
      randomInt & 0xFF
    ].join('.');
  }

  // 工具方法：大数运算相关
  _bchexdec(hex) {
    return BigInt('0x' + hex);
  }

  _bcdechex(dec) {
    return dec.toString(16);
  }

  _str2hex(str) {
    return Buffer.from(str, 'utf8').toString('hex');
  }

  // 网易云音乐 AES 加密
  async netease_AESCBC(api) {
    const modulus = '157794750267131502212476817800345498121872783333389747424011531025366277535262539913701806290766479189477533597854989606803194253978660329941980786072432806427833685472618792592200595694346872951301770580765135349259590167490536138082469680638514416594216629258349130257685001248172188325316586707301643237607';
    const pubkey = '65537';
    const nonce = '0CoJUm6Qyw8W8jud';
    const vi = '0102030405060708';
    
    // 生成随机密钥
    const skey = this._getRandomHex(16);
    
    let body = JSON.stringify(api.body);
    
    // 两次 AES 加密
    const cipher1 = crypto.createCipheriv('aes-128-cbc', nonce, vi);
    cipher1.setAutoPadding(true);
    let encrypted1 = cipher1.update(body, 'utf8', 'base64');
    encrypted1 += cipher1.final('base64');
    
    const cipher2 = crypto.createCipheriv('aes-128-cbc', skey, vi);
    cipher2.setAutoPadding(true);
    let encrypted2 = cipher2.update(encrypted1, 'utf8', 'base64');
    encrypted2 += cipher2.final('base64');
    
    // RSA 加密密钥
    const reversedSkey = skey.split('').reverse().join('');
    const skeyBigInt = this._bchexdec(this._str2hex(reversedSkey));
    const modBigInt = BigInt(modulus);
    const pubkeyBigInt = BigInt(pubkey);
    
    const encryptedSkey = this._powMod(skeyBigInt, pubkeyBigInt, modBigInt);
    const encSecKey = encryptedSkey.toString(16).padStart(256, '0');
    
    api.url = api.url.replace('/api/', '/weapi/');
    api.body = {
      params: encrypted2,
      encSecKey: encSecKey
    };
    
    return api;
  }

  // 百度音乐 AES 加密
  async baidu_AESCBC(api) {
    const key = 'DBEECF8C50FD160E';
    const vi = '1231021386755796';
    
    const data = `songid=${api.body.songid}&ts=${Date.now()}`;
    
    const cipher = crypto.createCipheriv('aes-128-cbc', key, vi);
    cipher.setAutoPadding(true);
    let encrypted = cipher.update(data, 'utf8', 'base64');
    encrypted += cipher.final('base64');
    
    api.body.e = encrypted;
    
    return api;
  }

  // 虾米音乐签名
  async xiami_sign(api) {
    // 获取 token
    const tokenUrl = 'https://acs.m.xiami.com/h5/mtop.alimusic.recommend.songservice.getdailysongs/1.0/?appKey=12574478&t=1560663823000&dataType=json&data=%7B%22requestStr%22%3A%22%7B%5C%22header%5C%22%3A%7B%5C%22platformId%5C%22%3A%5C%22mac%5C%22%7D%2C%5C%22model%5C%22%3A%5B%5D%7D%22%7D&api=mtop.alimusic.recommend.songservice.getdailysongs&v=1.0&type=originaljson&sign=22ad1377ee193f3e2772c17c6192b17c';
    
    await this._curl(tokenUrl, null, true);
    const cookieMatch = this.raw.match(/_m_h5[^;]+/g);
    if (cookieMatch && cookieMatch.length >= 2) {
      this.header['Cookie'] = cookieMatch[0] + '; ' + cookieMatch[1];
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
    const cookie = this.header['Cookie'];
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

  // 大数幂模运算
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

  // 网易云音乐 ID 加密
  _netease_encryptId(id) {
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

  // 搜索功能
  async search(keyword, option = {}) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: 'http://music.163.com/api/cloudsearch/pc',
          body: {
            s: keyword,
            type: option.type || 1,
            limit: option.limit || 30,
            total: 'true',
            offset: (option.page && option.limit) ? (option.page - 1) * option.limit : 0
          },
          encode: 'netease_AESCBC',
          format: 'result.songs'
        };
        break;
        
      case 'tencent':
        api = {
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
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'GET',
          url: 'http://mobilecdn.kugou.com/api/v3/search/song',
          body: {
            api_ver: 1,
            area_code: 1,
            correct: 1,
            pagesize: option.limit || 30,
            plat: 2,
            tag: 1,
            sver: 5,
            showtype: 10,
            page: option.page || 1,
            keyword: keyword,
            version: 8990
          },
          format: 'data.info'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.search.merge',
            isNew: 1,
            platform: 'darwin',
            page_no: option.page || 1,
            query: keyword,
            version: '11.2.1',
            page_size: option.limit || 30
          },
          format: 'result.song_info.song_list'
        };
        break;
        
      case 'kuwo':
        api = {
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
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取歌曲详情
  async song(id) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: 'http://music.163.com/api/v3/song/detail/',
          body: {
            c: `[{"id":${id},"v":0}]`
          },
          encode: 'netease_AESCBC',
          format: 'songs'
        };
        break;
        
      case 'tencent':
        api = {
          method: 'GET',
          url: 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
          body: {
            songmid: id,
            platform: 'yqq',
            format: 'json'
          },
          format: 'data'
        };
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'POST',
          url: 'http://m.kugou.com/app/i/getSongInfo.php',
          body: {
            cmd: 'playInfo',
            hash: id,
            from: 'mkugou'
          },
          format: ''
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.song.getInfos',
            songid: id,
            res: 1,
            platform: 'darwin',
            version: '1.0.0'
          },
          encode: 'baidu_AESCBC',
          format: 'songinfo'
        };
        break;
        
      case 'kuwo':
        api = {
          method: 'GET',
          url: 'http://www.kuwo.cn/api/www/music/musicInfo',
          body: {
            mid: id,
            httpsStatus: 1
          },
          format: 'data'
        };
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取专辑信息
  async album(id) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
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
          encode: 'netease_AESCBC',
          format: 'songs'
        };
        break;
        
      case 'tencent':
        api = {
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
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'GET',
          url: 'http://mobilecdn.kugou.com/api/v3/album/song',
          body: {
            albumid: id,
            area_code: 1,
            plat: 2,
            page: 1,
            pagesize: -1,
            version: 8990
          },
          format: 'data.info'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.album.getAlbumInfo',
            album_id: id,
            platform: 'darwin',
            version: '11.2.1'
          },
          format: 'songlist'
        };
        break;
        
      case 'kuwo':
        api = {
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
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取艺术家作品
  async artist(id, limit = 50) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: `http://music.163.com/api/v1/artist/${id}`,
          body: {
            ext: 'true',
            private_cloud: 'true',
            top: limit,
            id: id
          },
          encode: 'netease_AESCBC',
          format: 'hotSongs'
        };
        break;
        
      case 'tencent':
        api = {
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
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'GET',
          url: 'http://mobilecdn.kugou.com/api/v3/singer/song',
          body: {
            singerid: id,
            area_code: 1,
            page: 1,
            plat: 0,
            pagesize: limit,
            version: 8990
          },
          format: 'data.info'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.artist.getSongList',
            artistid: id,
            limits: limit,
            platform: 'darwin',
            offset: 0,
            tinguid: 0,
            version: '11.2.1'
          },
          format: 'songlist'
        };
        break;
        
      case 'kuwo':
        api = {
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
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取播放列表
  async playlist(id) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: 'http://music.163.com/api/v6/playlist/detail',
          body: {
            s: '0',
            id: id,
            n: '1000',
            t: '0'
          },
          encode: 'netease_AESCBC',
          format: 'playlist.tracks'
        };
        break;
        
      case 'tencent':
        api = {
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
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'GET',
          url: 'http://mobilecdn.kugou.com/api/v3/special/song',
          body: {
            specialid: id,
            area_code: 1,
            page: 1,
            plat: 2,
            pagesize: -1,
            version: 8990
          },
          format: 'data.info'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.diy.gedanInfo',
            listid: id,
            platform: 'darwin',
            version: '11.2.1'
          },
          format: 'content'
        };
        break;
        
      case 'kuwo':
        api = {
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
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取音频播放链接
  async url(id, br = 320) {
    this.temp.br = br;
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: 'http://music.163.com/api/song/enhance/player/url',
          body: {
            ids: [id],
            br: br * 1000
          },
          encode: 'netease_AESCBC',
          decode: 'netease_url'
        };
        break;
        
      case 'tencent':
        api = {
          method: 'GET',
          url: 'https://c.y.qq.com/v8/fcg-bin/fcg_play_single_song.fcg',
          body: {
            songmid: id,
            platform: 'yqq',
            format: 'json'
          },
          decode: 'tencent_url'
        };
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'POST',
          url: 'http://media.store.kugou.com/v1/get_res_privilege',
          body: JSON.stringify({
            relate: 1,
            userid: '0',
            vip: 0,
            appid: 1000,
            token: '',
            behavior: 'download',
            area_code: '1',
            clientver: '8990',
            resource: [{
              id: 0,
              type: 'audio',
              hash: id
            }]
          }),
          decode: 'kugou_url'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.song.getInfos',
            songid: id,
            res: 1,
            platform: 'darwin',
            version: '1.0.0'
          },
          encode: 'baidu_AESCBC',
          decode: 'baidu_url'
        };
        break;
        
      case 'kuwo':
        api = {
          method: 'GET',
          url: 'http://www.kuwo.cn/api/v1/www/music/playUrl',
          body: {
            mid: id,
            type: 'music',
            httpsStatus: 1
          },
          decode: 'kuwo_url'
        };
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取歌词
  async lyric(id) {
    let api;
    
    switch (this.server) {
      case 'netease':
        api = {
          method: 'POST',
          url: 'http://music.163.com/api/song/lyric',
          body: {
            id: id,
            os: 'linux',
            lv: -1,
            kv: -1,
            tv: -1
          },
          encode: 'netease_AESCBC',
          decode: 'netease_lyric'
        };
        break;
        
      case 'tencent':
        api = {
          method: 'GET',
          url: 'https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg',
          body: {
            songmid: id,
            g_tk: '5381'
          },
          decode: 'tencent_lyric'
        };
        break;
        
      case 'xiami':
        api = {
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
        break;
        
      case 'kugou':
        api = {
          method: 'GET',
          url: 'http://krcs.kugou.com/search',
          body: {
            keyword: '%20-%20',
            ver: 1,
            hash: id,
            client: 'mobi',
            man: 'yes'
          },
          decode: 'kugou_lyric'
        };
        break;
        
      case 'baidu':
        api = {
          method: 'GET',
          url: 'http://musicapi.taihe.com/v1/restserver/ting',
          body: {
            from: 'qianqianmini',
            method: 'baidu.ting.song.lry',
            songid: id,
            platform: 'darwin',
            version: '1.0.0'
          },
          decode: 'baidu_lyric'
        };
        break;
        
      case 'kuwo':
        api = {
          method: 'GET',
          url: 'http://m.kuwo.cn/newh5/singles/songinfoandlrc',
          body: {
            musicId: id,
            httpsStatus: 1
          },
          decode: 'kuwo_lyric'
        };
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return await this._exec(api);
  }

  // 获取封面图片
  async pic(id, size = 300) {
    let url;
    
    switch (this.server) {
      case 'netease':
        url = `https://p3.music.126.net/${this._netease_encryptId(id)}/${id}.jpg?param=${size}y${size}`;
        break;
        
      case 'tencent':
        url = `https://y.gtimg.cn/music/photo_new/T002R${size}x${size}M000${id}.jpg?max_age=2592000`;
        break;
        
      case 'xiami':
        const format = this.isFormat;
        const data = await this.format(false).song(id);
        this.isFormat = format;
        const songData = JSON.parse(data);
        url = songData.data.data.songDetail.albumLogo;
        url = url.replace('http:', 'https:') + `@1e_1c_100Q_${size}h_${size}w`;
        break;
        
      case 'kugou':
        const formatKugou = this.isFormat;
        const dataKugou = await this.format(false).song(id);
        this.isFormat = formatKugou;
        const songDataKugou = JSON.parse(dataKugou);
        url = songDataKugou.imgUrl;
        url = url.replace('{size}', '400');
        break;
        
      case 'baidu':
        const formatBaidu = this.isFormat;
        const dataBaidu = await this.format(false).song(id);
        this.isFormat = formatBaidu;
        const songDataBaidu = JSON.parse(dataBaidu);
        url = songDataBaidu.songinfo.pic_radio || songDataBaidu.songinfo.pic_small;
        break;
        
      case 'kuwo':
        const formatKuwo = this.isFormat;
        const dataKuwo = await this.format(false).song(id);
        this.isFormat = formatKuwo;
        const songDataKuwo = JSON.parse(dataKuwo);
        url = songDataKuwo.data.pic || songDataKuwo.data.albumpic;
        break;
        
      default:
        throw new Error(`Unsupported server: ${this.server}`);
    }
    
    return JSON.stringify({ url: url });
  }

  // ========== 数据格式化方法 ==========
  
  // 网易云音乐数据格式化
  _format_netease(data) {
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

  // 腾讯音乐数据格式化
  _format_tencent(data) {
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

  // 虾米音乐数据格式化
  _format_xiami(data) {
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

  // 酷狗音乐数据格式化
  _format_kugou(data) {
    const filename = data.filename || data.fileName;
    const result = {
      id: data.hash,
      name: filename,
      artist: [],
      album: data.album_name || '',
      url_id: data.hash,
      pic_id: data.hash,
      lyric_id: data.hash,
      source: 'kugou'
    };
    
    const parts = filename.split(' - ');
    if (parts.length >= 2) {
      result.artist = parts[0].split('、');
      result.name = parts[1];
    }
    
    return result;
  }

  // 百度音乐数据格式化
  _format_baidu(data) {
    return {
      id: data.song_id,
      name: data.title,
      artist: data.author.split(','),
      album: data.album_title,
      pic_id: data.song_id,
      url_id: data.song_id,
      lyric_id: data.song_id,
      source: 'baidu'
    };
  }

  // 酷我音乐数据格式化
  _format_kuwo(data) {
    return {
      id: data.rid,
      name: data.name,
      artist: data.artist.split('&'),
      album: data.album,
      pic_id: data.rid,
      url_id: data.rid,
      lyric_id: data.rid,
      source: 'kuwo'
    };
  }

  // ========== URL 解码方法 ==========
  
  // 网易云音乐 URL 解码
  netease_url(result) {
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

  // 腾讯音乐 URL 解码
  async tencent_url(result) {
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
    const uinMatch = this.header.Cookie && this.header.Cookie.match(/uin=(\d+)/);
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
    
    const response = JSON.parse(await this._exec(api));
    const vkeys = response.req_0.data.midurlinfo;
    
    let url;
    for (let i = 0; i < qualityMap.length; i++) {
      const [sizeKey, br, prefix, ext] = qualityMap[i];
      if (data.data[0].file[sizeKey] && br <= this.temp.br) {
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

  // 虾米音乐 URL 解码
  xiami_url(result) {
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
      if (br <= this.temp.br && br > maxBr) {
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

  // 酷狗音乐 URL 解码
  async kugou_url(result) {
    const data = JSON.parse(result);
    
    let maxBr = 0;
    let url;
    
    for (const item of data.data[0].relate_goods) {
      if (item.info.bitrate <= this.temp.br && item.info.bitrate > maxBr) {
        const api = {
          method: 'GET',
          url: 'http://trackercdn.kugou.com/i/v2/',
          body: {
            hash: item.hash,
            key: crypto.createHash('md5').update(item.hash + 'kgcloudv2').digest('hex'),
            pid: 3,
            behavior: 'play',
            cmd: '25',
            version: 8990
          }
        };
        
        const response = JSON.parse(await this._exec(api));
        if (response.url) {
          maxBr = response.bitRate / 1000;
          url = {
            url: Array.isArray(response.url) ? response.url[0] : response.url,
            size: response.fileSize,
            br: response.bitRate / 1000
          };
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

  // 百度音乐 URL 解码
  baidu_url(result) {
    const data = JSON.parse(result);
    
    let maxBr = 0;
    let url;
    
    data.songurl.url.forEach(item => {
      if (item.file_bitrate <= this.temp.br && item.file_bitrate > maxBr) {
        maxBr = item.file_bitrate;
        url = {
          url: item.file_link,
          br: item.file_bitrate
        };
      }
    });
    
    if (!url) {
      url = {
        url: '',
        br: -1
      };
    }
    
    return JSON.stringify(url);
  }

  // 酷我音乐 URL 解码
  kuwo_url(result) {
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

  // ========== 歌词解码方法 ==========
  
  // 网易云音乐歌词解码
  netease_lyric(result) {
    const data = JSON.parse(result);
    const lyricData = {
      lyric: (data.lrc && data.lrc.lyric) ? data.lrc.lyric : '',
      tlyric: (data.tlyric && data.tlyric.lyric) ? data.tlyric.lyric : ''
    };
    
    return JSON.stringify(lyricData);
  }

  // 腾讯音乐歌词解码
  tencent_lyric(result) {
    const jsonStr = result.substring(18, result.length - 1);
    const data = JSON.parse(jsonStr);
    
    const lyricData = {
      lyric: data.lyric ? Buffer.from(data.lyric, 'base64').toString() : '',
      tlyric: data.trans ? Buffer.from(data.trans, 'base64').toString() : ''
    };
    
    return JSON.stringify(lyricData);
  }

  // 虾米音乐歌词解码
  xiami_lyric(result) {
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

  // 酷狗音乐歌词解码
  async kugou_lyric(result) {
    const data = JSON.parse(result);
    
    if (!data.candidates || data.candidates.length === 0) {
      return JSON.stringify({ lyric: '', tlyric: '' });
    }
    
    const api = {
      method: 'GET',
      url: 'http://lyrics.kugou.com/download',
      body: {
        charset: 'utf8',
        accesskey: data.candidates[0].accesskey,
        id: data.candidates[0].id,
        client: 'mobi',
        fmt: 'lrc',
        ver: 1
      }
    };
    
    const response = JSON.parse(await this._exec(api));
    const lyricData = {
      lyric: Buffer.from(response.content, 'base64').toString(),
      tlyric: ''
    };
    
    return JSON.stringify(lyricData);
  }

  // 百度音乐歌词解码
  baidu_lyric(result) {
    const data = JSON.parse(result);
    const lyricData = {
      lyric: data.lrcContent || '',
      tlyric: ''
    };
    
    return JSON.stringify(lyricData);
  }

  // 酷我音乐歌词解码
  kuwo_lyric(result) {
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

export default Meting;