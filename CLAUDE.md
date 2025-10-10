# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

Meting Node.js 版本是一个强大的音乐 API 框架，用于加速音乐相关应用的开发。这是原版 PHP Meting 项目的 Node.js 移植版本，支持多个主流音乐平台的 API 调用，包括网易云音乐(netease)、腾讯音乐(tencent)、虾米音乐(xiami)、酷狗音乐(kugou)、百度音乐(baidu)和酷我音乐(kuwo)。

## 开发相关命令

### 构建和测试
```bash
# 构建库文件（生成 ESM 和 CJS 格式）
npm run build

# 开发模式（文件监听自动构建）
npm run dev

# 运行完整测试（会先构建）
npm test

# 运行示例代码
npm start
# 或
npm run example

# 直接测试特定文件（需要先构建）
node test/test.js           # 完整平台测试
node test/example.js        # 运行示例代码
```

### 单独测试命令
```bash
# 快速测试单个平台（需要先构建）
node -e "
import Meting from './lib/meting.js';
const m = new Meting('netease');
m.format(true);
m.search('test', { limit: 5 }).then(console.log);
"

# 验证版本号注入
node -e "import Meting from './lib/meting.js'; console.log('Version:', (new Meting()).VERSION)"
```

### 环境要求
- Node.js >= 12.0.0
- 无外部依赖，仅使用 Node.js 内置模块

## 核心架构

### Provider 模式设计
项目采用 Provider 模式重构，实现了真正的内部闭环设计：

- **主 Meting 类** (`src/meting.js`): 纯粹的协调者，负责平台切换和 API 委托
- **Provider 工厂** (`src/providers/index.js`): 管理所有平台 Provider 的创建和注册
- **基础 Provider** (`src/providers/base.js`): 定义统一接口和默认实现
- **平台 Provider** (`src/providers/{platform}.js`): 每个音乐平台的独立实现
- **统一 EAPI 流程**: 所有请求都通过统一的 EAPI 请求栈执行，无需用户选择不同的协议

### 关键设计原则

1. **内部闭环**: 每个 Provider 完全独立处理自己的编码/解码逻辑，通过 `handleEncode()` 和 `handleDecode()` 方法
2. **单一职责**: 每个文件只负责一个平台或一个功能模块
3. **无方法映射**: 避免了主类中的方法名映射，Provider 内部直接处理特定逻辑

### 执行流程
```
用户 API 调用 → 主 Meting 类 → Provider.executeRequest() → 平台特定处理 → 返回标准化结果
```

**详细请求处理流程：**
1. **API 调用**: `meting.search('关键词', { page: 1, limit: 30 })` 等公共方法
2. **Provider 委托**: 主类调用对应 Provider 的方法获取 API 配置
3. **编码处理**: 如果需要，Provider 调用 `handleEncode()` 进行请求加密
4. **HTTP 请求**: 使用内置 fetch API 发送统一的 EAPI 请求，包含重试机制和超时控制
5. **解码处理**: 如果需要，Provider 调用 `handleDecode()` 进行响应解密
6. **数据格式化**: 根据 `format()` 设置决定是否标准化数据结构
7. **结果返回**: 返回 JSON 字符串格式的处理结果

## 公共 API
- `search(keyword, option = {})`: 根据关键词搜索音乐，返回 Promise；`option` 支持 `type`（分类，默认 1 即歌曲）、`page`（页码，默认 1）、`limit`（每页数量，默认 30）
- `song(id)`: 获取歌曲详情
- `album(id)`: 获取专辑信息
- `artist(id, limit = 50)`: 获取艺术家作品列表
- `playlist(id)`: 获取播放列表
- `url(id, br = 320)`: 获取音频播放地址，可指定码率（kbps）
- `lyric(id)`: 获取歌词
- `pic(id, size = 300)`: 获取封面图片信息，可指定尺寸

### 错误处理机制
- **网络错误**: 自动重试 3 次，每次间隔 1 秒
- **超时控制**: 默认 20 秒请求超时
- **平台切换**: 支持动态切换音乐平台作为降级方案
- **错误状态**: 错误信息存储在 `meting.error` 和 `meting.status` 属性中

### 版本号管理
- 源码中使用 `__VERSION__` 占位符
- 构建时通过 Rollup 自定义插件注入 package.json 中的实际版本号
- 避免运行时文件系统读取，提升性能

**构建注入机制详情：**
```javascript
// rollup.config.js 中的版本注入插件
{
  name: 'inject-version',
  transform(code, id) {
    if (id.endsWith('src/meting.js')) {
      return code.replace('__VERSION__', packageInfo.version);
    }
    return null;
  }
}
```

**使用方式：**
```javascript
const meting = new Meting();
console.log(meting.VERSION); // 输出实际版本号，如 "1.5.13"
```

## 重要设计模式

### 适配器模式
每个音乐平台都有对应的格式化方法，统一数据结构：
- 各平台返回统一的 JSON 格式
- `format(true)` 开启时进行数据标准化

### 策略模式
不同平台的请求处理策略通过 Provider 实现：
- 每个平台有独特的 API 端点配置
- 平台特定的加密和签名方式
- 动态的请求头和参数处理

### 内置加密支持
- 网易云音乐：AES-CBC 加密 + RSA 公钥加密
- 虾米音乐：签名验证机制
- 百度音乐：AES 加密
- 使用 Node.js 内置 crypto 模块，无外部依赖

## 添加新平台

添加新平台的完整流程：

1. 在 `src/providers/` 下创建新的 Provider 文件
2. 继承 `BaseProvider` 并实现所有必需方法
3. 实现平台特定的 `handleEncode()` 和 `handleDecode()` 方法
4. 在 `src/providers/index.js` 中注册新 Provider
5. 添加对应的测试用例

```javascript
// src/providers/newplatform.js
import BaseProvider from './base.js';

export default class NewPlatformProvider extends BaseProvider {
  constructor(meting) {
    super(meting);
    this.name = 'newplatform';
  }

  getHeaders() {
    // 实现平台特定的请求头
  }

  search(keyword, option = {}) {
    // 实现搜索逻辑并返回统一的 EAPI 配置
  }

  async handleEncode(api) {
    // 处理平台特定的编码逻辑
    return api;
  }

  async handleDecode(decodeType, data) {
    // 处理平台特定的解码逻辑
    return data;
  }

  // ... 实现其他必需方法
}
```

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
const meting = new Meting('netease');
meting.format(true);

try {
  const result = await meting.search('关键词', { page: 1, limit: 30 });
  // 处理统一格式的结果
} catch (error) {
  // 记录错误并尝试切换平台重试
  console.error(meting.status);
  meting.site('tencent');
  const fallback = await meting.search('关键词', { page: 1, limit: 30 });
}
```

## 构建系统

### Rollup 配置特点
- **双格式输出**: 同时生成 ESM (`lib/meting.esm.js`) 和 CJS (`lib/meting.js`) 格式
- **版本号注入**: 构建时自动替换源码中的 `__VERSION__` 占位符
- **依赖管理**: 仅使用 Node.js 内置模块，external 配置包含 `crypto`, `url`, `fs`, `path`
- **代码压缩**: 使用 terser 插件进行代码压缩优化
- **开发模式**: 支持文件监听自动构建 (`npm run dev`)

### 构建流程
```bash
# 开发流程
npm run dev    # 监听文件变化，自动重新构建

# 生产构建
npm run build  # 构建两种格式到 lib/ 目录
npm test       # 构建后运行测试验证
npm publish    # 发布前会自动执行 prepublishOnly 构建命令
```

### 输出文件说明
- `lib/meting.esm.js`: ES Module 格式，用于现代打包工具
- `lib/meting.js`: CommonJS 格式，用于 Node.js require 语法
- 两种格式都经过压缩优化，文件大小约 30-40KB
