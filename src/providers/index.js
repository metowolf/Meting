import NeteaseProvider from './netease.js';
import TencentProvider from './tencent.js';
import XiamiProvider from './xiami.js';
import KugouProvider from './kugou.js';
import BaiduProvider from './baidu.js';
import KuwoProvider from './kuwo.js';

/**
 * 音乐平台提供者工厂
 */
export default class ProviderFactory {
  static providers = {
    netease: NeteaseProvider,
    tencent: TencentProvider,
    xiami: XiamiProvider,
    kugou: KugouProvider,
    baidu: BaiduProvider,
    kuwo: KuwoProvider
  };

  /**
   * 创建指定平台的提供者实例
   * @param {string} platform 平台名称
   * @param {Object} meting Meting 实例
   * @returns {BaseProvider} 平台提供者实例
   */
  static create(platform, meting) {
    const ProviderClass = this.providers[platform];
    if (!ProviderClass) {
      throw new Error(`Unsupported platform: ${platform}`);
    }
    return new ProviderClass(meting);
  }

  /**
   * 获取支持的平台列表
   * @returns {string[]} 支持的平台名称数组
   */
  static getSupportedPlatforms() {
    return Object.keys(this.providers);
  }

  /**
   * 检查平台是否支持
   * @param {string} platform 平台名称
   * @returns {boolean} 是否支持
   */
  static isSupported(platform) {
    return platform in this.providers;
  }
}