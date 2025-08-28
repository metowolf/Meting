# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

Meting Node.js 版本是一个强大的音乐 API 框架，用于加速音乐相关应用的开发。这是原版 PHP Meting 项目的 Node.js 移植版本，支持多个主流音乐平台的 API 调用，包括网易云音乐(netease)、腾讯音乐(tencent)、虾米音乐(xiami)、酷狗音乐(kugou)、百度音乐(baidu)和酷我音乐(kuwo)。

## 开发相关命令

### 运行和测试
```bash
# 运行示例
npm start
# 或
node example/example.js

# 运行测试
npm test  
# 或
node test/test.js

# 直接运行示例（替代命令）
npm run example
```

### 环境要求
- Node.js >= 12.0.0
- 无外部依赖，仅使用 Node.js 内置模块

## 核心架构

### 单文件设计
- 整个框架只有一个核心文件：`lib/meting.js`
- 使用 ES6 类设计，支持链式调用
- 基于 Promise 的异步 API，支持 async/await

### 主要功能模块
1. **平台管理** - `site(server)` 方法切换不同音乐平台
2. **搜索功能** - `search(keyword, option)` 异步搜索歌曲、专辑、艺术家
3. **歌曲详情** - `song(id)` 异步获取单首歌曲信息
4. **专辑管理** - `album(id)` 异步获取专辑内容
5. **艺术家管理** - `artist(id, limit)` 异步获取艺术家作品
6. **播放列表** - `playlist(id)` 异步获取歌单内容
7. **音频链接** - `url(id, br)` 异步获取音频播放链接
8. **歌词获取** - `lyric(id)` 异步获取歌词内容
9. **封面图片** - `pic(id, size)` 异步获取专辑封面

### 内部方法架构
- `_exec(api)` - 执行 API 请求的核心方法
- `_curl(url, payload, headerOnly)` - HTTP 请求处理方法
- `_curlset()` - 设置不同平台的请求头
- `_clean(raw, rule)` - 数据格式化和清理
- 各平台专用的编码/解码方法（如 `netease_AESCBC`, `xiami_sign` 等）

### 异步设计特点
- 所有 API 方法都返回 Promise
- 支持 async/await 语法
- 错误处理通过 try/catch 机制
- 内置请求延迟机制防止频率限制

## 重要设计模式

### 适配器模式
每个音乐平台都有对应的格式化方法，统一数据结构：
- 各平台返回统一的 JSON 格式
- `format(true)` 开启时进行数据标准化
- 支持原始数据获取 `format(false)`

### 策略模式
不同平台的请求处理策略通过方法映射实现：
- 每个平台有独特的 API 端点配置
- 平台特定的加密和签名方式
- 动态的请求头和参数处理

### 内置加密支持
- 网易云音乐：AES-CBC 加密 + RSA 公钥加密
- 虾米音乐：签名验证机制
- 百度音乐：AES 加密
- 使用 Node.js 内置 crypto 模块，无外部依赖

## 数据格式

### 标准化歌曲格式（format: true）
```javascript
{
  "id": "歌曲ID",
  "name": "歌曲名称", 
  "artist": ["艺术家1", "艺术家2"],
  "album": "专辑名称",
  "pic_id": "封面图片ID",
  "url_id": "播放链接ID", 
  "lyric_id": "歌词ID",
  "source": "平台标识"
}
```

## 注意事项

### API 调用限制
- 建议在连续请求间添加延迟（测试中使用 2 秒间隔）
- 避免过于频繁的 API 调用导致被限制
- 各平台可能有不同的频率限制策略

### 平台兼容性
- 音乐平台 API 可能随时变更，需要定期更新
- 部分功能可能因版权限制无法获取（如播放链接）
- 某些平台可能需要 Cookie 验证

### 错误处理模式
```javascript
try {
  const result = await meting.search('关键词');
  // 处理结果
} catch (error) {
  // 可以尝试切换平台重试
  meting.site('tencent');
  const fallback = await meting.search('关键词');
}
```