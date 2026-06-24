# 归档文档

本目录保留项目设计与实施过程中的原始 spec 与 plan，供追溯决策背景。**日常开发请以 [docs/README.md](../README.md) 正式文档为准。**

## 目录

```text
archive/superpowers/
  specs/    # 设计规范（长期参考价值）
  plans/    # 分阶段实施计划（历史记录）
```

## 主要归档文件

| 文件 | 说明 |
|------|------|
| `specs/2026-05-19-site-shell-and-theme-tokens-design.md` | 站点壳层与设计令牌原始方案 → 现 [architecture/design-tokens.md](../architecture/design-tokens.md) |
| `specs/2026-05-21-woocommerce-purchase-flow-design.md` | 购买流程原始设计 → 现 [features/purchase-flow.md](../features/purchase-flow.md) |
| `specs/2026-05-22-wc-enhanced-tools-migration-design.md` | 插件迁移规范 → 现 [modules/README.md](../modules/README.md) |
| `specs/2026-05-23-plugin-theme-asset-integration-design.md` | 插件整合规范 → 现 [integrations/README.md](../integrations/README.md) |
| `plans/*-implementation.md` | 各模块/功能的逐步实施记录 |

## 维护说明

- 新内容写入 `docs/` 正式目录，不再向 `archive/` 追加 spec
- 归档内路径若指向旧环境（如 `D:/www/base/`），以当前仓库路径为准
- 模块 `CHANGELOG.md` 记录实现层面的增量变更
