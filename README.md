# TailwindScore

基于 PHP、SCSS 设计令牌、TypeScript、Alpine.js 与 WooCommerce 的 WordPress 商城主题。

## 特性

- 统一设计令牌（`--ts-*`）与语义化 `.*` 组件类
- Drawer-first 购买流程（Store API + 统一通知）
- 可配置主题模块：图库、变体选择、Size Guide、Quick View、评论附图等
- 独立插件前台资源整合（mailnest、订单流程等）
- Vite 构建、按页加载、TypeScript 类型检查

## 快速开始

```bash
npm install
npm run build
npm run typecheck
```

开发时启用 Vite HMR：见 [docs/development/getting-started.md](docs/development/getting-started.md)。

**请禁用 `wc-enhanced-tools`**，避免与主题模块冲突。

## 文档

完整文档索引：[docs/README.md](docs/README.md)

| 分类 | 入口 |
|------|------|
| 开发 | [getting-started](docs/development/getting-started.md) |
| 架构 | [overview](docs/architecture/overview.md)、[design-tokens](docs/architecture/design-tokens.md) |
| 功能 | [navigation](docs/features/navigation.md)、[purchase-flow](docs/features/purchase-flow.md) |
| 模块 | [modules](docs/modules/README.md) |
| 插件整合 | [integrations](docs/integrations/README.md) |

## 主题模块

| 模块 | 说明 |
|------|------|
| gallery | Swiper 单品图库、变体相册 |
| variations | 图片/色块 swatch 变体选择器 |
| size-guide | JSON 尺码表 modal + 内嵌 Tab |
| quick-view | 归档页 Quick View |
| related-products | 单品相关产品 |
| review-images | 评论附图、买家秀 |
| account-auth | 分屏登录、独立注册 |
| typography | 字体与排版 Kirki |

详见 [docs/modules/README.md](docs/modules/README.md)。

## 技术栈

PHP 8+ · WordPress · WooCommerce · SCSS · TypeScript · Vite · Alpine.js · Swiper · PhotoSwipe · Kirki
