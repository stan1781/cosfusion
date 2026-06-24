# 从 wc-enhanced-tools 迁移

`wc-enhanced-tools` 插件的前台/单品能力已迁入 TailwindScore `modules/`。新站只使用 Kirki `theme_mod`，**不**从插件 `get_option( 'wc_*' )` 回退。

## 开发环境

- 本地默认将 **整个 `wc-enhanced-tools` 设为 inactive**
- 同一能力不得插件与主题双开

## 已迁入模块

见 [modules/README.md](../modules/README.md) 登记表：

- gallery、variations、size-guide
- related-products、quick-view、review-images
- typography、account-auth（主题原生模块）

## 模块开发边界

| 层 | 路径 |
|----|------|
| PHP 模块 | `modules/*/includes/` |
| 模板 | `woocommerce/`、`template-parts/` |
| TypeScript | `src/ts/product/`、`src/ts/shop/` |
| 样式 | `src/scss/` |

禁止：Bootstrap、`data-bs-toggle`、插件路径 `wc_get_template` filter 覆盖。

## 产品 meta 兼容

保留既有 meta 键名（如 `_wc_variation_selector_*`、`_size_guide*`），以兼容 feed-export 等工具。自定义数据库表在功能未迁完前勿删除。

## 新会话 / AI 工作指引

1. 阅读 [modules/README.md](../modules/README.md) 确认边界与登记表
2. 独立插件任务改读 [integrations/README.md](../integrations/README.md)
3. 阅读目标模块 `CHANGELOG.md` 与 `archive/superpowers/plans/*-implementation.md`
4. 对照插件源只读参考，在主题实现
5. 完成后更新登记表与 `CHANGELOG.md`

## 完整归档规范

[archive/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md](../archive/superpowers/specs/2026-05-22-wc-enhanced-tools-migration-design.md)
