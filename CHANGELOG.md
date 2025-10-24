# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.0] - 2025-10-25

### Removed
- **BREAKING CHANGE**: Removed Xiami Music platform support
  - Deleted `src/providers/xiami.js` provider implementation
  - Removed Xiami from all documentation and examples
  - Updated supported platforms list: NetEase, Tencent, KuGou, Baidu, Kuwo (5 platforms)
  - Reason: Xiami Music service has been discontinued

### Changed
- Updated `package.json` description to reflect current supported platforms
- Updated `README.md` to remove Xiami references from features list, platform codes, and support table
- Updated `CLAUDE.md` project overview
- Updated `ARCHITECTURE.md` structure documentation
- Updated `test/example.js` comments
- Cleaned up keywords in `package.json`

### Migration Guide
If you were using Xiami platform (`'xiami'`), please switch to one of the supported platforms:
- NetEase Cloud Music (`'netease'`)
- Tencent Music (`'tencent'`)
- KuGou Music (`'kugou'`)
- Baidu Music (`'baidu'`)
- Kuwo Music (`'kuwo'`)

```javascript
// Before
const meting = new Meting('xiami');

// After - choose an alternative platform
const meting = new Meting('netease');
```

Note: The library will automatically fallback to `'netease'` if an unsupported platform is specified.

## [1.5.14] - 2025-01-XX

### Fixed
- Added HTML entity decoding for Tencent Music lyrics

### Changed
- Improved npm authentication configuration in GitHub Actions
- Simplified npm authentication using `~/.npmrc`

## [1.5.13] - Earlier

### Added
- Initial stable release with 6 platform support
- Provider pattern architecture
- Zero-dependency implementation
- Built-in encryption support
- Promise-based async/await APIs
- Unified data format across platforms
- Version number injection at build time

---

## Version History

- **v1.6.0** - Removed Xiami Music support (breaking change)
- **v1.5.14** - Bug fixes and CI improvements
- **v1.5.13** - Stable release with complete platform support

[1.6.0]: https://github.com/metowolf/Meting/compare/v1.5.14...v1.6.0
[1.5.14]: https://github.com/metowolf/Meting/compare/v1.5.13...v1.5.14
[1.5.13]: https://github.com/metowolf/Meting/releases/tag/v1.5.13
