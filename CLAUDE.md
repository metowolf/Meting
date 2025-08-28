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

### 关键设计原则

1. **内部闭环**: 每个 Provider 完全独立处理自己的编码/解码逻辑，通过 `handleEncode()` 和 `handleDecode()` 方法
2. **单一职责**: 每个文件只负责一个平台或一个功能模块
3. **无方法映射**: 避免了主类中的方法名映射，Provider 内部直接处理特定逻辑

### 执行流程
```
用户 API 调用 → 主 Meting 类 → Provider.executeRequest() → 平台特定处理 → 返回标准化结果
```

### 版本号管理
- 源码中使用 `__VERSION__` 占位符
- 构建时通过 Rollup 自定义插件注入 package.json 中的实际版本号
- 避免运行时文件系统读取，提升性能

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
    // 实现搜索逻辑
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
try {
  const result = await meting.search('关键词');
  // 处理结果
} catch (error) {
  // 可以尝试切换平台重试
  meting.site('tencent');
  const fallback = await meting.search('关键词');
}
```

## 构建系统

- 使用 Rollup 构建 ESM (`lib/meting.esm.js`) 和 CJS (`lib/meting.js`) 两种格式
- 构建时版本号注入，无运行时开销
- 支持开发模式下的文件监听自动构建
- 构建前会自动进行测试验证