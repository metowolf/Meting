# Meting 架构重构说明

## 重构概述

本次重构将原本单一文件中的不同音乐厂商逻辑抽离到独立的 Provider 文件中，采用了标准的 Provider 模式，提高了代码的可维护性和扩展性。

## 新架构结构

```
src/
├── meting.js                 # 主入口文件（重构后）
├── meting-original.js       # 原始文件备份
└── providers/               # 音乐平台提供者目录
    ├── index.js            # Provider 工厂类
    ├── base.js             # 基础 Provider 接口
    ├── netease.js          # 网易云音乐 Provider
    ├── tencent.js          # 腾讯音乐 Provider
    ├── kugou.js            # 酷狗音乐 Provider
    ├── baidu.js            # 百度音乐 Provider
    └── kuwo.js             # 酷我音乐 Provider
```

## 架构优势

### 1. 单一职责原则
- 每个 Provider 只负责一个音乐平台的逻辑
- 主 Meting 类只负责协调和通用功能

### 2. 开放封闭原则
- 添加新平台只需创建新的 Provider，无需修改现有代码
- 修改某个平台的逻辑不会影响其他平台

### 3. 内部闭环设计
- 每个 Provider 内部处理自己的编码/解码逻辑
- 避免了主类中的方法映射和统一处理
- 真正实现了平台逻辑的完全隔离

### 4. 代码组织清晰
- 每个文件职责明确，便于维护
- 相关功能聚合在一起
- 版本号在构建时从 package.json 注入，避免运行时文件读取

## 核心组件

### BaseProvider 基础类
所有平台 Provider 的基础接口，定义了标准的方法：
- `getHeaders()`: 获取请求头配置
- `search()`: 搜索功能
- `song()`: 获取歌曲详情
- `album()`: 获取专辑信息
- `artist()`: 获取艺术家作品
- `playlist()`: 获取播放列表
- `url()`: 获取播放链接
- `lyric()`: 获取歌词
- `pic()`: 获取封面图片
- `format()`: 数据格式化
- `encode()`: 请求编码（如需要）
- `urlDecode()`: URL解码（如需要）
- `lyricDecode()`: 歌词解码（如需要）

### ProviderFactory 工厂类
负责创建和管理 Provider 实例：
- `create(platform, meting)`: 创建指定平台的 Provider
- `getSupportedPlatforms()`: 获取支持的平台列表
- `isSupported(platform)`: 检查平台是否支持

### 主 Meting 类
协调各个 Provider，提供统一的 API 接口：
- 保持原有的公共 API 不变
- 简化为纯粹的协调者角色
- 将具体执行逻辑完全委托给 Provider
- 版本号在构建时注入，无运行时开销

## 使用方式

重构后的使用方式与原版完全兼容：

```javascript
import Meting from './src/meting.js';

// 创建实例
const meting = new Meting('netease');

// 或者动态切换平台
meting.site('tencent');

// 使用 API（与原版完全相同）
const result = await meting.search('稻香');
```

## 扩展新平台

添加新平台只需要：

1. 在 `src/providers/` 下创建新的 Provider 文件
2. 继承 `BaseProvider` 并实现所需方法
3. 在 `src/providers/index.js` 中注册新 Provider

示例：
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

  // ... 实现其他必需方法
}
```

## 构建系统

### Rollup 构建配置
- 使用自定义插件在构建时注入版本号
- 源码中使用 `__VERSION__` 占位符
- 构建时自动替换为 package.json 中的实际版本
- 避免运行时文件系统读取，提升性能

### 构建流程
```bash
npm run build  # 构建 ESM 和 CJS 两种格式
```

构建后：
- `lib/meting.esm.js` - ES Module 格式
- `lib/meting.js` - CommonJS 格式

## 兼容性

- ✅ 保持原有 API 接口不变
- ✅ 保持原有使用方式不变
- ✅ 保持原有功能特性不变
- ✅ 支持原有的链式调用
- ✅ 支持原有的配置方法
- ✅ 构建时版本号注入，无运行时开销

## 测试验证

已通过以下测试：
- 基础功能测试（`test/test.js`）
- 架构验证测试（`test/simple-test.js`）
- 版本号注入测试（`test/build-version-test.js`）
- 各平台 Provider 测试

重构成功保持了所有原有功能，同时大大提升了代码的可维护性和扩展性，并优化了运行时性能。